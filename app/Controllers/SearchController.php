<?php

namespace COMP1687\CW\Controllers;

use PDO;

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
        $searchType = ($_GET['by'] === 'postcode') ? 'postcode' : 'type';
        $formData = $_POST;

        // Search by sitter type
        if ($searchType === 'type') {
            $stmt = $this->db->prepare("SELECT * FROM service WHERE type=?");
            $stmt->execute([$formData['type']]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Search by postcode
        if ($searchType === 'postcode') {
            $stmt = $this->db->prepare("SELECT * FROM service WHERE type=?");
            $stmt->execute([$formData['type']]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        return $this->render('services/view-search.html', [
            'service' => $service,
        ]);
    }
}
