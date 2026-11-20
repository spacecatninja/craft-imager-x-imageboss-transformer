<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2021 André Elvan
 */

namespace SpaceCatNinja\ImageBossTransformer\Data;

use CraftCms\Cms\Asset\Elements\Asset;
use SpaceCatNinja\ImagerX\Data\BaseTransformedImageModel;
use SpaceCatNinja\ImagerX\Data\TransformedImageInterface;

class ImageBossTransformedImageModel extends BaseTransformedImageModel implements TransformedImageInterface
{
    /**
     * ImageBossTransformedImageModel constructor.
     *
     * @param string|null $imageUrl
     * @param Asset|null  $source
     * @param array       $transform
     * @param bool        $upscale   Whether an image transformed with mode `fit`, or only a width or height, may be enlarged.
     */
    public function __construct(?string $imageUrl = null, ?Asset $source = null, array $transform = [], bool $upscale = true)
    {
        $this->source = $source;

        if ($imageUrl !== null) {
            $this->url = $imageUrl;
        }

        $mode = $transform['mode'] ?? 'crop';

        if (isset($transform['width'], $transform['height'])) {
            $this->width = (int)$transform['width'];
            $this->height = (int)$transform['height'];

            if ($source !== null && $mode === 'fit') {
                [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);

                $transformW = (int)$transform['width'];
                $transformH = (int)$transform['height'];

                // ImageBoss' cover:inside enlarges the image to fit, unless it's told not to
                if ($sourceWidth && $sourceHeight) {
                    if ($sourceWidth / $sourceHeight > $transformW / $transformH) {
                        $useW = $upscale ? $transformW : min($transformW, $sourceWidth);
                        $this->width = $useW;
                        $this->height = round($useW * ($sourceHeight / $sourceWidth));
                    } else {
                        $useH = $upscale ? $transformH : min($transformH, $sourceHeight);
                        $this->width = round($useH * ($sourceWidth / $sourceHeight));
                        $this->height = $useH;
                    }
                }
            }
        } else if (isset($transform['width']) || isset($transform['height'])) {
            if ($source !== null && $transform !== null) {
                [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);
                [$w, $h] = $this->calculateTargetSize($transform, $sourceWidth ?? 1, $sourceHeight ?? 1);

                // The width and height operations don't enlarge the image with withoutEnlargement
                if (!$upscale && $sourceWidth && $sourceHeight && ($w > $sourceWidth || $h > $sourceHeight)) {
                    [$w, $h] = [(int)$sourceWidth, (int)$sourceHeight];
                }

                $this->width = $w;
                $this->height = $h;
            }
        } else {
            // Neither is set, image is not resized. Just get dimensions and return.
            [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);

            $this->width = $sourceWidth;
            $this->height = $sourceHeight;
        }
    }

    /**
     * @param Asset $source
     *
     * @return array
     */
    protected function getSourceImageDimensions(Asset $source): array
    {
        return [$source->getWidth(), $source->getHeight()];
    }

    /**
     * @param array $transform
     * @param int   $sourceWidth
     * @param int   $sourceHeight
     *
     * @return array
     */
    protected function calculateTargetSize(array $transform, int $sourceWidth, int $sourceHeight): array
    {
        $ratio = $sourceWidth / $sourceHeight;

        $w = $transform['width'] ?? null;
        $h = $transform['height'] ?? null;

        if ($w) {
            return [$w, (int)round($w / $ratio)];
        }
        if ($h) {
            return [(int)round($h * $ratio), $h];
        }

        return [0, 0];
    }
}
