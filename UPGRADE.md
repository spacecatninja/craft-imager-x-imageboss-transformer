# Upgrading to ImageBoss transformer 4 (Craft 6)

This guide lists what changed in version 4 of the ImageBoss transformer for Imager X, and what you need to update in your project and custom code. It applies when upgrading from version 3 (Craft 5 and Imager X 5 or 6).

Templates that transform with ImageBoss through Imager X (`craft.imagerx.transformImage()` with the `imageboss` transformer) aren't affected. Projects need to move and convert the config file. The class changes only affect custom code that uses the plugin's classes directly.

## Requirements

- Craft CMS 6.0.0 or later
- Imager X 7.0.0-beta.2 or later, Pro edition
- PHP 8.5 or later
- The plugin doesn't need the Yii 2 adapter (`craftcms/yii2-adapter`).

## Upgrade steps

1. Upgrade your project to Craft 6, and Imager X to version 7.
2. Update the plugin: `composer require spacecatninja/imager-x-imageboss-transformer:^4.0.0`.
3. Move your config file to `config/craft/imager-x-imageboss-transformer.php`, and convert it (see below).
4. Review the breaking changes below, and update your code.

## Breaking changes

### The config file

Craft 6 reads plugin config files from `config/craft/`, as plain Laravel config. Move `config/imager-x-imageboss-transformer.php` to `config/craft/imager-x-imageboss-transformer.php`. If it uses the multi-environment `'*'` format, return a flat array instead, and use `env()` instead of `craft\helpers\App::env()`. The settings themselves are unchanged.

Before:

```php
use craft\helpers\App;

return [
    '*' => [
        'defaultProfile' => 'default',
        'profiles' => [
            'default' => [
                'sourceName' => 'my-source',
                'signToken' => App::env('IMAGEBOSS_SIGN_TOKEN'),
                'useCloudSourcePath' => true,
            ],
        ],
    ],
];
```

After:

```php
return [
    'defaultProfile' => 'default',
    'profiles' => [
        'default' => [
            'sourceName' => 'my-source',
            'signToken' => env('IMAGEBOSS_SIGN_TOKEN', ''),
            'useCloudSourcePath' => true,
        ],
    ],
];
```

Config files are loaded before Laravel knows the environment, so use `env()` for per-environment values, not `app()->environment()`. For example: `'sourceName' => env('APP_ENV') === 'production' ? 'my-source' : 'my-staging-source'`.

### The cloud source path

With a profile's `useCloudSourcePath` setting on, the path of an asset in the ImageBoss source starts with its cloud storage path. On Craft 5 that was the filesystem's subfolder. Craft 6 volumes use Laravel filesystem disks, so it's now the `root` of the volume's disk. The volume's subpath is still always added after it. If your S3 or GCS disk's `root` matches the subfolder your Craft 5 filesystem had, the URLs are unchanged.

### Transform URLs

Some transform URLs change:

- Modes `stretch` and `croponly` now crop like `crop`. Version 3 left out the operation, which ImageBoss requires.
- A transform without a `width` or `height`, for instance one that only sets `format`, now uses the `cdn` operation, for the same reason.
- A transform with `format` set to `webp`, `avif` or `png` now gets its quality from `webpQuality`, `avifQuality` or `pngCompressionLevel`.
- When `allowUpscale` is off:
  - Mode `fit` and transforms with only a `width` or `height` now get ImageBoss' `withoutEnlargement:true` option.
  - A crop larger than the source image is now made smaller, with the same ratio, like Imager X's `craft` transformer does. For example, `width: 3000, height: 1000` of a 2400x1600 image gives a 2400x800 image instead of an enlarged 3000x1000 one.

The size the transformer reports for mode `fit` with `allowUpscale` on is now the enlarged size ImageBoss gives, not the source image's size.

### Namespace and classes

The plugin moved from `spacecatninja\imagebosstransformer` to the `SpaceCatNinja\ImageBossTransformer` namespace, with Laravel-style folders.

| Before | After |
| --- | --- |
| `spacecatninja\imagebosstransformer\ImageBossTransformer` | `SpaceCatNinja\ImageBossTransformer\ImageBossTransformer` |
| `spacecatninja\imagebosstransformer\models\Settings` | `SpaceCatNinja\ImageBossTransformer\Settings` |
| `spacecatninja\imagebosstransformer\models\ImageBossProfile` | `SpaceCatNinja\ImageBossTransformer\Data\ImageBossProfile` |
| `spacecatninja\imagebosstransformer\models\ImageBossTransformedImageModel` | `SpaceCatNinja\ImageBossTransformer\Data\ImageBossTransformedImageModel` |
| `spacecatninja\imagebosstransformer\helpers\ImageBossHelpers` | `SpaceCatNinja\ImageBossTransformer\Support\ImageBossHelpers` |
| `spacecatninja\imagebosstransformer\transformers\ImageBoss` | `SpaceCatNinja\ImageBossTransformer\Transformers\ImageBoss` |

`ImageBossTransformedImageModel` extends Imager X 7's `SpaceCatNinja\ImagerX\Data\BaseTransformedImageModel`, and `ImageBossProfile` extends Craft 6's `CraftCms\Cms\Component\Component`. The `ImageBoss` transformer no longer extends `craft\base\Component`, and the classes throw Imager X 7's `SpaceCatNinja\ImagerX\Exceptions\ImagerException`.

### Settings

`ImageBossTransformer::$plugin` was removed. Read the plugin settings with `app(SpaceCatNinja\ImageBossTransformer\Settings::class)` instead of `ImageBossTransformer::$plugin->getSettings()`.
