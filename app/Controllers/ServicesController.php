<?php

namespace COMP1687\CW\Controllers;

use PDO;
use Respect\Validation\Validator;

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

        $service = $this->getServiceDetails();
        $service['details'] = nl2br($service['details']);
        return $this->render('services/list.html', [
            'service' =>  $service,
        ]);
    }

    /**
     * Add new service
     * GET addservice
     */
    public function getAddservice()
    {
        if (!$this->checkAuthentication()) {
            return "You are not authorized to access this page!";
        }

        return $this->render('services/update.html', [
            'input' => $this->getServiceDetails(),
        ]);
    }

    /**
     * Handle update service form
     * POST addservice
     */
    public function postAddservice()
    {
        if (!$this->checkAuthentication()) {
            return "You are not authorized to access this page!";
        }

        $formData = $_POST;
        $errors = [];

        // Validate business name
        $businessNameValidator = Validator::length(3, 30);
        try {
            $businessNameValidator->assert($formData['business']);
        } catch(\InvalidArgumentException $e) {
            $errors['business'] = array_filter($e->findMessages([
                'length'       => '<strong>Business name</strong> must be between 3 and 30 characters',
                //'notEmpty'     => '<strong>Business name</strong> cannot be empty',
            ]));
        }

        // Validate postcode
        $postcodeValidator = Validator::alnum()->length(3, 9)->postcode();
        try {
            $postcodeValidator->assert($formData['postcode']);
        } catch(\InvalidArgumentException $e) {
            $errors['postcode'] = array_filter($e->findMessages([
                'alnum'        => '<strong>Postcode</strong> must contain only letters and digits',
                'length'       => '<strong>Postcode</strong> must be between 5 and 9 characters',
                //'notEmpty'     => '<strong>Postcode</strong> cannot be empty',
                'postcode'     => '<strong>Postcode</strong> is invalid. Only Royal Borought of Greenwich districts are allowed. Example: SE10 9ED',
            ]));
        }

        // We get errors so display form again
        if (!empty($errors)) {
            return $this->render('services/update.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
            ]);
        }

        // All good, create service in DB
        $stmt = $this->db->prepare("UPDATE service SET business=?, postcode=?, type=?, details=? WHERE account_id=?");
        $stmt->execute([
            $formData['business'], $formData['postcode'], $formData['type'], $formData['details'], $_SESSION['user']['id']
        ]);

        return $this->redirect('services');
    }

    /**
     * Level 5 : Image upload : 10 marks
     * GET addpicture
     */
    public function getAddpicture()
    {
        if (!$this->checkAuthentication()) {
            return "You are not authorized to access this page!";
        }

        return $this->render('services/add-picture.html', []);
    }

    /**
     * Handle add picture form
     * POST addpicture
     */
    public function postAddpicture()
    {
        $formData = $_POST;
        $errors = [];

        // Validate picture title
        $pictureTitleValidator = Validator::length(2, 15)->notEmpty();
        try {
            $pictureTitleValidator->assert($formData['title']);
        } catch(\InvalidArgumentException $e) {
            $errors['picture'] = array_filter($e->findMessages([
                'length'       => '<strong>Picture title</strong> must be between 2 and 15 characters',
                'notEmpty'     => '<strong>Picture title</strong> cannot be empty',
            ]));
        }

        // Validate file
        $fileValidator = Validator::notEmpty();
        try {
            $fileValidator->assert($formData['file']);
        } catch(\InvalidArgumentException $e) {
            $errors['file'] = array_filter($e->findMessages([
                'length'       => '<strong>File</strong> must be between 2 and 15 characters',
                'notEmpty'     => '<strong>File</strong> cannot be empty',
            ]));
        }

        // We get errors so display form again
        if (!empty($errors)) {
            return $this->render('services/add-picture.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
            ]);
        }

        die('all good');
    }

    /**
     * @return array
     */
    private function getServiceDetails()
    {
        $stmt = $this->db->prepare("SELECT * FROM service WHERE account_id=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }
}
