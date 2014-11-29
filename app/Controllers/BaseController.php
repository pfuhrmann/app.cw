<?php

namespace COMP1687\CW\Controllers;

use COMP1687\CW\DatabaseManager;
use COMP1687\CW\ValidationHelper;
use PDO;
use Twig_Environment;

abstract class BaseController
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
     * @var ValidationHelper
     */
    protected $validator;

    /**
     * @param Twig_Environment $twig
     */
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->db = DatabaseManager::getInstance();
        $this->validator = ValidationHelper::getInstance();
    }

    /**
     * Render Twig template
     *
     * @param $template
     */
    protected function render($template, $options)
    {
        $template = $this->twig->loadTemplate($template.'.twig');
        echo $template->render($options);
    }

    /**
     * Redirect to specified uri
     *
     * @param $uri
     */
    protected function redirect($uri = '')
    {
        if (empty($uri)) {
            return header('Location: ./');
        }

        return header('Location: index.php?uri='.$uri);
    }

    /**
     * @return string
     */
    protected function checkAuthentication($active = "1")
    {
        // Must be authenticated
        if (!isset($_SESSION['user']) || $_SESSION['user']['active'] !== $active) {
            header("HTTP/1.0 403 Forbidden");

            return false;
        }

        return true;
    }
}
