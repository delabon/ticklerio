<?php

namespace App\Core\Interfaces;

interface MigrationInterface
{
    public function up(): void;

    public function down(): void;
}
