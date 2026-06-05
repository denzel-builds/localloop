<?php
// ============================================================
// LocalLoop — Cloudinary Configuration
// Sign up free at cloudinary.com → Dashboard → copy values
// ============================================================

define('CLOUDINARY_CLOUD_NAME', 'dzrscvanm');   // e.g. dxyz123abc
define('CLOUDINARY_API_KEY',    '961256533541223');       // 15-digit number
define('CLOUDINARY_API_SECRET', '12zkxWkAweLBQEStDQIA5MFS_x4');    // long random string
define('CLOUDINARY_FOLDER',     'localloop');          // folder inside Cloudinary

/**
 * Upload an image file to Cloudinary via cURL.
 * Returns the secure CDN URL on success, null on failure.
 *
 * @param string $file_tmp   Temp path of uploaded file ($_FILES['x']['tmp_name'])
 * @param string $file_name  Original filename (for reference)
 * @return string|null       Full Cloudinary HTTPS URL, or null on failure
 */
function uploadToCloudinary(string $file_tmp, string $file_name): ?string {
    $timestamp = time();
    $folder    = CLOUDINARY_FOLDER;

    // Build the signature: alphabetical params + secret
    $params_str = "folder=$folder&timestamp=$timestamp";
    $signature  = sha1($params_str . CLOUDINARY_API_SECRET);

    $endpoint = "https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/upload";

    $post_data = [
        'file'      => new CURLFile($file_tmp, mime_content_type($file_tmp), $file_name),
        'api_key'   => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder'    => $folder,
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // required on some shared hosts

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return null;

    $result = json_decode($response, true);
    return $result['secure_url'] ?? null;
}

/**
 * Validate an uploaded image before sending to Cloudinary.
 * Returns an error message string, or empty string if valid.
 */
function validateImage(array $file): string {
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_mb  = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK)          return 'Upload error — please try again.';
    if (!in_array($file['type'], $allowed))         return 'Only JPG, PNG, WebP or GIF images allowed.';
    if ($file['size'] > $max_mb)                   return 'Image must be under 5 MB.';
    return '';
}
?>