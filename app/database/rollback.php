#!/usr/local/bin/php
<?php

/**
 * Runs all migration scripts
 */

use App\Core\App;
use App\Core\Migration\Migration;

require_once __DIR__ . '/../src/bootstrap.php';

$migration = new Migration(
    (App::getInstance())->pdo(),
    __DIR__ . '/migrations/'
);
$migration->rollback();
