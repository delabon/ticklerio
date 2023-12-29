#!/usr/local/bin/php
<?php

/**
 * Runs all migration scripts
 */

use App\Core\Migration\Migration;
use App\Core\Utilities\ClassNameConverter;

$container = require_once __DIR__ . '/../src/bootstrap.php';

$migration = new Migration(
    $container->get(PDO::class),
    new ClassNameConverter(),
    __DIR__ . '/migrations/'
);
$migration->rollback();
