<?php

namespace Tests\Mysql;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MysqlTestCase;
use Tests\Support\OwnerModuleScenarios;

class OwnerModuleTest extends MysqlTestCase
{
    use DatabaseTransactions, OwnerModuleScenarios;
}
