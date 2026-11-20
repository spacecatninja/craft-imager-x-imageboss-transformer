<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\ImageBossTransformer;

use CraftCms\Cms\Plugin\PluginSettings;

class Settings extends PluginSettings
{
    public string $baseUrl = 'https://img.imageboss.me';
    public string $apiKey = '';
    public string $defaultProfile = '';
    public array $profiles = [];
    public bool $enableCompression = true;
    public bool $enableProgressive = true;
    public bool $enableAutoRotate = true;
}
