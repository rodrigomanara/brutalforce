[![Latest Stable Version](https://poser.pugx.org/rmanara/brutalforce/v/stable)](https://packagist.org/packages/rmanara/brutalforce)
[![Total Downloads](https://poser.pugx.org/rmanara/brutalforce/downloads)](https://packagist.org/packages/rmanara/brutalforce)
[![License](https://poser.pugx.org/rmanara/brutalforce/license)](https://packagist.org/packages/rmanara/brutalforce)
[![composer.lock](https://poser.pugx.org/rmanara/brutalforce/composerlock)](https://packagist.org/packages/rmanara/brutalforce)
[![Build Status](https://travis-ci.org/rodrigomanara/brutalforce.svg?branch=master)](https://travis-ci.org/rodrigomanara/brutalforce)

# brutalforce
Will check  the client IP and will count how many request in less than a 2 seconds how many request was done.
Automaticly a file will be create and saved that ip and will save for futures request.

It is very simple setup and can be used in any frameworks.

### very simple setup
`
 $firewall = new BrutalForce\Firewall\Firewall(__DIR__);
`

`
 $firewall->initializer(BrutalForce\Firewall\Firewall::TYPE_FILE);

    if ($firewall->isLocked()) {
        echo "wait 10 minutes" . PHP_EOL;
    } else {
        echo "free to go". PHP_EOL;
}
`
