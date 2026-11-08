<?php

namespace Tests\Mysql;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MysqlTestCase;
use Tests\Support\AdminModuleScenarios;

class AdminModuleTest extends MysqlTestCase
{
    use AdminModuleScenarios, DatabaseTransactions;
}
