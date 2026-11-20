<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2021 André Elvan
 */

namespace SpaceCatNinja\ImageBossTransformer\Transformers;

use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Imagine\Image\Box;
use SpaceCatNinja\ImageBossTransformer\Data\ImageBossTransformedImageModel;
use SpaceCatNinja\ImageBossTransformer\Settings;
use SpaceCatNinja\ImageBossTransformer\Support\ImageBossHelpers;
use SpaceCatNinja\ImagerX\Exceptions\ImagerException;
use SpaceCatNinja\ImagerX\Services\ImagerService;
use SpaceCatNinja\ImagerX\Support\ImagerHelpers;
use SpaceCatNinja\ImagerX\Transformers\TransformerInterface;

use function CraftCms\Cms\t;

class ImageBoss implements TransformerInterface
{

    /**
     * @param Asset|string $image
     * @param array $transforms
     *
     * @return array|null
     *
     * @throws ImagerException
     */
    public function transform(Asset|string $image, array $transforms): ?array
    {
        if (\is_string($image)) {
            throw new ImagerException(t('The ImageBoss transformer can only transform assets, not paths or URLs.', category: 'imager-x-imageboss-transformer'));
        }

        $transformedImages = [];

        foreach ($transforms as $transform) {
            $transformedImages[] = $this->getTransformedImage($image, $transform);
        }

        return $transformedImages;
    }

    /**
     * @param Asset $image
     * @param array $transform
     *
     * @return ImageBossTransformedImageModel
     * @throws ImagerException
     */
    private function getTransformedImage(Asset $image, array $transform): ImageBossTransformedImageModel
    {
        $settings = app(Settings::class);
        $config = ImagerService::getConfig();
        $transformerParams = $transform['transformerParams'] ?? [];
        $allowUpscale = (bool)$config->getSetting('allowUpscale', $transform);

        $profileName = $transformerParams['profile'] ?? $settings->defaultProfile;
        $profile = ImageBossHelpers::getProfile($profileName);

        if ($profile === null) {
            throw new ImagerException("No ImageBoss profile with name `$profileName` exists.");
        }

        $urlSegments = [$settings->baseUrl, $profile->sourceName];
        $opts = [];

        if (isset($transform['width'], $transform['height'])) {
            $mode = $transform['mode'] ?? 'crop';
            $coverMode = $transformerParams['coverMode'] ?? $transform['coverMode'] ?? null;

            // ImageBoss' cover always gives the requested size, and its withoutEnlargement option changes the ratio,
            // so a crop that would enlarge the image is made smaller, with the same ratio, like Imager X does.
            if (!$allowUpscale && !\in_array($mode, ['fit', 'letterbox'], true) && $image->getWidth() && $image->getHeight()) {
                [$width, $height] = ImagerHelpers::enforceMaxSize((int)$transform['width'], (int)$transform['height'], new Box((int)$image->getWidth(), (int)$image->getHeight()), true);
                $transform['width'] = max(1, (int)$width);
                $transform['height'] = max(1, (int)$height);
            }

            $sizeSegment = $transform['width'].'x'.$transform['height'];

            switch ($mode) {
                case 'fit':
                    $urlSegments[] = 'cover:inside';
                    $urlSegments[] = $sizeSegment;

                    // cover:inside enlarges images unless it's told not to
                    if (!$allowUpscale) {
                        $opts[] = 'withoutEnlargement:true';
                    }
                    break;
                case 'letterbox':
                    $urlSegments[] = 'cover:contain';
                    $urlSegments[] = $sizeSegment;

                    $letterboxDef = $config->getSetting('letterbox', $transform);
                    $opts[] = 'fill-color:'.ImageBossHelpers::getLetterboxColor($letterboxDef);
                    break;
                default:
                    // ImageBoss can't stretch or crop without resizing, so `stretch` and `croponly` crop like `crop`
                    if ($coverMode) {
                        $urlSegments[] = 'cover:'.$coverMode;
                        $urlSegments[] = $sizeSegment;

                        if ($coverMode === 'face' && isset($transform['cropZoom'])) {
                            $opts[] = 'fp-z:'.$transform['cropZoom'];
                        }
                    } else {
                        $urlSegments[] = 'cover';
                        $urlSegments[] = $sizeSegment;
                        $opts[] = ImageBossHelpers::getFocalPointString($transform);
                    }
                    break;
            }
        } elseif (isset($transform['width']) || isset($transform['height'])) {
            $urlSegments[] = isset($transform['width']) ? 'width' : 'height';
            $urlSegments[] = $transform['width'] ?? $transform['height'];

            // The width and height operations enlarge images unless they're told not to
            if (!$allowUpscale) {
                $opts[] = 'withoutEnlargement:true';
            }
        } else {
            // Every URL needs an operation, and cdn serves the image at its own size. The width operation isn't used
            // with the image's width, since ImageBoss refuses widths above about 5000 pixels.
            $urlSegments[] = 'cdn';
        }

        // Set quality, from the format the image is transformed to, or the image's own
        $opts[] = 'quality:'.ImageBossHelpers::getQualityFromExtension($transform['format'] ?? $image->getExtension(), $transform);

        // Set default opts
        if ($settings->enableCompression === false) {
            $opts[] = 'compression:false';
        }

        if ($settings->enableProgressive === false) {
            $opts[] = 'progressive:false';
        }

        if ($settings->enableAutoRotate === false) {
            $opts[] = 'autorotate:false';
        }

        if (isset($transform['format'])) {
            $opts[] = 'format:'.$transform['format'];
        }

        // parse effects
        $opts = array_merge($opts, ImageBossHelpers::getEffects($transform));

        // add custom opts
        if (isset($transformerParams['options'])) {
            $opts[] = $transformerParams['options'];
        }

        // Add options
        $urlSegments[] = implode(',', $opts);
        $volume = $image->getVolume();
        $disk = $volume->sourceDisk();
        $subpath = trim($volume->getSubpath(), '/');

        // Add the profile's addPath, or the one for the image's volume
        $addPath = \is_array($profile->addPath) ? ($profile->addPath[$volume->handle] ?? '') : $profile->addPath;
        $addPath = trim((string)$addPath, '/');

        if ($addPath !== '') {
            $urlSegments[] = $addPath;
        }

        if ($profile->useCloudSourcePath && !$disk instanceof LocalFilesystemAdapter) {
            // Add the cloud source path: the disk's root, and the volume's subpath
            $cloudSourcePath = trim($disk->path(''), '/');

            if ($cloudSourcePath !== '') {
                $urlSegments[] = $cloudSourcePath;
            }
        } elseif ($subpath !== '') {
            $urlSegments[] = $subpath;
        }

        // Add file path
        $urlSegments[] = $image->getPath();

        // Merge to url
        $url = implode('/', $urlSegments);

        // Make secure if signToken is set
        if (!empty($profile->signToken)) {
            $bossToken = hash_hmac('sha256', parse_url($url, PHP_URL_PATH), $profile->signToken);
            $url .= "?bossToken=$bossToken";
        }

        return new ImageBossTransformedImageModel($url, $image, $transform, $allowUpscale);
    }

}
