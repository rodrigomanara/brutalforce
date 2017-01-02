<?php

require_once './vendor/autoload.php';

for ($i = 0; $i < rand(0, 9999999); $i++) {

    $firewall = new BrutalForce\Firewall\Firewall(__DIR__);
    $firewall->inicializer(BrutalForce\Firewall\Firewall::TYPE_FILE);
}