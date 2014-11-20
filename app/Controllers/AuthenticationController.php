<?php

namespace COMP1687\CW\Controllers;

use Gregwar\Captcha\CaptchaBuilder;
use PDO;
use Respect\Validation\Validator;

class AuthenticationController extends BaseController
{
    /**
     * Index page
     * GET /
     */
    public function getIndex()
    {
        return $this->render('index.html', []);
    }

    /**
     * Level 1 : Account creation : 18 marks
     * GET registration
     */
    public function getRegistration()
    {
        $builder = $this->generateCaptcha();

        return $this->render('authentication/registration.html', [
            'captcha' => $builder->inline()
        ]);
    }

    /**
     * Handle registration form
     * POST registration
     */
    public function postRegistration()
    {
        $formData = $_POST;
        $errors = [];

        // Validate username
        $usernameValidator = Validator::alnum()->noWhitespace()->length(3, 15)->notEmpty()->uniqueUser();
        try {
            $usernameValidator->assert($formData['username']);
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
            $passwordValidator->assert($formData['password']);
        } catch(\InvalidArgumentException $e) {
            $errors['password'] = array_filter($e->findMessages([
                'notEmpty' => '<strong>Password</strong> cannot be empty',
                'length'   => '<strong>Password</strong> length must be between 6 and 20 characters'
            ]));
        }

        // Validate email
        $emailValidator = Validator::email()->notEmpty();
        try {
            $emailValidator->assert($formData['email']);
        } catch(\InvalidArgumentException $e) {
            $errors['email'] = array_filter($e->findMessages([
                'notEmpty' => '<strong>Email</strong> cannot be empty',
                'email'    => '<strong>Email</strong> is not valid',
            ]));
        }

        // Validate captcha
        $captchaValidator = Validator::equals($_SESSION['phrase'])->notEmpty();
        try {
            $captchaValidator->assert($formData['captcha']);
        } catch(\InvalidArgumentException $e) {
            $errors['captcha'] = array_filter($e->findMessages([
                'notEmpty' => '<strong>CAPTCHA</strong> cannot be empty',
                'equals'   => 'Incorrect <strong>CAPTCHA</strong>, please try again',
            ]));
        }

        // We get errors so display reg form again
        if (!empty($errors)) {
            $builder = $this->generateCaptcha();

            return $this->render('authentication/registration.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
                'captcha'   => $builder->inline(),
            ]);
        }

        // All good, create account
        $pass = password_hash($formData['password'], PASSWORD_BCRYPT);
        $code = rand(10000, 99999);
        $stmt = $this->db->prepare("INSERT INTO account (username, password, email, code) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $formData['username'], $pass, $formData['email'], $code
        ]);
        $user_id = $this->db->lastInsertId();

        // Create empty service in DB
        $stmt = $this->db->prepare("INSERT INTO service (account_id) VALUES (?)");
        $stmt->execute([$user_id]);

        // Send verification email
        $subject = 'Confirm your sitter\'s account';
        $message = "Hello ".$formData['username']."! You activation code is: ".$code;
        mail($formData['email'], $subject, $message);

        // Login user
        $_SESSION['user']['username'] = $formData['username'];
        $_SESSION['user']['email'] = $formData['email'];
        $_SESSION['user']['active'] = '0';
        $_SESSION['user']['id'] = $user_id;

        // Redirect to verification page
        return $this->redirect('verify');
    }

    /**
     * Level 2 : Verify account : 12 marks
     * GET verify
     */
    public function getVerify()
    {
        if (!$this->checkAuthentication("0")) {
            return "You are not authorized to access this page!";
        }

        // Check if user actually needs verification
        if ($_SESSION['user']['active'] === '1') {
            // Activated already, redirect to main page
            return $this->redirect();
        }

        return $this->render('authentication/verify.html', [
            'displayInfo' => true,
            'email' => $_SESSION['user']['email'],
        ]);
    }

    /**
     * Handle verification form
     */
    public function postVerify()
    {
        $formData = $_POST;
        $errors = [];

        // Get code for this user
        $stmt = $this->db->prepare("SELECT code FROM account WHERE username=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['username']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validate code
        $codeValidator = Validator::equals($row['code'])->notEmpty();
        try {
            $codeValidator->assert($formData['code']);
        } catch(\InvalidArgumentException $e) {
            $errors['code'] = array_filter($e->findMessages([
                'equals'   => '<strong>Code</strong> entered is invalid, please recheck your verification email',
                'notEmpty' => 'Please provide activation <strong>code</strong>, which you received in the verification email',
            ]));
        }

        // We get errors so display verify form again
        if (!empty($errors)) {
            return $this->render('authentication/verify.html', [
                'errorsAll' => $errors,
                'displayInfo' => false,
            ]);
        }

        // All good, activate account
        $stmt = $this->db->prepare("UPDATE account SET active = 1, code = '' WHERE username=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['username']]);
        $_SESSION['user']['active'] = '1';

        // Redirect to main page
        return $this->redirect();
    }

    /**
     * Level 3 : Authentication : 10 marks
     * GET login
     */
    public function getLogin()
    {
        echo $this->render('authentication/login.html', []);
    }

    /**
     * Handle login form
     */
    public function postLogin()
    {
        $formData = $_POST;
        $errors = [];

        // Validate username
        $usernameValidator = Validator::allOf(Validator::notEmpty(), Validator::uniqueUser()->not());
        if (!$usernameValidator->validate($formData['username'])) {
            $error['login']['false'] = 'Incorrect <strong>username</strong> or <strong>password</strong>';
        }

        // Validate password
        $passwordValidator = Validator::notEmpty()->passwordMatches($formData['username']);
        try {
            $passwordValidator->assert($formData['password']);
        } catch(\InvalidArgumentException $e) {
            $errors['login']['false'] = 'Incorrect <strong>username</strong> or <strong>password</strong>';
        }

        // We get errors so display login page again
        if (!empty($errors)) {
            return $this->render('authentication/login.html', [
                'errorsAll' => $errors,
            ]);
        }

        // Get user's email
        $stmt = $this->db->prepare("SELECT id, email FROM account WHERE username=? LIMIT 1");
        $stmt->execute([$formData['username']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Login user
        $_SESSION['user']['username'] = $formData['username'];
        $_SESSION['user']['email'] = $row['email'];

        // Check user is active
        $userValidator = Validator::activeUser();
        if (!($userValidator->validate($formData['username']))) {
            // Not active, redirect to verify
            $_SESSION['user']['active'] = '0';

            return $this->redirect('verify');
        }

        // All good, redirect to main page
        $_SESSION['user']['active'] = '1';
        $_SESSION['user']['id'] = $row['id'];
        return $this->redirect();
    }

    /**
     * Log user out of system
     */
    public function getLogout()
    {
        session_destroy();

        return $this->redirect();
    }

    /**
     * Generate new captcha
     *
     * @return CaptchaBuilder
     */
    private function generateCaptcha()
    {
        $builder = new CaptchaBuilder;
        $builder->build();
        // Store phrase to use later in validation
        $_SESSION['phrase'] = $builder->getPhrase();

        return $builder;
    }
}
