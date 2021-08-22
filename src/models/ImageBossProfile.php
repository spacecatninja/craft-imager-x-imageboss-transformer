<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2021 André Elvan
 */

namespace spacecatninja\imagebosstransformer\models;

use craft\base\Model;

class ImageBossProfile extends Model
{
    public $sourceName = '';
    public $signToken = '';
    public $useCloudSourcePath = false;
}
