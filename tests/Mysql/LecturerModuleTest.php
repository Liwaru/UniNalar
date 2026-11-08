<?php

namespace Tests\Mysql;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MysqlTestCase;
use Tests\Support\LecturerModuleScenarios;

class LecturerModuleTest extends MysqlTestCase
{
    use DatabaseTransactions, LecturerModuleScenarios;
}
