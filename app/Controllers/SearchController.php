<?php

namespace COMP1687\CW\Controllers;

use PDO;
use Respect\Validation\Validator;
use Kilte\Pagination\Pagination;

/**
 * Handles Service search function
 */
class SearchController extends BaseController
{
    /**
     * Search for Sitters
     * GET/POST search
     */
    public function anySearch()
    {
        // Initial page request
        if ((!isset($_POST) || empty($_POST)) && !isset($_GET['page'])) {
            return $this->render('services/search.html', []);
        }

        // Store form data to Session initially
        if (!isset($_GET['page'])) {
            $formData = $_POST;
            $_SESSION['search-data'] = $formData;
        } else {
            $formData = $_SESSION['search-data'];
        }

        $currentPage = (isset($_GET['page'])) ? $_GET['page'] : 1;
        $itemsPerPage = 5;

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

        //
        // Search by sitter type
        //
        if ($searchType === 'type') {
            $formData['postcode'] = '';

            // Search for only posts with images
            if ($imageOnly) {
                // Prepare pagination
                $query = "SELECT COUNT(s.id) AS count FROM service s
                             INNER JOIN account a ON s.account_id = a.id
                             INNER JOIN image i ON a.id = i.account_id
                           WHERE s.type=?
                             AND i.defaultimg = 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$formData['type']]);
                $totalItems = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $pagination = new Pagination($totalItems, $currentPage, $itemsPerPage);
                $limit = $pagination->limit();
                $offset = $pagination->offset();

                // Get posts
                $query = "SELECT s.*, i.name, i.alt FROM service s
                            INNER JOIN account a ON s.account_id = a.id
                            INNER JOIN image i ON a.id = i.account_id
                          WHERE s.type=:type
                            AND i.defaultimg = 1
                          LIMIT :limit
                          OFFSET :offset";
                $stmt = $this->db->prepare($query);
            } else {
                // Prepare pagination
                $query = "SELECT COUNT(s.id) AS count FROM service s
                            INNER JOIN account a ON s.account_id = a.id
                            LEFT JOIN image i ON a.id = i.account_id
                          WHERE s.type=?
                            AND IF ((SELECT COUNT(*) FROM image WHERE account_id = i.account_id), i.defaultimg = 1, 1=1)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$formData['type']]);
                $totalItems = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $pagination = new Pagination($totalItems, $currentPage, $itemsPerPage);
                $limit = $pagination->limit();
                $offset = $pagination->offset();

                // Get posts
                $query = "SELECT s.*, i.name, i.alt FROM service s
                            INNER JOIN account a ON s.account_id = a.id
                            LEFT JOIN image i ON a.id = i.account_id
                          WHERE s.type=:type
                            AND IF ((SELECT COUNT(*) FROM image WHERE account_id = i.account_id), i.defaultimg = 1, 1=1)
                          LIMIT :limit
                          OFFSET :offset";
                $stmt = $this->db->prepare($query);
            }
            $stmt->bindParam(':type', $formData['type'], PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //
        // Search by postcode
        //
        } else if ($searchType === 'postcode') {
            $formData['type'] = '';

            // Validate postcode
            $errors = $this->validator->postcode($formData);

            // We get errors so display form again
            if (!empty($errors)) {
                return $this->render('services/search.html', [
                    'input'     => $formData,
                    'errorsAll' => $errors,
                ]);
            }

            // Search for only posts with images
            if ($imageOnly) {
                // Prepare pagination
                $query = "SELECT COUNT(s.id) AS count FROM service s
                             INNER JOIN account a ON s.account_id = a.id
                             INNER JOIN image i ON a.id = i.account_id
                           WHERE s.postcode LIKE ?
                             AND i.defaultimg = 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute(['%'.$formData['postcode'].'%']);
                $totalItems = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $pagination = new Pagination($totalItems, $currentPage, $itemsPerPage);
                $limit = $pagination->limit();
                $offset = $pagination->offset();

                // Get posts
                $query = "SELECT s.*, i.name, i.alt FROM service s
                            INNER JOIN account a ON s.account_id = a.id
                            INNER JOIN image i ON a.id = i.account_id
                          WHERE s.postcode LIKE :postcode
                            AND i.defaultimg = 1
                          LIMIT :limit
                          OFFSET :offset";
                $stmt = $this->db->prepare($query);
            } else {
                // Prepare pagination
                $query = "SELECT COUNT(s.id) AS count FROM service s
                             INNER JOIN account a ON s.account_id = a.id
                             LEFT JOIN image i ON a.id = i.account_id
                           WHERE s.postcode LIKE ?
                              AND IF ((SELECT COUNT(*) FROM image WHERE account_id = i.account_id), i.defaultimg = 1, 1=1)";
                $stmt = $this->db->prepare($query);
                $stmt->execute(['%'.$formData['postcode'].'%']);
                $totalItems = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $pagination = new Pagination($totalItems, $currentPage, $itemsPerPage);
                $limit = $pagination->limit();
                $offset = $pagination->offset();

                $query = "SELECT s.*, i.name, i.alt FROM service s
                            INNER JOIN account a ON s.account_id = a.id
                            LEFT JOIN image i ON a.id = i.account_id
                          WHERE s.postcode LIKE :postcode
                            AND IF ((SELECT COUNT(*) FROM image WHERE account_id = i.account_id), i.defaultimg = 1, 1=1)
                          LIMIT :limit
                          OFFSET :offset";
                $stmt = $this->db->prepare($query);
            }
            $searchParam = '%'.$formData['postcode'].'%';
            $stmt->bindParam(':postcode', $searchParam, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->render('services/search.html', [
            'posts' => $posts,
            'input' => $formData,
            'pages' => $pagination->build(),
            'currentPage' => $currentPage,
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
