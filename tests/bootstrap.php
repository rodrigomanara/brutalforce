<?php

require __DIR__ . '/../vendor/autoload.php'; 

ini_set('precision', 14);
ini_set('serialize_precision', 14);

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

$_SERVER['REMOTE_ADDR'] = '192.168.0.1';