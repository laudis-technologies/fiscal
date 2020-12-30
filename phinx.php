<?php

declare(strict_types=1);

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/vendor/autoload.php';

return [
    'paths' => [
        'migrations' => [
            __DIR__.'/database/migrations',
        ],
        'seeds' => __DIR__.'/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'migrations_log',
        'default_database' => 'default',
        'default' => [
            'adapter' => 'mysql',
            'connection' => new PDO('mysql:host=mariadb;dbname=test', 'root', 'test'),
            'name' => 'test',
        ],
    ],
    'version_order' => 'creation',
];
