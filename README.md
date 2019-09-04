phpMorphy
===================

[![Build Status](https://travis-ci.org/aleksender/phpmorphy.svg?branch=master)](https://travis-ci.org/aleksender/phpmorphy)
[![GitHub tag](https://img.shields.io/github/tag/aleksender/phpmorphy.svg?label=latest)](https://packagist.org/packages/aleksender/php-morphy)

phpMorphy is morphological analyzer library for Russian, English and German languages.

 * [Website (in Russian)](http://phpmorphy.sourceforge.net/)
 * [Sourceforge project](http://sourceforge.net/projects/phpmorphy)


This library allows to retrieve following morph information for any word:
 * base (normal) form;
 * all forms;
 * grammatical (part of speech, grammems) information.

## Installation

To install the library in your project using `Composer`, first add the following to your `composer.json`
config file:
```javascript
{
    "require": {
        "aleksender/phpmorphy": "~1.0"
    }
}
```
Then run Composer's install or update commands to complete installation.

## Usage

See examples in [examples](examples) directory.        