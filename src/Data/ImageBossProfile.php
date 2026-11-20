<?php
/**
 * ImageBoss transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\ImageBossTransformer\Data;

use CraftCms\Cms\Component\Component;

/**
 * The config of one ImageBoss profile, from the `profiles` setting.
 */
class ImageBossProfile extends Component
{
    public string $sourceName = '';
    public string $signToken = '';
    public bool $useCloudSourcePath = false;

    /**
     * A path to prepend to the image's path, or an array of paths by volume handle.
     *
     * @var string|array<string, string>
     */
    public array|string $addPath = [];
}
