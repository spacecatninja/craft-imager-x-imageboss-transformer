<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2021 André Elvan
 */

namespace spacecatninja\imagebosstransformer\helpers;

use spacecatninja\imagebosstransformer\ImageBossTransformer;
use spacecatninja\imagebosstransformer\models\ImageBossProfile;
use spacecatninja\imagerx\models\ConfigModel;
use spacecatninja\imagerx\services\ImagerService;

class ImageBossHelpers
{
    /**
     * @param string $name
     *
     * @return ImageBossProfile|null
     */
    public static function getProfile(string $name): ?ImageBossProfile
    {
        $settings = ImageBossTransformer::$plugin->getSettings();
        
        if (isset($settings->profiles[$name])) {
            return new ImageBossProfile($settings->profiles[$name]);
        }
        
        return null;
    }

    /**
     * @param array $transform
     *
     * @return string
     */
    public static function getFocalPointString(array $transform): string
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();
        [$x, $y] = explode(' ', $config->getSetting('position', $transform));
        
        $r = 'fp-x:'.(((int)$x)/100).',fp-y:'.(((int)$y)/100);
        
        if (isset($transform['cropZoom'])) {
            $r .= ',fp-z:'.$transform['cropZoom'];
        }
        
        return $r;
    }
    
    /**
     * Gets letterbox params string
     *
     * @param $letterboxDef
     *
     * @return string
     */
    public static function getLetterboxColor($letterboxDef): string
    {
        $color = $letterboxDef['color'];

        $color = str_replace('#', '', $color);
        
        if (strlen($color) === 6) {
            return $color;
        }

        if (strlen($color) === 3) {
            return $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }
        
        return $color;
    }
    
    /**
     * Gets the quality setting based on the extension.
     *
     * @param string     $ext
     * @param array|null $transform
     *
     * @return string
     */
    public static function getQualityFromExtension(string $ext, array $transform = null): string
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        switch ($ext) {
            case 'png':
                $pngCompression = $config->getSetting('pngCompressionLevel', $transform);
                return max(100 - ($pngCompression * 10), 1);
            case 'webp':
                return $config->getSetting('webpQuality', $transform);
            case 'avif':
                return $config->getSetting('avifQuality', $transform);
        }

        return $config->getSetting('jpegQuality', $transform);
    }

    /**
     * @param array $transform
     *
     * @return array
     */
    public static function getEffects(array $transform): array
    {
        if (!isset($transform['effects'])) {
            return [];
        }
        
        $r = [];
        $effects = $transform['effects'];
        
        if ((isset($effects['grayscale']) && $effects['grayscale'] === true) || (isset($effects['greyscale']) && $effects['greyscale'] === true)) {
            $r[] = 'grayscale:true';
        }
        
        if (isset($effects['blur'])) {
            $val = $effects['blur'];
            if (is_bool($val) && $val === true) {
                $r[] = 'blur:2';
            } else {
                $r[] = "blur:$val";
            }
        }
        
        if (isset($effects['sharpen'])) {
            $val = $effects['sharpen'];
            if (is_bool($val) && $val === true) {
                $r[] = 'sharpen:2';
            } else {
                $r[] = "sharpen:$val";
            }
        }
        
        if (isset($effects['gamma'])) {
            $val = $effects['gamma'];
            $r[] = "gamma:$val";
        }
        
        return $r;
    }
}
