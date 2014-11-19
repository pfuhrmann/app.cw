<?php

namespace Respect\Validation\Rules;

use COMP1687\CW\DatabaseManager;
use PDO;

/**
 * Check if user's account is active
 */
class ActiveUser extends AbstractRule
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
        $stmt = $this->db->prepare("SELECT active FROM account WHERE username=?");
        $stmt->execute([$input]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($row['active'] === "1");
    }
}
