<?php

namespace COMP1687\CW;

class DatabaseManager
{
    protected static $db;

    public static function getInstance()
    {
        if (!isset(self::$db)) {
            // Testing env
            if ($_SERVER['SERVER_NAME'] == 'app.cw') {
                return self::$db = new \PDO('mysql:host=localhost;dbname=app-cw;charset=utf8', 'root', 'toor');
            }

            // Production env
            return self::$db = new \PDO('mysql:host=mysql.cms.gre.ac.uk;dbname=mdb_userid;charset=utf8', 'fp202', 'wantze8Q');
        }

        return self::$db;
    }
}
