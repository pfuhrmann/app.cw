<?php

namespace COMP1687\CW;

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
