<?php

namespace Xima\XimaDeployerTools\Database;

use Xima\XimaDeployerTools\Database\Manager\Api;
use Xima\XimaDeployerTools\Database\Manager\ManagerInterface;
use Xima\XimaDeployerTools\Database\Manager\Root;
use Xima\XimaDeployerTools\Database\Manager\Simple;
use function Deployer\get;
use function Deployer\has;
use function Deployer\run;
use function Deployer\test;

class DbUtility
{

    public const DATABASE_MANAGEMENT_TYPE_ROOT = 'root';
    public const DATABASE_MANAGEMENT_TYPE_SIMPLE = 'simple';
    public const DATABASE_MANAGEMENT_TYPE_API = 'api';

    protected static array $databaseManagers = [
        'default' => Root::class,
        self::DATABASE_MANAGEMENT_TYPE_ROOT => Root::class,
        self::DATABASE_MANAGEMENT_TYPE_SIMPLE => Simple::class,
        self::DATABASE_MANAGEMENT_TYPE_API => Api::class,
    ];

    public static function getDatabaseManager(): ManagerInterface
    {
        $type = has('database_manager_type') ? get('database_manager_type') : 'default';

        if (array_key_exists($type, self::$databaseManagers)) {
            $managerClass = self::$databaseManagers[$type];
            if (class_exists($managerClass)) {
                return new $managerClass();
            } else {
                throw new \Exception("Database manager class {$managerClass} does not exist.");
            }
        } else {
            throw new \Exception("Database manager type {$type} is not supported.");
        }
    }
}