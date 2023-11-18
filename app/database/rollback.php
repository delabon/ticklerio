#!/usr/local/bin/php
<?php

/**
 * Runs all migration scripts
 */

use App\Core\Migration\Migration;

$container = require_once __DIR__ . '/../src/bootstrap.php';

$migration = new Migration(
    $container->get(PDO::class),
    __DIR__ . '/migrations/'
);
$migration->rollback();
