<?php
/**
 * Store Controller
 * Handles all public-facing API requests for /api/products
 */
class StoreController extends BaseController {

    private $storeModel;

    public function __construct($db) {
        $this->storeModel = new StoreModels($db);
    }

    /**
     * Handles GET /api/products
     * Gets all available products.
     */
    public function getAllProducts() {
        try {
            $products = $this->storeModel->findAll();
            $this->sendResponse($products, 200);
        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles GET /api/products/:id
     * Gets a single product by its ID.
     */
    public function getProductById($itemId) {
        try {
            // Sanitize the input to make sure it's an integer
            $id = (int)$itemId;

            $product = $this->storeModel->findById($id);

            if ($product) {
                $this->sendResponse($product, 200);
            } else {
                // If no product is found, send a 404
                $this->sendError('Product not found.', 404);
            }
        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }
}