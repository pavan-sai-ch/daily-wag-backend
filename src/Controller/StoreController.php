<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../Utils/S3Uploader.php';

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

    // --- Helper: Validate Image (Improved) ---
    private function validateImage($file) {
        // 1. Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload error code: ' . $file['error'];
        }

        // 2. Check file size (Max 3MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return 'File size exceeds 10MB limit.';
        }

        // 3. Check Extension (More reliable than MIME type for some browsers)
        $filename = strtolower($file['name']);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowedExtensions)) {
            // If extension fails, check MIME type as a backup
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
            if (!in_array($file['type'], $allowedMimes)) {
                return 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP.';
            }
        }

        return true;
    }

    public function getAllProducts() {
        try {
            $products = $this->storeModel->findAll();
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

    /**
     * (Admin) POST /api/admin/products
     * Create a new product.
     */
    public function createProduct() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $data = $_POST; // Expecting Multipart Form Data
            $data = Sanitize::all($data);

            // Validation
            if (empty($data['item_name']) || empty($data['price'])) {
                $this->sendError('Name and Price are required.', 400);
                return;
            }

            // Image Upload
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
            $data['stock'] = $data['stock'] ?? 0; // Default to 0 if empty

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

    /**
     * (Admin) POST /api/admin/products/:id
     * Update a product.
     */
    public function updateProduct($itemId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $data = $_POST;
            $data = Sanitize::all($data);

            // Image Upload (Only if new file is sent)
            if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                $validation = $this->validateImage($_FILES['image']);
                if ($validation !== true) {
                    $this->sendError($validation, 400);
                    return;
                }
                // Upload new image
                $photoUrl = $this->s3->upload($_FILES['image'], 'products');
                if ($photoUrl) {
                    $data['photo_url'] = $photoUrl;

                    // Optional: Delete old image if exists?
                    // Fetch old product to get URL, then $this->s3->delete(oldUrl)
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

    /**
     * (Admin) DELETE /api/admin/products/:id
     */
    public function deleteProduct($itemId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            // Fetch product to delete image first
            $product = $this->storeModel->findById($itemId);
            if ($product && !empty($product['photo_url'])) {
                $this->s3->delete($product['photo_url']);
            }

            $success = $this->storeModel->delete($itemId);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Product deleted.'], 200);
            } else {
                $this->sendError('Failed to delete product. It may be part of an existing order.', 400);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}