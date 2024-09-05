<?php
require('phpqrcode.php');
$t = $_GET['text'];
QRcode::png($t);