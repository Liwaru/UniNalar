<?php

namespace Tests\HostingMysql;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MysqlTestCase;
use Tests\Support\HostingProvisioningScenarios;

/** Run on a freshly migrated isolated QA database BEFORE other committed fixtures. */
class HostingProvisioningTest extends MysqlTestCase
{
    use DatabaseTransactions, HostingProvisioningScenarios;
}
