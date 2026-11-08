<?php

namespace Tests\Mysql;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MysqlTestCase;
use Tests\Support\StudentModuleScenarios;

class StudentModuleTest extends MysqlTestCase
{
    use DatabaseTransactions, StudentModuleScenarios;
}
