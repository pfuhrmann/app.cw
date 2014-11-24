<?php

namespace COMP1687\CW\Controllers;

use Gregwar\Image\Image;
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
        // Get accounts posts
        $stmt = $this->db->prepare("SELECT * FROM service WHERE account_id=?");
        $stmt->execute([$_SESSION['user']['id']]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get accounts images
        $stmt = $this->db->prepare("SELECT * FROM image WHERE account_id=?");
        $stmt->execute([$_SESSION['user']['id']]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $service = $this->getServiceDetails();
        $service['details'] = nl2br($service['details']);
        return $this->render('services/list.html', [
            'service' =>  $service,
            'images' => $images,
            'posts' => $posts,
        ]);
    }

    /**
     * Add new service
     * GET addservice
     */
    public function getAddservice()
    {
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
                'length'   => '<strong>Picture title</strong> must be between 2 and 15 characters',
                'notEmpty' => '<strong>Picture title</strong> cannot be empty',
            ]));
        }

        // Validate file
        if (empty($_FILES['file']['tmp_name'])) {
            $errors['picture']['file']  = '<strong>File</strong> cannot be empty';
        } else if (!getimagesize($_FILES['file']['tmp_name'])) {
            $errors['picture']['file']  = '<strong>File</strong> is not an image';
        }

        // We get errors so display form again
        if (!empty($errors)) {
            return $this->render('services/add-picture.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
            ]);
        }

        // Save file thumb
        $name = md5($_FILES['file']['tmp_name'].time());
        Image::open($_FILES['file']['tmp_name'])
            ->resize(290, 190)
            ->save('uploads/'.$name);

        // Store image ref in DB
        $stmt = $this->db->prepare("INSERT INTO image (account_id, alt, name) VALUES (?, ?, ?)");
        $stmt->execute([
            $_SESSION['user']['id'], $formData['title'], $name
        ]);

        return $this->redirect('services');
    }

    /**
     * Delete picture
     * GET deletepicture
     */
    public function getDeletepicture()
    {
        // Get images ref first
        $stmt = $this->db->prepare("SELECT * FROM image WHERE account_id=? AND id=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['id'], $_GET['id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete database record
        $stmt = $this->db->prepare("DELETE FROM image WHERE account_id=? AND id=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['id'], $_GET['id']]);

        // Delete file
        if (isset($image['name']))
        unlink('uploads/'.$image['name']);

        return $this->redirect('services');
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
