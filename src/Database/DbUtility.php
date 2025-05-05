<?php

namespace Xima\XimaDeployerTools\Utility;


use Xima\XimaDeployerTools\Database\Manager\ManagerInterface;
use Xima\XimaDeployerTools\Database\Manager\Root;
use function Deployer\get;
use function Deployer\run;
use function Deployer\test;

class DbUtility
{

    protected static array $databaseManagers = [
        'default' => Root::class,
        'root' => Root::class,
        'simple' => 'todo',
        'api' => 'todo',
    ];


    /**
     *
     */
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