<?php
// Load the Composer autoloader so we can use the AWS SDK
require_once __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Uploader {
    private $s3;
    private $bucket;

    public function __construct() {
        // Initialize the S3 Client using credentials from .env
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region'  => getenv('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ]
        ]);

        $this->bucket = getenv('AWS_BUCKET');
    }

    /**
     * Uploads a file to S3.
     * * @param array $file The $_FILES['key'] array from the form request.
     * @param string $folder The folder name in the bucket (e.g., 'pets', 'products').
     * @return string|false The public URL of the uploaded file, or false on failure.
     */
    public function upload($file, $folder = 'uploads') {
        // 1. Validate file error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("S3 Upload Error: File upload failed with code " . $file['error']);
            return false;
        }

        // 2. Generate a unique filename to prevent overwriting
        // e.g., pets/654a3b2c_17000000.jpg
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid() . '_' . time() . '.' . $extension;
        $key = $folder . '/' . $uniqueName;

        try {
            // 3. SEND THE FILE TO AWS
            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SourceFile' => $file['tmp_name'],
                'ContentType' => $file['type'] // Important so the browser treats it as an image
                // 'ACL' => 'public-read' // Optional: Only if your bucket settings require per-object ACLs
            ]);

            // Return the URL of the uploaded file
            return $result['ObjectURL'];

        } catch (AwsException $e) {
            error_log("S3 Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a file from S3 given its full URL.
     * @param string $url The full S3 object URL (e.g. https://bucket.s3.../folder/file.jpg)
     * @return bool True on success.
     */
    public function delete($url) {
        if (empty($url)) return true; // Nothing to delete

        try {
            // 1. Extract the "Key" from the URL.
            // parse_url path gives: /folder/file.jpg
            $path = parse_url($url, PHP_URL_PATH);

            // Remove the leading slash to get the Key: folder/file.jpg
            $key = ltrim($path, '/');

            // 2. Send Delete Command
            $this->s3->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key
            ]);

            return true;
        } catch (AwsException $e) {
            error_log("S3 Delete Error: " . $e->getMessage());
            // We return true even on error so the database delete operation can proceed
            return false;
        }
    }
}