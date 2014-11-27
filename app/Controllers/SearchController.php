<?php

namespace COMP1687\CW\Controllers;

use PDO;
use Respect\Validation\Validator;

/**
 * Handles Service search function
 */
class SearchController extends BaseController
{
    /**
     * Search Sitters
     * GET search
     */
    public function getSearch()
    {
        return $this->render('services/search.html', []);
    }

    /**
     * Handle Search Sitters form
     * POST search
     */
    public function postSearch()
    {
        $formData = $_POST;

        // Get search type
        if (isset($formData['by-type'])) {
            $searchType = 'type';
            $formData['imageonly'] = false;
        } elseif (isset($formData['by-postcode'])) {
            $searchType = 'postcode';
            $formData['imageonly'] = false;
        } else {
            $searchType = $formData['search-type'];
        }
        $formData['searchType'] = $searchType;
        $imageOnly = (isset($formData['imageonly']) && $formData['imageonly'] === 'on');

        // Search by sitter type
        if ($searchType === 'type') {
            $formData['postcode'] = '';

            // Search for only posts with images
            if ($imageOnly) {
                $query = "SELECT s.*, i.name, i.alt FROM service s
                            INNER JOIN account a ON s.account_id = a.id
                            INNER JOIN image i ON a.id = i.account_id
                          WHERE s.type=?
                            AND i.defaultimg = 1";
                $stmt = $this->db->prepare($query);
            } else {
                $query = "SELECT s.*, i.name, i.alt FROM service s
                            INNER JOIN account a ON s.account_id = a.id
                            LEFT JOIN image i ON a.id = i.account_id
                          WHERE s.type=?
                            AND IF ((SELECT COUNT(*) FROM image WHERE account_id = i.account_id), i.defaultimg = 1, 1=1)";
                $stmt = $this->db->prepare($query);
            }
            $stmt->execute([$formData['type']]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Search by postcode
        } else if ($searchType === 'postcode') {
            $formData['type'] = '';

            // Validate postcode
            $postcodeValidator = Validator::alnum()->length(3, 9)->postcode()->notEmpty();
            try {
                $postcodeValidator->assert($formData['postcode']);
            } catch(\InvalidArgumentException $e) {
                $errors['postcode'] = array_filter($e->findMessages([
                    'alnum'        => '<strong>Postcode</strong> must contain only letters and digits',
                    'length'       => '<strong>Postcode</strong> must be between 5 and 9 characters',
                    'notEmpty'     => '<strong>Postcode</strong> cannot be empty',
                    'postcode'     => '<strong>Postcode</strong> is invalid. Only Royal Borought of Greenwich districts are allowed. Example: SE10 9ED',
                ]));
            }
        }

        // We get errors so display form again
        if (!empty($errors)) {
            return $this->render('services/search.html', [
                'input'     => $formData,
                'errorsAll' => $errors,
            ]);
        }

        return $this->render('services/search.html', [
            'posts' => $posts,
            'input' => $formData,
        ]);
    }

    /**
     * View service details
     * GET service-details
     */
    public function getServiceDetails()
    {
        $serviceID = $_GET['id'];

        // Get service data
        $stmt = $this->db->prepare("SELECT * FROM service WHERE id=? LIMIT 1");
        $stmt->execute([$serviceID]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        $service['details'] = nl2br($service['details']);

        // Get sitter's images
        $stmt = $this->db->prepare("SELECT * FROM image WHERE account_id=?");
        $stmt->execute([$service['account_id']]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('services/view-search.html', [
            'service' => $service,
            'images' => $images,
        ]);
    }
}
