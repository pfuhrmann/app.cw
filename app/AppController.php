<?php

namespace COMP1687\CW;

use Gregwar\Captcha\CaptchaBuilder;
use Twig_Environment;

class AppController
{
    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @param Twig_Environment $twig
     */
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
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
        // Generate captcha
        $builder = new CaptchaBuilder;
        $builder->build();

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

        echo $this->render('registration.html', []);
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
}