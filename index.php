<?php

require_once './vendor/autoload.php';

for ($i = 0; $i < rand(0,9999999); $i++) {

$firewall = new BrutalForce\Firewall\Firewall(__DIR__);

    if ($firewall->isLocked()) {
        echo "sorry you will need to wait" . PHP_EOL;
    } else {
        echo "free to go" . PHP_EOL;
    }
}