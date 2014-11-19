<?php

namespace COMP1687\CW;

/**
 * Singleton for database management.
 * Initiates PDO based on environment.
 */
class DatabaseManager
{
    protected static $db;

    public static function getInstance()
    {
        if (!isset(self::$db)) {
            // Testing env
            if ($_SERVER['SERVER_NAME'] === 'app.cw') {
                return self::$db = new \PDO('mysql:host=localhost;dbname=mdb_fp202;charset=utf8', 'root', 'toor');
            }

            // Production env
            return self::$db = new \PDO('mysql:host=mysql.cms.gre.ac.uk;dbname=mdb_fp202;charset=utf8', 'fp202', 'wantze8Q');
        }

        return self::$db;
    }
}
