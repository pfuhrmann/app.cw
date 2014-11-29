<?php

namespace COMP1687\CW\Controllers;

use COMP1687\CW\ValidationHelper;

/**
 * Handle Ajax for JavaScript validations
 */
class AjaxController
{
    /**
     * @var ValidationHelper
     */
    protected $validator;

    /**
     * Overloading base constructor
     */
    public function __construct()
    {
        $this->validator = ValidationHelper::getInstance();
    }

    /**
     * Validate registration form
     * POST /validate-registration
     */
    public function postValidateRegistration()
    {
        $errors = $this->validator->registration($_POST);

        return json_encode($errors);
    }

    /**
     * Validate login form
     * POST /validate-login
     */
    public function postValidateLogin()
    {
        $errors = $this->validator->login($_POST);

        return json_encode($errors);
    }

    /**
     * Validate verify form
     * POST /validate-verify
     */
    public function postValidateVerify()
    {
        $errors = $this->validator->verify($_POST);

        return json_encode($errors);
    }

    /**
     * Validate post form
     * POST /validate-post
     */
    public function postValidatePost()
    {
        $errors = $this->validator->post($_POST);

        return json_encode($errors);
    }

    /**
     * Validate picture form
     * POST /validate-picture
     */
    public function postValidatePicture()
    {
        $errors = $this->validator->picture($_POST);

        return json_encode($errors);
    }

    /**
     * Validate postcode
     * POST /validate-postcode
     */
    public function postValidatePostcode()
    {
        $errors = $this->validator->postcode($_POST);

        return json_encode($errors);
    }
}
