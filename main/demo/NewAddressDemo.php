<?php
require('../vendor/autoload.php');

use TronTool\Credential;

echo 'create a new address...' . PHP_EOL;
echo "</br>";
$credential = Credential::create();
echo 'private key => ' . $credential->privateKey() . PHP_EOL;
echo "</br>";
echo 'public key => ' . $credential->publicKey() . PHP_EOL;
echo "</br>";
echo 'address => ' . $credential->address() . PHP_EOL;
echo "</br>";
echo 'import an existing private key...' . PHP_EOL;
echo "</br>";
$credential = Credential::fromPrivateKey('***');
echo 'private key => ' . $credential->privateKey() . PHP_EOL;
echo "</br>";
echo 'public key => ' . $credential->publicKey() . PHP_EOL;
echo "</br>";
echo 'address => ' . $credential->address() . PHP_EOL;
echo "</br>";
