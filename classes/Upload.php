<?php
/**
 * Upload - Secure File Upload Handler
 * Validates file sizes, MIME types, randomizes file names, and isolates uploads.
 */

class Upload {
    private const MAX_IMAGE_SIZE = 5242880; // 5 MB
    private const MAX_MODEL_SIZE = 10485760; // 10 MB

    private const ALLOWED_IMAGE_TYPES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    private const ALLOWED_MODEL_EXTENSIONS = ['glb', 'gltf', 'usdz'];

    /**
     * Upload product 2D image
     */
    public static function image(array $file, string $subDir = 'products'): array {
        if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::getErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE)];
        }

        if ($file['size'] > self::MAX_IMAGE_SIZE) {
            return ['success' => false, 'message' => 'Image exceeds maximum allowed size of 5MB.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!array_key_exists($ext, self::ALLOWED_IMAGE_TYPES) || !in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true)) {
            return ['success' => false, 'message' => 'Invalid image format. Allowed: JPG, PNG, WEBP.'];
        }

        $targetDir = ROOT_PATH . '/assets/uploads/' . trim($subDir, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $targetDir . '/' . $randomName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Failed to save uploaded image.'];
        }

        return [
            'success'  => true,
            'filename' => $randomName,
            'path'     => 'assets/uploads/' . trim($subDir, '/') . '/' . $randomName
        ];
    }

    /**
     * Upload 3D model (.glb, .gltf, .usdz)
     */
    public static function model3D(array $file, string $subDir = 'models'): array {
        if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::getErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE)];
        }

        if ($file['size'] > self::MAX_MODEL_SIZE) {
            return ['success' => false, 'message' => '3D model exceeds maximum allowed size of 10MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_MODEL_EXTENSIONS, true)) {
            return ['success' => false, 'message' => 'Invalid 3D model format. Allowed: .glb, .gltf, .usdz.'];
        }

        // Validate basic binary header for .glb
        if ($ext === 'glb') {
            $handle = fopen($file['tmp_name'], 'rb');
            $header = fread($handle, 4);
            fclose($handle);
            if ($header !== 'glTF') {
                return ['success' => false, 'message' => 'Corrupt or invalid .glb binary file.'];
            }
        }

        $targetDir = ROOT_PATH . '/assets/uploads/' . trim($subDir, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $targetDir . '/' . $randomName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Failed to save uploaded 3D model.'];
        }

        return [
            'success'  => true,
            'filename' => $randomName,
            'path'     => 'assets/uploads/' . trim($subDir, '/') . '/' . $randomName
        ];
    }

    private static function getErrorMessage(int $code): string {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds upload size limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            default => 'Unknown upload error.'
        };
    }
}
