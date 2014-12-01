<?php

namespace COMP1687\CW;

use PDO;

/**
 * Singleton for database management.
 * Initiates PDO based on environment.
 */
class DatabaseManager
{
    /**
     * @var PDO
     */
    protected static $db;

    /**
     * @return PDO
     */
    public static function getInstance()
    {
        if (!isset(self::$db)) {
            // Testing env
            if ($_SERVER['SERVER_NAME'] === 'app.cw') {
                return self::$db = new \PDO('mysql:host=;dbname=;charset=utf8', 'root', 'toor');
            }

            // Production env
            return self::$db = new \PDO('mysql:host;dbname=;charset=utf8', 'fp202', 'wantze8Q');
        }

        return self::$db;
    }
}
