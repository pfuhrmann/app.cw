<?php

namespace COMP1687\CW\Controllers;

class ServicesController extends BaseController
{
    /**
     * Level 4 : Sitter post :12 marks
     * GET services
     */
    public function getServices()
    {
        if (!$this->checkAuthentication()) {
            return "You are not authorized to access this page!";
        }

        return $this->render('services/list.html', []);
    }

    /**
     * Add new service
     *
     * GET addservice
     */
    public function getAddservice()
    {
        if (!$this->checkAuthentication()) {
            return "You are not authorized to access this page!";
        }

        return $this->render('services/update.html', []);
    }
}
