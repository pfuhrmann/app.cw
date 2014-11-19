<?php

namespace Respect\Validation\Rules;

use COMP1687\CW\DatabaseManager;
use PDO;

/**
 * Check if username from input is
 *  unique within our db
 */
class UniqueUser extends AbstractRule
{
    /**
     * @var PDO
     */
    protected $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    public function validate($input)
    {
        $stmt = $this->db->prepare("SELECT count('id') AS count FROM account WHERE username=?");
        $stmt->execute([$input]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($row['count'] === "0");
    }
}
