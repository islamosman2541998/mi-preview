<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseConfigTest extends TestCase
{
    /**
     * The shared host's MySQL defaults to MyISAM: 1000-byte keys (utf8mb4 varchar(255) primary
     * keys fail) and no transactions. Tables must be created as InnoDB.
     */
    #[Test]
    public function mysql_tables_are_created_as_innodb(): void
    {
        $this->assertSame('InnoDB', config('database.connections.mysql.engine'));
        $this->assertSame('InnoDB', config('database.connections.mariadb.engine'));
    }
}
