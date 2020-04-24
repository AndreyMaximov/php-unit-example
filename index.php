<?php
/**
 * @file
 * Entry point.
 */
use App\App;

define('DOCROOT', __DIR__);

require DOCROOT . '/vendor/autoload.php';

$app = new App();
$app->run();
