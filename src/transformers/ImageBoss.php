<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2021 André Elvan
 */

namespace spacecatninja\imagebosstransformer\transformers;

use craft\base\Component;
use craft\elements\Asset;

use craft\fs\Local;
use craft\helpers\App;
use spacecatninja\imagebosstransformer\ImageBossTransformer;
use spacecatninja\imagebosstransformer\helpers\ImageBossHelpers;
use spacecatninja\imagebosstransformer\models\ImageBossTransformedImageModel;
use spacecatninja\imagebosstransformer\models\Settings;
use spacecatninja\imagerx\services\ImagerService;
use spacecatninja\imagerx\transformers\TransformerInterface;
use spacecatninja\imagerx\exceptions\ImagerException;

class ImageBoss extends Component implements TransformerInterface
{

    /**
     * @param Asset $image
     * @param array $transforms
     *
     * @return array|null
     *
     * @throws ImagerException
     */
    public function transform($image, array $transforms): ?array
    {
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
        /** @var Settings $settings */
        $settings = ImageBossTransformer::$plugin->getSettings();
        $config = ImagerService::getConfig();
        $transformerParams = $transform['transformerParams'] ?? [];

        $profileName = $transformerParams['profile'] ?? $settings->defaultProfile;
        $profile = ImageBossHelpers::getProfile($profileName);

        if ($profile === null) {
            throw new ImagerException("No ImageBoss profile with name `$profileName` exists.");
        }

        $urlSegments = [$settings->baseUrl, $profile->sourceName];
        $opts = [];
        $hasBothDimensions = isset($transform['width'], $transform['height']);
        $sizeSegment = '';

        if ($hasBothDimensions) {
            $sizeSegment = $transform['width'].'x'.$transform['height'];
        }

        if ($hasBothDimensions) {
            $mode = $transform['mode'] ?? 'crop';
            $coverMode = $transformerParams['coverMode'] ?? $transform['coverMode'] ?? null;

            switch ($mode) {
                case 'fit':
                    $urlSegments[] = 'cover:inside';
                    $urlSegments[] = $sizeSegment;
                    break;
                case 'stretch':
                case 'croponly':
                    // not supported
                    break;
                case 'letterbox':
                    $urlSegments[] = 'cover:contain';
                    $urlSegments[] = $sizeSegment;

                    $letterboxDef = $config->getSetting('letterbox', $transform);
                    $opts[] = 'fill-color:'.ImageBossHelpers::getLetterboxColor($letterboxDef);
                    break;
                default:
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
        } elseif (isset($transform['width'])) {
            $urlSegments[] = 'width';
            $urlSegments[] = $transform['width'];
        } elseif (isset($transform['height'])) {
            $urlSegments[] = 'height';
            $urlSegments[] = $transform['height'];
        }

        // Set quality
        $opts[] = 'quality:'.ImageBossHelpers::getQualityFromExtension($image->getExtension(), $transform);

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
        $fs = $volume->getFs();

        // Add cloud source path if applicable
        if ($profile->useCloudSourcePath) {
            try {
                if (property_exists($fs, 'subfolder') && $fs->subfolder !== '' && $fs::class !== Local::class) {
                    $urlSegments[] = trim(App::parseEnv($fs->subfolder), '/');
                }
            } catch (\Throwable) {

            }
        }

        if ($volume->getSubpath() !== '') {
            $urlSegments[] = trim(App::parseEnv($volume->getSubpath()), '/');
        }

        // Add file path
        $urlSegments[] = $image->path;

        // Merge to url
        $url = implode('/', $urlSegments);

        // Make secure if signToken is set
        if (!empty($profile->signToken)) {
            $bossToken = hash_hmac('sha256', parse_url($url, PHP_URL_PATH), $profile->signToken);
            $url .= "?bossToken=$bossToken";
        }

        return new ImageBossTransformedImageModel($url, $image, $transform);
    }

}
