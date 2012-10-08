#!/usr/bin/env php
<?php

if (is_readable('/var/www/html/sphpdox/vendor/autoload.php')) {
    require '/var/www/html/sphpdox/vendor/autoload.php';

}

use Sphpdox\Process;
use Symfony\Component\Console\Shell;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;


$application = new Application('sphpdoc', '1.0.0-alpha');
$application->add(new Process());
$application->run();

// */