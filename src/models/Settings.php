<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2021 André Elvan
 */

namespace spacecatninja\imagebosstransformer\models;

use craft\base\Model;

class Settings extends Model
{
    public string $baseUrl = 'https://img.imageboss.me';
    public string $apiKey = '';
    public string $defaultProfile = '';
    public array $profiles = [];
    public bool $enableCompression = true;
    public bool $enableProgressive = true;
    public bool $enableAutoRotate = true;
}
