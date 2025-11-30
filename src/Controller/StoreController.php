<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../Utils/S3Uploader.php';
require_once __DIR__ . '/../models/StoreModels.php';

/**
 * Store Controller
 */
class StoreController extends BaseController {

    private $storeModel;
    private $s3;

    public function __construct($db) {
        $this->storeModel = new StoreModels($db);
        $this->s3 = new S3Uploader();
    }

    // --- Helper: Validate Image ---
    private function validateImage($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) return 'File upload error code: ' . $file['error'];

        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) return 'File size exceeds 10MB limit.';

        $filename = strtolower($file['name']);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowedExtensions)) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
            if (!in_array($file['type'], $allowedMimes)) return 'Invalid file type.';
        }
        return true;
    }

    /**
     * GET /api/products?limit=10&offset=0&search=...
     * Fetches products with pagination AND search support.
     */
    public function getAllProducts() {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $search = isset($_GET['search']) ? Sanitize::string($_GET['search']) : '';

            // Pass search term to model
            $products = $this->storeModel->findAll($limit, $offset, $search);

            $this->sendResponse($products, 200);
        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function getProductById($itemId) {
        try {
            $product = $this->storeModel->findById((int)$itemId);
            if ($product) {
                $this->sendResponse($product, 200);
            } else {
                $this->sendError('Product not found.', 404);
            }
        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function createProduct() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $data = $_POST;
            $data = Sanitize::all($data);

            if (empty($data['item_name']) || empty($data['price'])) {
                $this->sendError('Name and Price are required.', 400);
                return;
            }

            $photoUrl = null;
            if (isset($_FILES['image'])) {
                $validation = $this->validateImage($_FILES['image']);
                if ($validation !== true) {
                    $this->sendError($validation, 400);
                    return;
                }
                $photoUrl = $this->s3->upload($_FILES['image'], 'products');
            }

            $data['photo_url'] = $photoUrl;
            $data['stock'] = $data['stock'] ?? 0;

            $newId = $this->storeModel->create($data);

            if ($newId) {
                $this->sendResponse(['status' => 'success', 'message' => 'Product created.', 'item_id' => $newId], 201);
            } else {
                $this->sendError('Failed to create product.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    public function updateProduct($itemId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $data = $_POST;
            $data = Sanitize::all($data);

            if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                $validation = $this->validateImage($_FILES['image']);
                if ($validation !== true) {
                    $this->sendError($validation, 400);
                    return;
                }
                $photoUrl = $this->s3->upload($_FILES['image'], 'products');
                if ($photoUrl) {
                    $data['photo_url'] = $photoUrl;
                }
            }

            $success = $this->storeModel->update($itemId, $data);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Product updated.'], 200);
            } else {
                $this->sendError('Failed to update product.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    public function deleteProduct($itemId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $product = $this->storeModel->findById($itemId);
            if ($product && !empty($product['photo_url'])) {
                $this->s3->delete($product['photo_url']);
            }

            $success = $this->storeModel->delete($itemId);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Product deleted.'], 200);
            } else {
                $this->sendError('Failed to delete product.', 400);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}