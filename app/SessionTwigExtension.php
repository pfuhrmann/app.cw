<?php

namespace COMP1687\CW;

/**
 * Insert user session to Twig globals
 *  so we can access anywhere in the template
 */
class SessionTwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'session';
    }

    public function getGlobals()
    {
        if (isset($_SESSION['user'])) {
            return [
                'user' => $_SESSION['user'],
            ];
        }

        return ['user'];
    }
}
