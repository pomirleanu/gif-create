# gif-create

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]


Easy way to create gif animations from png images for laravel 5.*.

## Install

Via Composer

``` bash
$ composer require pomirleanu/gif-create
```

### Usage basic php

**1. Inputs:**

```php
// Use an array containing file paths, resource vars (initialized with imagecreatefromXXX), 
// image URLs or binary image data.
$frames = array(
    imagecreatefrompng("/../images/pic1.png"),      // resource var
    "/../images/pic2.png",                          // image file path
    file_get_contents("/../images/pic3.jpg"),       // image binary data
    "http://thisisafakedomain.com/images/pic4.jpg", // URL
);

// Or: load images from a dir (sorted, skipping .files):
//$frames = "../images";

// Optionally: set different durations (in 1/100s units) for each frame
$durations = array(20, 30, 10, 10);

// Or: you can leave off repeated values from the end:
//$durations = array(20, 30, 10); // use 10 for the rest
// Or: use 'null' anywhere to re-apply the previous delay:
//$durations = array(250, null, null, 500);
```

**2. Create the GIF:**

``` php
use Pomirleanu\GifCreate;

// ...

$gif = new GifCreate\GifCreate();
$gif->create($frames, $durations);

// Or: using the default 100ms even delay:
//$gif->create($frames);

// Or: loop 5 times, then stop:
//$gif->create($frames, $durations, 5); // default: infinite looping
```

**3. Get/use the result:**

You can now get the animated GIF binary:

```php
$gif = $gif->get();
```

...and e.g. send it directly to the browser:

```php
header("Content-type: image/gif");
echo $gif;
exit;
```

Or just save it to a file:

```php
$gif->save("animated.gif");
```


### Usage in laravel 5.*
Service provider should be :

```php
Pomirleanu\GifCreate\GifCreateServiceProvider::class,
```
Publish needed assets (config file) :

```bash
php artisan vendor:publish --provider="Pomirleanu\GifCreate\GifCreateServiceProvider"
```
*Note:* Composer won't update them after `composer update`, you'll need to do it manually!



### Behavior

- Transparency is based on the first frame. [!!NOT VERIFIED: "It will be saved only if you give multiple frames with the same transparent background"]
- The dimensions of the generated GIF are based on the first frame, too. If you need to resize your images to get the same dimensions, you can use this class: https://github.com/Sybio/ImageWorkshop.


### Dependencies

* PHP 5.3 (for namespace support & whatnot; noone still shamelessly uses PHP < 5.3, right?!)
* GD (`imagecreatefromstring`, `imagegif`, `imagecolortransparent`)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email pomirleanu.florentin@gmail.com instead of using the issue tracker.

## Credits

- [Pomirleanu Florentin Cristinel][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/pomirleanu/gif-create.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/pomirleanu/gif-create/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/pomirleanu/gif-create.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/pomirleanu/gif-create.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/pomirleanu/gif-create.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/pomirleanu/gif-create
[link-travis]: https://travis-ci.org/pomirleanu/gif-create
[link-scrutinizer]: https://scrutinizer-ci.com/g/pomirleanu/gif-create/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/pomirleanu/gif-create
[link-downloads]: https://packagist.org/packages/pomirleanu/gif-create
[link-author]: https://github.com/pomirleanu
[link-contributors]: ../../contributors
