[![Latest Stable Version](https://poser.pugx.org/rmanara/brutalforce/v/stable)](https://packagist.org/packages/rmanara/brutalforce)
[![Total Downloads](https://poser.pugx.org/rmanara/brutalforce/downloads)](https://packagist.org/packages/rmanara/brutalforce)
[![License](https://poser.pugx.org/rmanara/brutalforce/license)](https://packagist.org/packages/rmanara/brutalforce)
[![composer.lock](https://poser.pugx.org/rmanara/brutalforce/composerlock)](https://packagist.org/packages/rmanara/brutalforce)
[![Build Status](https://travis-ci.org/rodrigomanara/brutalforce.svg?branch=master)](https://travis-ci.org/rodrigomanara/brutalforce)

# brutalforce

* Repository: https://github.com/rodrigomanara/brutalforce
* Version: 2.0.0

    composer require rmanara/brutalforce

Brute-Force method is used from many hackers but the <b>brutalforce</b> will 
help you void this problem by check  the client IP and will count how many 
request in less than a 1 seconds, it will set a session and will reset after one minute

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
$form = $init->getScript();
//display this google link on the header
$header = $init->getUrl();
//this check needs to be done after the submit
$allowed = $init->run();

