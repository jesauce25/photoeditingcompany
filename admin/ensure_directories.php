<?php
/**
 * Ensure Directories
 * This script ensures that all required directories for the system exist and are writable
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define directories that need to exist
$directories = [
    __DIR__ . '/assets/img/profile',
    __DIR__ . '/../logs'
];

// Function to ensure a directory exists and is writable
function ensure_directory($dir)
{
    // Check if directory exists
    if (!file_exists($dir)) {
        echo "Directory does not exist: {$dir}. Creating...\n";
        // Make sure parent directory exists first
        $parent_dir = dirname($dir);
        if (!file_exists($parent_dir)) {
            if (!ensure_directory($parent_dir)) {
                return false;
            }
        }

        // Try to create the directory
        if (!mkdir($dir, 0777, true)) {
            echo "ERROR: Failed to create directory: {$dir}\n";
            return false;
        }
        echo "Directory created: {$dir}\n";
    }

    // Check if directory is writable
    if (!is_writable($dir)) {
        echo "Directory is not writable: {$dir}. Setting permissions...\n";
        try {
            if (!chmod($dir, 0777)) {
                echo "ERROR: Failed to set permissions on directory: {$dir}\n";
                return false;
            }
            echo "Permissions set on directory: {$dir}\n";
        } catch (Exception $e) {
            echo "ERROR: Exception while setting permissions: " . $e->getMessage() . "\n";
            return false;
        }
    }

    return true;
}

// Process each directory
$all_success = true;
foreach ($directories as $dir) {
    echo "Checking directory: {$dir}\n";
    if (!ensure_directory($dir)) {
        $all_success = false;
    }
}

// Create a test file in the profile image directory to verify it's working
$test_file = __DIR__ . '/assets/img/profile/test_file.txt';
echo "Creating test file: {$test_file}\n";
if (file_put_contents($test_file, 'This is a test file to verify directory permissions.')) {
    echo "Test file created successfully.\n";
    if (unlink($test_file)) {
        echo "Test file removed successfully.\n";
    } else {
        echo "WARNING: Could not remove test file.\n";
    }
} else {
    echo "ERROR: Failed to create test file.\n";
    $all_success = false;
}

if ($all_success) {
    echo "SUCCESS: All directories verified and are working correctly.\n";
} else {
    echo "WARNING: Some directories are not set up correctly. See above for details.\n";
}

// If this was called directly, return success/failure code
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    exit($all_success ? 0 : 1);
}
?>