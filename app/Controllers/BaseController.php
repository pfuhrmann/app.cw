<?php

namespace COMP1687\CW\Controllers;

use COMP1687\CW\DatabaseManager;
use PDO;
use Twig_Environment;

class BaseController
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
}
