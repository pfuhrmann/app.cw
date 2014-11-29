<?php

namespace COMP1687\CW\Controllers;

use Gregwar\Image\Image;
use PDO;
use Respect\Validation\Validator;

/**
 * Handles Service and Image CRUD functions
 */
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

        // Get post images
        $stmt = $this->db->prepare("SELECT * FROM image WHERE account_id=?");
        $stmt->execute([$_SESSION['user']['id']]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('services/list.html', [
            'images' => $images,
            'posts' => $posts,
        ]);
    }

    /**
     * View service details
     * GET post-details
     */
    public function getPostDetails()
    {
        $serviceID = $_GET['id'];
        $service = $this->getServiceData($serviceID);
        $service['details'] = nl2br($service['details']);

        return $this->render('services/view.html', [
            'service' => $service,
        ]);
    }

    /**
     * Add new service form
     * GET add-service
     */
    public function getAddService()
    {
        return $this->render('services/add.html', []);
    }

    /**
     * Handle add service form
     * POST add-service
     */
    public function postAddService()
    {
        $formData = $_POST;
        $errors = $this->validator->post($formData);

        // We get errors so display form again
        if (!empty($errors)) {
            return $this->render('services/add.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
            ]);
        }

        // All good, create service in DB
        $stmt = $this->db->prepare("INSERT INTO service(account_id, business, postcode, type, details) VALUES(?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user']['id'], $formData['business'], $formData['postcode'], $formData['type'], $formData['details']
        ]);

        return $this->redirect('services');
    }

    /**
     * Update service form
     * GET update-service
     */
    public function getUpdateService()
    {
        $serviceID = $_GET['id'];

        return $this->render('services/update.html', [
            'input' => $this->getServiceData($serviceID),
        ]);
    }

    /**
     * Handle update service form
     * POST update-service
     */
    public function postUpdateService()
    {
        $serviceID = $_GET['id'];
        $formData = $_POST;
        $errors = $this->validator->post($formData);

        // We get errors so display form again
        if (!empty($errors)) {
            return $this->render('services/add.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
            ]);
        }

        // All good, update service in DB
        $stmt = $this->db->prepare("UPDATE service SET business=?, postcode=?, type=?, details=? WHERE ID=? AND account_id=?");
        $stmt->execute([
            $formData['business'], $formData['postcode'], $formData['type'], $formData['details'], $serviceID, $_SESSION['user']['id']
        ]);

        return $this->redirect('services');
    }

    /**
     * Delete service
     * GET delete-service
     */
    public function getDeleteService()
    {
        $serviceID = $_GET['id'];

        // Delete database record
        $stmt = $this->db->prepare("DELETE FROM service WHERE account_id=? AND id=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['id'], $serviceID]);

        return $this->redirect('services');
    }

    /**
     * Level 5 : Image upload : 10 marks
     * GET add-picture
     */
    public function getAddPicture()
    {
        return $this->render('services/add-picture.html', []);
    }

    /**
     * Handle add picture form
     * POST add-picture
     */
    public function postAddPicture()
    {
        $formData = $_POST;
        $errors = $this->validator->picture($formData);

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

        // Store image
        $name = md5($_FILES['file']['tmp_name'].time());
        // Save original
        Image::open($_FILES['file']['tmp_name'])
            ->save('uploads/'.$name.'_original');
        // Save thumb large
        Image::open($_FILES['file']['tmp_name'])
            ->resize(250, 150)
            ->save('uploads/'.$name.'_large');
        // Save thumb small
        Image::open($_FILES['file']['tmp_name'])
            ->resize(120, 120)
            ->save('uploads/'.$name.'_small');

        // Get image count for this user
        $stmt = $this->db->prepare("SELECT COUNT(id) AS count FROM image WHERE account_id=?");
        $stmt->execute([$_SESSION['user']['id']]);
        $imageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        // Default if selected or if first picture
        $default = ($formData['default'] === 'on' || $imageCount === "0");
        if ($default) {
            // New default image update old default (if any)
            $stmt = $this->db->prepare("UPDATE image SET defaultimg=0 WHERE account_id=? AND defaultimg=1 LIMIT 1");
            $stmt->execute([$_SESSION['user']['id']]);
        }
        // Store image ref in DB
        $stmt = $this->db->prepare("INSERT INTO image (account_id, alt, name, defaultimg) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user']['id'], $formData['title'], $name, $default
        ]);

        return $this->redirect('services');
    }

    /**
     * Delete picture
     * GET delete-picture
     */
    public function getDeletePicture()
    {
        // Get images ref first
        $stmt = $this->db->prepare("SELECT * FROM image WHERE account_id=? AND id=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['id'], $_GET['id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete database record
        $stmt = $this->db->prepare("DELETE FROM image WHERE account_id=? AND id=? LIMIT 1");
        $stmt->execute([$_SESSION['user']['id'], $_GET['id']]);

        // Delete images
        if (isset($image['name'])) {
            unlink('uploads/'.$image['name'].'_original');
            unlink('uploads/'.$image['name'].'_small');
            unlink('uploads/'.$image['name'].'_large');
        }

        return $this->redirect('services');
    }

    /**
     * @return array
     */
    private function getServiceData($serviceID)
    {
        $stmt = $this->db->prepare("SELECT * FROM service WHERE id=? AND account_id=? LIMIT 1");
        $stmt->execute([$serviceID, $_SESSION['user']['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }
}
