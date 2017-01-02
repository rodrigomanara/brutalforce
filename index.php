<?php

require_once './vendor/autoload.php';

for ($i = 0; $i < rand(0, 5); $i++) {

    $firewall = new BrutalForce\Firewall\Firewall(__DIR__);
    $firewall->inicializer(BrutalForce\Firewall\Firewall::TYPE_FILE);

    if ($firewall->isLocked()) {
        echo "wait 10 minutes" . PHP_EOL;
    } else {
        echo "free to go". PHP_EOL;
    }
}