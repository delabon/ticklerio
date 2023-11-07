<?php

// phpcs:ignoreFile

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn(
            'email',
            MysqlAdapter::PHINX_TYPE_STRING
        );
        $table->addColumn(
            'first_name',
            MysqlAdapter::PHINX_TYPE_STRING
        );
        $table->addColumn(
            'last_name',
            MysqlAdapter::PHINX_TYPE_STRING
        );
        $table->addColumn(
            'password',
            MysqlAdapter::PHINX_TYPE_STRING
        );
        $table->addColumn(
            'created_at',
            MysqlAdapter::PHINX_TYPE_BIG_INTEGER,
            [
                'default' => 0,
                'signed' => false,
            ]
        );
        $table->addColumn(
            'updated_at',
            MysqlAdapter::PHINX_TYPE_BIG_INTEGER,
            [
                'default' => 0,
                'signed' => false,
            ]
        );
        $table->save();
    }
}
