<?php

namespace App\Core\Migration;

interface MigrationInterface
{
    public function up(): void;

    public function down(): void;
}
