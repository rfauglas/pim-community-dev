<?php

declare(strict_types=1);

namespace Pim\Upgrade\Schema\Tests;

use Akeneo\Test\Integration\TestCase;

final class Version_6_0_20210819080024_add_warning_count_in_step_execution_Integration extends TestCase
{
    use ExecuteMigrationTrait;

    private const MIGRATION_LABEL = '_6_0_20210819080024_add_warning_count_in_step_execution';

    protected function getConfiguration()
    {
        return $this->catalog->useMinimalCatalog();
    }

    public function test_it_adds_warning_count_column_in_step_execution_table()
    {
        $dbConnection = $this->get('database_connection');

        $dbConnection->executeQuery(
            "ALTER TABLE akeneo_batch_step_execution DROP COLUMN warning_count;"
        );

        $this->reExecuteMigration(self::MIGRATION_LABEL);

        $this->assertWarningCountColumnExists();
    }

    private function assertWarningCountColumnExists(): void
    {
        $columns = $this->get('database_connection')
            ->getSchemaManager()
            ->listTableColumns('akeneo_batch_step_execution');

        $this->assertArrayHasKey('warning_count', $columns);
    }
}
