# ImageBoss transformer for Imager X

A plugin for using [ImageBoss](https://imageboss.me/) as a transformer in Imager X.   
Also, an example of [how to make a custom transformer for Imager X](https://imager-x.spacecat.ninja/extending.html#transformers).

## Requirements

This plugin requires Craft CMS 6.0.0 or later, [Imager X 7.0.0](https://github.com/spacecatninja/craft-imager-x/) or later
(the Pro edition, like all custom transformers), PHP 8.5 or later, and an account at [ImageBoss](https://imageboss.me/).

Version 3.x of this plugin supports Craft CMS 5 and Imager X 5 and 6. See [UPGRADE.md](UPGRADE.md) when moving to Craft 6.
 
## Usage

Install and configure this transformer as described below. Then, in your [Imager X config](https://imager-x.spacecat.ninja/configuration.html), 
`config/craft/imager-x.php`, set the transformer to `imageboss`, ie:

```
'transformer' => 'imageboss',
``` 

Transforms are now by default transformed with ImageBoss, test your configuration with a 
simple transform like this:

```
{% set transform = craft.imagerx.transformImage(asset, { width: 600 }) %}
<img src="{{ transform.url }}" width="600">
<p>URL is: {{ transform.url }}</p>
``` 

If this doesn't work, make sure you've configured a `defaultProfile`, have a profile with the correct name, and 
that the source is set up to use the same buckets that you assets are on.

### Cave-ats, shortcomings, and tips

This transformer only supports a subset of what Imager X can do when using the default `craft` transformer. 
All the basic transform parameters are supported, with the following exceptions:

- Only assets can be transformed. If you need to transform external images, you need to switch to the `craft` transformer for those transforms.
- The `croponly` and `stretch` resize modes are not supported, and crop the image like `crop`.
- `allowUpscale` only applies to transforms with only a `width` or `height`, which get ImageBoss' `withoutEnlargement`
  option when it's off.
- Opacity for colors on `letterbox` resize mode is not supported.
- Only the following `effects` are converted and supported: `grayscale`, `sharpen`, `blur` and `gamma`.
– Watermarks are not translated automatically from Imager syntax to ImageBoss', but you can still add watermarks by manually passing them through the `options` object (see below).

To pass additional options directly to ImageBoss, you can use the `transformerParams` transform parameter and pass them in using an `options` object. Example:

```
{% set transforms = craft.imagerx.transformImage(asset, 
    [{width: 400}, {width: 600}, {width: 800}], 
    { ratio: 2/1, transformerParams: { options: 'sharpen:6,grayscale:true' } }
) %}
```   

For more information, check out the [ImageBoss documentation](https://imageboss.me/docs).


## Installation

To install the plugin, follow these instructions:

1. Install with composer via `composer require spacecatninja/imager-x-imageboss-transformer` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings > Plugins, or from the command line via `php artisan craft:plugin:install imager-x-imageboss-transformer`.


## Configuration

You can configure the transformer by creating a file called `imager-x-imageboss-transformer.php` in your 
`config/craft/` folder, and override settings as needed. It's a plain Laravel config file, so use `env()` for 
sign tokens and other environment-specific values.

### profiles [array]
Default: `[]`  
Profiles are usually a one-to-one mapping to the [image sources](https://imageboss.me/docs#image-sources) you've created in ImageBoss.
Which in turn will often map to a Volume in your Craft setup. You set the default profile to use using the `defaultProfile` config
setting, and can override it at the template level by setting `profile` in your `transformerParams`.

Example profile:

```
'profiles' => [
    'default' => [
        'sourceName' => 'imagerx-s3',
        'signToken' => env('IMAGEBOSS_SIGN_TOKEN', ''),
        'useCloudSourcePath' => true,
    ],
    'web' => [
        'sourceName' => 'imagerx-web',
        'signToken' => env('IMAGEBOSS_WEB_SIGN_TOKEN', ''),
        'useCloudSourcePath' => false,
    ]
],
```

Each profile takes three settings:

*sourceName*: This is the "Source Name" you selected in ImageBoss.

*signToken*: If you've enabled signed URL's for security, add your sign token here. 

*useCloudSourcePath*: If enabled, Imager will prepend the `root` of the volume's filesystem disk to the asset path, before 
adding it to the ImageBoss URL. This makes it possible to have one ImageBoss source pulling images from many Craft volumes when 
they are for instance on the same S3 bucket, but in different folders. This only applies to cloud disks (for instance S3 and 
GCS), not local disks. The volume's subpath is always added.

### defaultProfile [string]
Default: `''`  
Sets the default profile to use (see `profiles`). You can override profile at the transform level by setting it through the `transformerParams` transform parameter. Example:

```
{% set transforms = craft.imagerx.transformImage(asset, 
    [{width: 800}, {width: 2000}], 
    { transformerParams: { profile: 'myotherprofile' } }
) %}
```

### enableCompression [bool]
Default: `true`  
Set to `false`to disable ImageBoss' [default auto compression](https://imageboss.me/docs/image-compression) that delivers WebP automatically when the browser supports it. 

### enableProgressive [bool]
Default: `true`  
Set to `false`to disable ImageBoss' [default behavior](https://imageboss.me/docs/progressive-images) that delivers progressive JPEG's when possible. 

### enableAutoRotate [bool]
Default: `true`  
Set to `false`to disable ImageBoss' [default behavior](https://imageboss.me/docs/auto-rotate) that uses EXIF data in the image to auto rotate it. 


Price, license and support
---
The plugin is released under the MIT license. It requires Imager X, which is a commercial 
plugin [available in the Craft plugin store](https://plugins.craftcms.com/imager-x). If you 
need help, or found a bug, please post an issue in this repo, or in Imager X' repo (preferably). 
