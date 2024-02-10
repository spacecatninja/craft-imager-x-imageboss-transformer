# ImageBoss transformer for Imager X

A plugin for using [ImageBoss](https://imageboss.me/) as a transformer in Imager X.   
Also, an example of [how to make a custom transformer for Imager X](https://imager-x.spacecat.ninja/extending.html#transformers).

## Requirements

This plugin requires Craft CMS 5.0.0-beta.1 or later, [Imager X 5.0.0-beta.1](https://github.com/spacecatninja/craft-imager-x/) or later,
and an account at [ImageBoss](https://imageboss.me/).
 
## Usage

Install and configure this transformer as described below. Then, in your [Imager X config](https://imager-x.spacecat.ninja/configuration.html), 
set the transformer to `imageboss`, ie:

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
- The `cropOnly` and `stretch` resize modes are not supported.
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
2. Install the plugin in the Craft Control Panel under Settings > Plugins, or from the command line via `./craft plugin/install imager-x-imageboss-transformer`.


## Configuration

You can configure the transformer by creating a file in your config folder called
`imager-x-imageboss-transformer.php`, and override settings as needed.

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
        'signToken' => '7a7cc5142212378b435edb18b273bec8799e1270272cc49f34836651cd023a28',
        'useCloudSourcePath' => true,
    ],
    'web' => [
        'sourceName' => 'imagerx-web',
        'signToken' => '1b7ca41421125791a31edb18a273bec8792e1970272ad49f34836631cd023a28',
        'useCloudSourcePath' => false,
    ]
],
```

Each profile takes three settings:

*sourceName*: This is the "Source Name" you selected in ImageBoss.

*signToken*: If you've enabled signed URL's for security, add your sign token here. 

*useCloudSourcePath*: If enabled, Imager will prepend the Craft source path to the asset path, before adding it to the 
ImageBoss URL. This makes it possible to have one ImageBoss source pulling images from many Craft volumes when they are for instance 
on the same S3 bucket, but in different subfolder. This only works on volumes that implements a path 
setting (AWS S3 and GCS does, local volumes does not).

### defaultProfile [string]
Default: `''`  
Sets the default profile to use (see `profiles`). You can override profile at the transform level by setting it through the `transformParams` transform parameter. Example:

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
