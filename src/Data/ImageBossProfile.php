<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2021 André Elvan
 */

namespace SpaceCatNinja\ImageBossTransformer\Data;

use craft\base\Model;

class ImageBossProfile extends Model
{
    public string $sourceName = '';
    public string $signToken = '';
    public bool $useCloudSourcePath = false;
}
