<?php
/**
 * RK Marketing Drive - Configuracion Doctrine ORM
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

function createEntityManager(): EntityManager {
    $config = ORMSetup::createAttributeMetadataConfiguration(
        paths: [__DIR__ . '/../src/Entity'],
        isDevMode: APP_ENV === 'development'
    );

    $connection = [
        'driver'   => 'pdo_mysql',
        'host'     => DB_HOST,
        'port'     => DB_PORT,
        'dbname'   => DB_NAME,
        'user'     => DB_USER,
        'password' => DB_PASS,
        'charset'  => DB_CHARSET,
    ];

    return new EntityManager(
        \Doctrine\DBAL\DriverManager::getConnection($connection, $config),
        $config
    );
}

function em(): EntityManager {
    static $instance = null;
    if ($instance === null) {
        $instance = createEntityManager();
    }
    return $instance;
}
