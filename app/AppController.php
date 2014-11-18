<?php

namespace COMP1687\CW;

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
     * URI: /
     */
    public function getIndex()
    {
        echo $this->render('index.html', []);
    }

    /**
     * Level 1 : Account creation : 18 marks
     * URI: registration
     */
    public function getRegistration()
    {
        echo $this->render('registration.html', []);
    }

    /**
     * Render Twig template
     *
     * @param $template
     */
    private function render($template, $options)
    {
        $template = $this->twig->loadTemplate($template);
        echo $template->render($options);
    }
}