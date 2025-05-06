<?php

namespace Xima\XimaDeployerTools\Database\Manager;

use function Deployer\debug;
use function Deployer\get;
use function Deployer\set;
use function Deployer\has;
use function Deployer\run;
use function Deployer\input;
use function Deployer\upload;
use function Deployer\runExtended;
use function Deployer\test;

/**
 * Database Management "Simple"
 *
 * This manager supports the database management via a simple user with limited privileges. The idea behind this is to use a fixed pool of existing databases.
 */
class Simple extends AbstractManager implements ManagerInterface
{
    public function create(): void
    {
        debug('Creating database');
        $this->ensureDatabasePoolExists();

        if (!$this->hasFreeAssignments() && $this->getAssignment($this->getFeatureName()) === null) {
            throw new \RuntimeException('No free databases available. Check your pool or cleanup unused assignments.');
        }

        $database = $this->getAssignment($this->getFeatureName()) ?: $this->getFreeAssignment();
        $databaseConfiguration = $this->getDatabaseConfiguration($database);
        $this->checkAssignmentConfiguration($databaseConfiguration);
        $this->updateAssignment($database, $this->getFeatureName());
        $this->initDatabaseConfiguration($database);
    }

    public function delete(string $feature): void
    {
        debug('Deleting database');
        $this->ensureDatabasePoolExists();
        $this->removeAssignment($feature);

        $this->initDatabaseConfiguration(feature: $feature);
        $this->run($this->generateDropTablesQuery($this->getDatabaseName($feature)));
    }

    public function getDatabaseName(?string $feature = null): string
    {
        $feature = $feature ?: input()->getOption('feature');
        $databaseAssignment = $this->getAssignment($feature);

        if (!$databaseAssignment) {
            return '';
        }
        $databaseName = $this->getDatabaseConfiguration($databaseAssignment)['database_name'] ?? '';
        return $databaseName;
    }

    private function readAssignment(): array
    {
        $filePath = get('deploy_base_path') . '/' . get('feature_directory_path') . '/database_assignments.json';
        return test("[ -f $filePath ]") ? \json_decode(runExtended("cat $filePath"), true) ?: [] : [];
    }

    private function updateAssignment(string $database, string $feature): void
    {
        $assignments = $this->readAssignment();
        $assignments[$feature] = $database;
        $this->writeAssignments($assignments);
    }

    private function getAssignment(string $feature): ?string
    {
        return $this->readAssignment()[$feature] ?? null;
    }

    private function removeAssignment(string $feature): void
    {
        $assignments = $this->readAssignment();
        unset($assignments[$feature]);
        $this->writeAssignments($assignments);
    }

    private function getFreeAssignment(): ?string
    {
        $used = array_values($this->readAssignment());
        $pool = get('database_pool');
        $free = array_diff(array_keys($pool), $used);
        return $free ? array_shift($free) : null;
    }

    private function hasFreeAssignments(): bool
    {
        return !empty(array_diff(array_keys(get('database_pool')), array_values($this->readAssignment())));
    }

    private function getDatabaseConfiguration(string $database): array
    {
        $pool = get('database_pool');
        if (!isset($pool[$database])) {
            throw new \RuntimeException(sprintf('Database "%s" not found in pool.', $database));
        }

        return $pool[$database];
    }

    private function initDatabaseConfiguration(?string $database = null, ?string $feature = null): void
    {
        $pool = get('database_pool');
        if (!$database) {
            $database = $this->getAssignment($feature ?: $this->getFeatureName());
        }

        if (!isset($pool[$database])) {
            throw new \RuntimeException(sprintf('Database "%s" not found in pool.', $database));
        }

        $config = $pool[$database];
        foreach ($config as $key => $value) {
            if (str_starts_with($key, 'DEPLOYER_CONFIG_')) {
                set($key, getenv($value) ?: $value);
                continue;
            }
            set($key, $value);
        }
    }

    private function checkAssignmentConfiguration(array $assignment): void
    {
        $requiredKeys = ['database_user', 'database_password', 'database_name'];
        $isValid = !array_diff_key(array_flip($requiredKeys), array_filter($assignment));

        if (!$isValid) {
            throw new \RuntimeException(sprintf(
                'Invalid database assignment configuration. Required keys: %s',
                implode(', ', $requiredKeys)
            ));
        }
    }

    private function ensureDatabasePoolExists(): void
    {
        if (!has('database_pool')) {
            throw new \RuntimeException('Database pool is not defined. Set "database_pool" in your configuration.');
        }
    }

    private function writeAssignments(array $assignments): void
    {
        $filePath = get('deploy_base_path') . '/' . get('feature_directory_path') . '/database_assignments.json';
        $tempFile = '.deployer.database_assignments.tmp';
        file_put_contents($tempFile, json_encode($assignments, JSON_PRETTY_PRINT));
        upload($tempFile, $filePath);
        unlink($tempFile);
    }

    private function generateDropTablesQuery(string $database): string
    {
        return preg_replace('/\s*\R\s*/', ' ', trim(sprintf(<<<'EOT'
        SET FOREIGN_KEY_CHECKS = 0;
        SET GROUP_CONCAT_MAX_LEN = 32768;

        SET @tables = NULL;
        SELECT GROUP_CONCAT('`', table_name, '`') INTO @tables
        FROM information_schema.tables
        WHERE table_schema = '%s';

        SET @query = CONCAT('DROP TABLE IF EXISTS ', @tables);
        PREPARE stmt FROM @query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        SET FOREIGN_KEY_CHECKS = 1;
        EOT, $database)));
    }
}
