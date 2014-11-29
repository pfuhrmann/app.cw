<?php

namespace COMP1687\CW;

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
}
