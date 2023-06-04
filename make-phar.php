<?php

if (file_exists(__DIR__ . "/CloudBridge.phar")) unlink(__DIR__ . "/CloudBridge.phar");

$phar = new Phar(__DIR__ . "/CloudBridge.phar", 0, "CloudBridge.phar");
$phar->buildFromDirectory(__DIR__ . "/", "/\.php$/");
if (isset($phar["make-phar.php"])) unset($phar["make-phar.php"]);
if (isset($phar["make-phar.bat"])) unset($phar["make-phar.bat"]);
$phar->compressFiles(Phar::GZ);