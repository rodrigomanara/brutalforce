[![Latest Stable Version](https://poser.pugx.org/rmanara/brutalforce/v/stable)](https://packagist.org/packages/rmanara/brutalforce)
[![Total Downloads](https://poser.pugx.org/rmanara/brutalforce/downloads)](https://packagist.org/packages/rmanara/brutalforce)
[![License](https://poser.pugx.org/rmanara/brutalforce/license)](https://packagist.org/packages/rmanara/brutalforce)
[![composer.lock](https://poser.pugx.org/rmanara/brutalforce/composerlock)](https://packagist.org/packages/rmanara/brutalforce)
[![Build Status](https://travis-ci.org/rodrigomanara/brutalforce.svg?branch=master)](https://travis-ci.org/rodrigomanara/brutalforce)

# brutalforce

* Repository: https://github.com/rodrigomanara/brutalforce
* Version: 1.0.5

Brute-Force method is used from many hackers but the <b>brutalforce</b> will help you void this problem by check  the client IP and will count how many request in less than a 2 seconds how many request was done.
Automaticly a file will be create and saved that ip and will save for futures request.

It is very simple setup and can be used in any frameworks.

### very simple setup
```php
<?php
$firewall = new BrutalForce\Firewall\Firewall(__DIR__, "sitekey", "secret");
```
## specify type of handler
```php
<?php
 $firewall->initializer(BrutalForce\Firewall\Firewall::TYPE_FILE);
```
##check if the firewall is locked
```php
<?php
if ($firewall->isLocked()) {
    // here you check the recaptcha is already able to display
    if ($firewall->verify()->recaptcha['valid'] == false) {
        echo "<form method='post' action=''>";
        // diplay message 
        echo $firewall->verify()->recaptcha['form_message'];
        // show input
        echo $firewall->verify()->recaptcha['form'];
        
        echo "<button>send</button></form>";
    } else {
        echo $firewall->verify()->recaptcha['form_message']; PHP_EOL;
    }
} else {
    echo "free to go" . PHP_EOL;
}
```
### Reset the lock completely to original
by doing this the cound down will start from zero and the recaptcha will be called again from the last 3 counts

```php 

$firewall->resetLock(true);

```

Bugs:

* found issue on setup the file and folder to holder the ip

enhancement:

* add new method to reset lock
* remove setup file on handler
* add request uri so the full url is recored and can be redirect to it if is need to

