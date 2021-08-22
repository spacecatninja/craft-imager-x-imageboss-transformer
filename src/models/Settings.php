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
    public $baseUrl = 'https://img.imageboss.me';
    public $apiKey = '';
    public $defaultProfile = '';
    public $profiles = [];
    public $enableCompression = true;
    public $enableProgressive = true;
    public $enableAutoRotate = true;
}
