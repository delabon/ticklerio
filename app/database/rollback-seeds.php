#!/usr/local/bin/php
<?php

/**
 * Runs all seeder scripts
 */

use App\Core\Seeding\Seeder;
use App\Core\Utilities\ClassNameConverter;

$container = require_once __DIR__ . '/../src/bootstrap.php';

$migration = new Seeder(
    $container->get(PDO::class),
    new ClassNameConverter(),
    __DIR__ . '/seeders/'
);
$migration->rollback();
