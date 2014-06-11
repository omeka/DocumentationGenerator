#!/usr/bin/env php
<?php
//require __DIR__ . '/vendor/autoload.php';
require '/var/www/DocumentationGenerator/vendor/autoload.php';
define('SPHPDOX_DIR', '/var/www/DocumentationGenerator/vendor/sphpdox/sphpdox/');
use Sphpdox\Process;
use Symfony\Component\Console\Shell;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;

$application = new Application('sphpdoc', '1.0.0-alpha');
$application->add(new Process());
$application->run();
