[![Latest Stable Version](https://poser.pugx.org/rmanara/brutalforce/v/stable)](https://packagist.org/packages/rmanara/brutalforce)
[![Total Downloads](https://poser.pugx.org/rmanara/brutalforce/downloads)](https://packagist.org/packages/rmanara/brutalforce)
[![License](https://poser.pugx.org/rmanara/brutalforce/license)](https://packagist.org/packages/rmanara/brutalforce)
[![composer.lock](https://poser.pugx.org/rmanara/brutalforce/composerlock)](https://packagist.org/packages/rmanara/brutalforce)
[![Build Status](https://travis-ci.org/rodrigomanara/brutalforce.svg?branch=master)](https://travis-ci.org/rodrigomanara/brutalforce)

# brutalforce

* Repository: https://github.com/rodrigomanara/brutalforce
* Version: 2.0.0

    composer require rmanara/brutalforce

Brute-Force method is used from many hackers but the <b>brutalforce</b> will help you void this problem by check  the client IP and will count how many request in less than a 2 seconds how many request was done.
Automaticly a file will be create and saved that ip and will save for futures request.

It is very simple setup and can be used in any frameworks.

### very simple setup
```php
<?php
//
require_once './vendor/autoload.php';
//
use BrutalForce\Initiate;
//
$site_key = "";
$secret = "";
//
$init = new Initiate($site_key, $secret);
//display on the footer after the jQuery url been loaded
$form = $init->getScript($site_key);
//display this google link on the header
$header = $init->getUrl($site_key);
//this check needs to be done after the submit
$allowed = $init->run();

