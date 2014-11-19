<?php

namespace Respect\Validation\Rules;

use COMP1687\CW\DatabaseManager;
use PDO;

/**
 * Check of user's password matches
 */
class PasswordMatches extends AbstractRule
{
    /**
     * @var PDO
     */
    protected $db;

    protected $username;

    public function __construct($username)
    {
        $this->username = $username;
        $this->db = DatabaseManager::getInstance();
    }

    public function validate($input)
    {
        $stmt = $this->db->prepare("SELECT password FROM account WHERE username=? LIMIT 1");
        $stmt->execute([$this->username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return password_verify($input, $row['password']);
    }
}
