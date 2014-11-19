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
        echo $this->render('index.html', []);
    }

    /**
     * Level 1 : Account creation : 18 marks
     * GET registration
     */
    public function getRegistration()
    {
        $builder = $this->generateCaptcha();
        echo $this->render('registration.html', [
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
        $stmt = $this->db->prepare(" INSERT INTO accounts (username, password, email) VALUES (?, ?, ?)");
        var_dump($stmt->execute([
            $formData['username'], $pass, $formData['email']
        ]));
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