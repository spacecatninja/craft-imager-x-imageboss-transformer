# ImageBoss transformer for Imager X Changelog

## 4.0.0 - Unreleased

> [!IMPORTANT]
> Version 4 requires Craft CMS 6, Imager X 7 and PHP 8.5, the config file moves to `config/craft/`, and there are breaking changes for code that uses the plugin's classes. See [UPGRADE.md](UPGRADE.md).

### Added
- Added the `addPath` profile setting, which prepends a path to the asset's path, or a path per volume handle.

### Changed
- The plugin now requires Craft CMS 6, Imager X 7.0.0-beta.2 or later, and PHP 8.5, and uses Craft 6's native plugin APIs. It doesn't need the Yii 2 adapter.
- The config file is now `config/craft/imager-x-imageboss-transformer.php`, and is a plain Laravel config file.
- Classes moved to the `SpaceCatNinja\ImageBossTransformer` namespace, and to Laravel-style folders.
- The transformer is now registered with Imager X's `Transformers` registry.
- With `useCloudSourcePath`, the cloud source path is now the `root` of the volume's filesystem disk. Version 3 used the filesystem's subfolder. The volume's subpath is still always added.
- `ImageBossTransformedImageModel`'s constructor takes an optional `$upscale` argument.

### Removed
- Removed the `ImageBossTransformer::$plugin` property.

### Fixed
- Fixed modes `stretch` and `croponly`, and transforms without a `width` or `height`, giving URLs without an operation, which ImageBoss requires. `stretch` and `croponly` now crop like `crop`, and a transform without a size uses the `cdn` operation.
- Fixed `webpQuality`, `avifQuality` and `pngCompressionLevel` being ignored when a transform set `format`. The quality came from the source image's extension.
- Fixed `allowUpscale` being ignored. When it's off, mode `fit` and transforms with only a `width` or `height` get ImageBoss' `withoutEnlargement` option, and crops larger than the source image are made smaller, with the same ratio.
- Fixed the reported size of `fit` transforms larger than the source image. ImageBoss enlarges them, but the transformer reported the source image's size.
- Fixed transforming a path or URL throwing a `TypeError`. It now throws an `ImagerException`, since only assets can be transformed.


## 3.0.1 - 2024-06-16

### Fixed
- Fixed an issue that would throw an error if width or height for source image was `null` (fixes #1)

## 3.0.0 - 2024-02-10

### Added
- Added support for Craft 5


## 2.0.0 - 2022-05-03

### Added
- Added support for Craft 4


## 1.0.0 - 2021-08-22

### Added
- Initial release
