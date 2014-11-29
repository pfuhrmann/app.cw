<?php

namespace COMP1687\CW\Controllers;

use Gregwar\Captcha\CaptchaBuilder;
use PDO;
use Respect\Validation\Validator;

/**
 * All the authentication functionality
 */
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
        $errors = $this->validator->registration($formData);

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
        $userId = $this->db->lastInsertId();

        // Send verification email
        $subject = 'Confirm your sitter\'s account';
        $message = "Hello ".$formData['username']."! You activation code is: ".$code;
        mail($formData['email'], $subject, $message);

        // Login user
        $_SESSION['user']['username'] = $formData['username'];
        $_SESSION['user']['email'] = $formData['email'];
        $_SESSION['user']['active'] = '0';
        $_SESSION['user']['id'] = $userId;

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
     * POST verify
     */
    public function postVerify()
    {
        $formData = $_POST;
        $errors = $this->validator->verify($formData);

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
        echo $this->render('authentication/login.html', [
            'username' => (isset($_COOKIE['username'])) ? $_COOKIE['username'] : '',
        ]);
    }

    /**
     * Handle login form
     * POST login
     */
    public function postLogin()
    {
        $formData = $_POST;
        $errors = $this->validator->login($formData);

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
        setcookie('username', $formData['username'], time() + (86400 * 30)); // 30 days
        $_SESSION['user']['active'] = '1';
        $_SESSION['user']['id'] = $row['id'];
        return $this->redirect();
    }

    /**
     * Log user out of system
     * GET verify
     */
    public function getLogout()
    {
        session_destroy();

        return $this->redirect();
    }

    /**
     * Display Cookies Policy page
     * GET cookies
     */
    public function getCookies()
    {
        return $this->render('cookie.html', []);
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
