<?php

namespace COMP1687\CW;

use Gregwar\Captcha\CaptchaBuilder;
use PDO;
use Respect\Validation\Validator;
use Twig_Environment;

class AppController
{
    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var PDO
     */
    protected $db;

    /**
     * @param Twig_Environment $twig
     */
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->db = DatabaseManager::getInstance();
    }

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
        return $this->render('registration.html', [
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

        /*// Validate captcha
        $captchaValidator = Validator::equals($_SESSION['phrase'])->notEmpty();
        try {
            $captchaValidator->assert($formData['captcha']);
        } catch(\InvalidArgumentException $e) {
            $errors['captcha'] = array_filter($e->findMessages([
                'notEmpty' => '<strong>CAPTCHA</strong> cannot be empty',
                'equals'   => 'Incorrect <strong>CAPTCHA</strong>, please try again',
            ]));
        }*/


        // We get errors so display reg form again
        if (!empty($errors)) {
            $builder = $this->generateCaptcha();

            return $this->render('registration.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
                'captcha'   => $builder->inline(),
            ]);
        }

        // All good, create account
        $pass = password_hash($formData['password'], PASSWORD_BCRYPT);
        $code = rand(10000, 99999);
        $stmt = $this->db->prepare("INSERT INTO accounts (username, password, email, code) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $formData['username'], $pass, $formData['email'], $code
        ]);

        // Send verification email
        $subject = 'Confirm your sitter\'s account';
        $message = "Hello ".$formData['username']."! You activation code is: ".$code;
        mail($formData['email'], $subject, $message);

        // Login user
        $_SESSION['user']['username'] = $formData['username'];
        $_SESSION['user']['email'] = $formData['email'];
        $_SESSION['user']['active'] = '0';

        // Redirect to verification page
        header('Location: index.php?uri=verify');
    }

    /**
     * Level 2 : Verify account : 12 marks
     * GET verify
     */
    public function getVerify()
    {
        // Check if logged in
        if (empty($_SESSION['user']['username'])) {
            header("HTTP/1.0 403 Forbidden");
            return "You are not authorized to access this page!";
        }

        // Check if user actually needs verification
        if ($_SESSION['user']['active'] === '1') {
            // Activated already, redirect to main page
            header('Location: ./');
        }

        return $this->render('verify.html', [
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
        $stmt = $this->db->prepare("SELECT code FROM accounts WHERE username=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['username']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validate code
        $codeValidator = Validator::equals($row['code'])->notEmpty();
        try {
            $codeValidator->assert($formData['code']);
        } catch(\InvalidArgumentException $e) {
            $errors['username'] = array_filter($e->findMessages([
                'equals'   => '<strong>Code</strong> entered is invalid, please recheck your verification email',
                'notEmpty' => 'Please provide activation <strong>code</strong>, which you received in the verification email',
            ]));
        }

        // We get errors so display verify form again
        if (!empty($errors)) {
            return $this->render('verify.html', [
                'errorsAll' => $errors,
                'displayInfo' => false,
            ]);
        }

        // All good, activate account
        $stmt = $this->db->prepare("UPDATE accounts SET active = 1, code = ''");
        $stmt->execute();
        $_SESSION['user']['active'] = '1';

        // Redirect to main page
        header('Location: ./');
    }

    /**
     * Level 3 : Authentication : 10 marks
     * GET login
     */
    public function getLogin()
    {
        echo $this->render('login.html', []);
    }

    /**
     * Logout user out of system
     */
    public function getLogout()
    {
        session_destroy();
        header('Location: ./');
    }

    /**
     * Render Twig template
     *
     * @param $template
     */
    private function render($template, $options)
    {
        $template = $this->twig->loadTemplate($template.'.twig');
        echo $template->render($options);
    }

    /**
     * Generate new captcha
     *
     * @return CaptchaBuilder
     */
    private function generateCaptcha()
    {
        // Generate captcha
        $builder = new CaptchaBuilder;
        $builder->build();
        // Store phrase to use later in validation
        $_SESSION['phrase'] = $builder->getPhrase();

        return $builder;
    }
}