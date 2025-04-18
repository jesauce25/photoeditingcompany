<?php
/**
 * Configuration file for application settings and limits
 */

// Set higher PHP limits for file uploads
ini_set('upload_max_filesize', '150M');
ini_set('post_max_size', '150M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '256M');

// Define constants for the application
define('MAX_UPLOAD_SIZE', 150 * 1024 * 1024); // 150MB in bytes
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/tiff',
    'image/bmp'
]);

// Define error codes
define('UPLOAD_ERR_FILE_SIZE', 'The file size exceeds the allowed limit.');
define('UPLOAD_ERR_FILE_TYPE', 'The file type is not allowed.');
define('UPLOAD_ERR_PARTIAL', 'The file was only partially uploaded.');
define('UPLOAD_ERR_NO_FILE', 'No file was uploaded.');
define('UPLOAD_ERR_CANT_WRITE', 'Failed to write file to disk.');
define('UPLOAD_ERR_EXTENSION', 'A PHP extension stopped the file upload.');

/**
 * Get human-readable file size
 * 
 * @param int $size File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($size)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// Include the config file in your PHP scripts
// <?php include_once('includes/config.php'); ?>
?>