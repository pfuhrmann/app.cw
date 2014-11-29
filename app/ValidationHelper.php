<?php

namespace COMP1687\CW;

use COMP1687\CW\DatabaseManager;
use PDO;
use Respect\Validation\Validator;

/**
 * Validation rules
 */
class ValidationHelper
{

    /**
     * @var ValidationHelper
     */
    protected static $validator;

    /**
     * @var PDO
     */
    protected $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * @return ValidationHelper
     */
    public static function getInstance()
    {
        if (!isset(self::$validator)) {
            return self::$validator = new ValidationHelper();
        }

        return self::$validator;
    }

    /**
     * Validate registration
     * @param $data
     */
    public function registration($data)
    {
        $errors = [];

        // Validate username
        $usernameValidator = Validator::alnum()->noWhitespace()->length(3, 15)->notEmpty()->uniqueUser();
        try {
            $usernameValidator->assert($data['username']);
        } catch(\InvalidArgumentException $e) {
            $errors['username'] = array_filter($e->findMessages([
                'alnum'        => '<strong>Username</strong> must contain only letters and digits',
                'length'       => '<strong>Username</strong> must be between 3 and 15 characters',
                'noWhitespace' => '<strong>Username</strong> cannot contain spaces',
                'notEmpty'     => '<strong>Username</strong> cannot be empty',
                'uniqueUser'   => 'This <strong>username</strong> is taken, please choose another one',
            ]));
        }

        // Validate password
        $passwordValidator = Validator::length(6, 20)->notEmpty();
        try {
            $passwordValidator->assert($data['password']);
        } catch(\InvalidArgumentException $e) {
            $errors['password'] = array_filter($e->findMessages([
                'notEmpty' => '<strong>Password</strong> cannot be empty',
                'length'   => '<strong>Password</strong> length must be between 6 and 20 characters'
            ]));
        }

        // Validate email
        $emailValidator = Validator::email()->notEmpty();
        try {
            $emailValidator->assert($data['email']);
        } catch(\InvalidArgumentException $e) {
            $errors['email'] = array_filter($e->findMessages([
                'notEmpty' => '<strong>Email</strong> cannot be empty',
                'email'    => '<strong>Email</strong> is not valid',
            ]));
        }

        // Validate captcha
        $captchaValidator = Validator::equals($_SESSION['phrase'])->notEmpty();
        try {
            $captchaValidator->assert($data['captcha']);
        } catch(\InvalidArgumentException $e) {
            $errors['captcha'] = array_filter($e->findMessages([
                'notEmpty' => '<strong>CAPTCHA</strong> cannot be empty',
                'equals'   => 'Incorrect <strong>CAPTCHA</strong>, please try again',
            ]));
        }

        return $errors;
    }

    /**
     * Validate login
     * @param $data
     */
    public function login($data)
    {
        $errors = [];

        // Validate username
        $usernameValidator = Validator::allOf(Validator::notEmpty(), Validator::uniqueUser()->not());
        if (!$usernameValidator->validate($data['username'])) {
            $error['login']['false'] = 'Incorrect <strong>username</strong> or <strong>password</strong>';
        }

        // Validate password
        $passwordValidator = Validator::notEmpty()->passwordMatches($data['username']);
        try {
            $passwordValidator->assert($data['password']);
        } catch(\InvalidArgumentException $e) {
            $errors['login']['false'] = 'Incorrect <strong>username</strong> or <strong>password</strong>';
        }

        return $errors;
    }

    /**
     * Validate verify form
     * @param $data
     */
    public function verify($data)
    {
        $errors = [];

        // Get code for this user
        $stmt = $this->db->prepare("SELECT code FROM account WHERE username=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['username']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validate code
        $codeValidator = Validator::equals($row['code'])->notEmpty();
        try {
            $codeValidator->assert($data['code']);
        } catch(\InvalidArgumentException $e) {
            $errors['code'] = array_filter($e->findMessages([
                'equals'   => '<strong>Code</strong> entered is invalid, please recheck your verification email',
                'notEmpty' => 'Please provide activation <strong>code</strong>, which you received in the verification email',
            ]));
        }

        return $errors;
    }

    /**
     * Validate post form
     * @param $data
     */
    public function post($data)
    {
        $errors = [];

        // Validate business name
        $businessNameValidator = Validator::length(3, 30)->notEmpty();
        try {
            $businessNameValidator->assert($data['business']);
        } catch(\InvalidArgumentException $e) {
            $errors['business'] = array_filter($e->findMessages([
                'length'       => '<strong>Business name</strong> must be between 3 and 30 characters',
                'notEmpty'     => '<strong>Business name</strong> cannot be empty',
            ]));
        }

        // Validate postcode
        $errors = $this->postcode($data);

        return $errors;
    }

    /**
     * Validate picture form
     * @param $data
     */
    public function picture($data)
    {
        $errors = [];

        // Validate picture title
        $pictureTitleValidator = Validator::length(2, 15)->notEmpty();
        try {
            $pictureTitleValidator->assert($data['title']);
        } catch(\InvalidArgumentException $e) {
            $errors['picture'] = array_filter($e->findMessages([
                'length'   => '<strong>Picture title</strong> must be between 2 and 15 characters',
                'notEmpty' => '<strong>Picture title</strong> cannot be empty',
            ]));
        }

        return $errors;
    }

    /**
     * Validate postcode
     * @param $data
     */
    public function postcode($data)
    {
        $errors = [];

        // Validate postcode
        $postcodeValidator = Validator::alnum()->length(3, 9)->postcode()->notEmpty();
        try {
            $postcodeValidator->assert($data['postcode']);
        } catch(\InvalidArgumentException $e) {
            $errors['postcode'] = array_filter($e->findMessages([
                'alnum'        => '<strong>Postcode</strong> must contain only letters and digits',
                'length'       => '<strong>Postcode</strong> must be between 3 and 9 characters',
                'notEmpty'     => '<strong>Postcode</strong> cannot be empty',
                'postcode'     => '<strong>Postcode</strong> is invalid. Only Royal Borought of Greenwich districts are allowed. Example: SE10 9ED',
            ]));
        }

        return $errors;
    }
}
