<?php
// includes/upload_helper.php
// Reusable, safe image-upload handler. Not wired into any page yet (the
// current codebase has no upload feature), but ready to use once product-image
// or avatar upload UI is built, e.g.:
//
//   $result = secure_image_upload($_FILES['product_image'], __DIR__ . '/../uploads/products');
//   if ($result['success']) { $filename = $result['filename']; }
//   else { $error = $result['error']; }

if (!function_exists('secure_image_upload')) {
    /**
     * Validates and stores an uploaded image safely.
     *
     * @param array  $file          A single entry from $_FILES, e.g. $_FILES['product_image']
     * @param string $destDir       Absolute path to the folder to store the file in.
     *                              Must NOT be a publicly executable PHP-enabled folder;
     *                              serve it via a dedicated static path or through a script.
     * @param int    $maxBytes      Max allowed file size in bytes (default 5MB).
     * @return array{success: bool, filename?: string, path?: string, error?: string}
     */
    function secure_image_upload(array $file, string $destDir, int $maxBytes = 5 * 1024 * 1024): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Invalid upload payload.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'No file was uploaded.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'File exceeds the maximum allowed size.'];
            default:
                return ['success' => false, 'error' => 'Upload failed. Please try again.'];
        }

        if ($file['size'] > $maxBytes) {
            return ['success' => false, 'error' => 'File exceeds the maximum allowed size.'];
        }

        // Verify it's genuinely an image by reading its actual content (not the client-supplied name/type,
        // which are trivially spoofable). Also blocks disguised .php/.phtml files with an image extension.
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'error' => 'The uploaded file is not a valid image.'];
        }

        $allowedMimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        $mime = $imageInfo['mime'] ?? '';
        if (!isset($allowedMimeToExt[$mime])) {
            return ['success' => false, 'error' => 'Only JPG, PNG, WEBP, or GIF images are allowed.'];
        }

        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                return ['success' => false, 'error' => 'Could not prepare upload directory.'];
            }
        }

        // Always generate a fresh random filename -- never trust the client-supplied name.
        // This also prevents path traversal and double-extension tricks (e.g. "shell.php.jpg").
        $ext = $allowedMimeToExt[$mime];
        $newFilename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Could not save the uploaded file.'];
        }

        // Defense in depth: strip execute permission on the stored file.
        @chmod($destPath, 0644);

        return ['success' => true, 'filename' => $newFilename, 'path' => $destPath];
    }
}
