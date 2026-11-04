<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase;
use Tests\Support\IsolatedMysql;

abstract class MysqlTestCase extends TestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        IsolatedMysql::verify($app);

        return $app;
    }
}
