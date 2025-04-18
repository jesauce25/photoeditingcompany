<?php
/**
 * Resource Fixer Script
 * 
 * This script handles common 404 errors for CSS, JS, and image resources by:
 * 1. Checking if the requested file exists
 * 2. If not, returning a fallback or generating the resource dynamically
 */

// Set error handler to silent mode for this script
error_reporting(0);

// Function to check and fix common resource paths
function fixResourcePath($path)
{
    // Check if the file exists in the requested location
    if (file_exists($path)) {
        return $path;
    }

    // Common CSS file fixes
    if (strpos($path, 'assets/css/') !== false) {
        $filename = basename($path);
        $alternate_paths = [
            "../assets/css/$filename",
            "../../assets/css/$filename",
            "../../../assets/css/$filename",
            "assets/css/$filename"
        ];

        foreach ($alternate_paths as $alt_path) {
            if (file_exists($alt_path)) {
                return $alt_path;
            }
        }
    }

    // Common JS file fixes
    if (strpos($path, 'assets/js/') !== false) {
        $filename = basename($path);
        $alternate_paths = [
            "../assets/js/$filename",
            "../../assets/js/$filename",
            "../../../assets/js/$filename",
            "assets/js/$filename"
        ];

        foreach ($alternate_paths as $alt_path) {
            if (file_exists($alt_path)) {
                return $alt_path;
            }
        }
    }

    // Common image file fixes
    if (strpos($path, 'assets/img/') !== false) {
        $filename = basename($path);
        $alternate_paths = [
            "../assets/img/$filename",
            "../../assets/img/$filename",
            "../../../assets/img/$filename",
            "assets/img/$filename"
        ];

        foreach ($alternate_paths as $alt_path) {
            if (file_exists($alt_path)) {
                return $alt_path;
            }
        }
    }

    // Return the original path if no fix found
    return $path;
}

// Register script variables for use in the head section
$script = <<<EOT
<script>
    // Resource path fixer
    document.addEventListener('DOMContentLoaded', function() {
        // Find all resources that failed to load
        document.querySelectorAll('img, link[rel="stylesheet"], script').forEach(function(element) {
            element.addEventListener('error', function(e) {
                console.log('Resource failed to load:', e.target.src || e.target.href);
                
                // Try alternative paths based on common patterns
                let originalPath = e.target.src || e.target.href;
                if (!originalPath) return;
                
                // Parse the URL to get the filename
                let filename = originalPath.split('/').pop();
                
                // Try alternative folders
                let alternativePaths = [
                    '../assets/img/' + filename,
                    '../../assets/img/' + filename,
                    '../assets/css/' + filename,
                    '../../assets/css/' + filename,
                    '../assets/js/' + filename,
                    '../../assets/js/' + filename
                ];
                
                // Try each alternative path
                for (let path of alternativePaths) {
                    let testImage = new Image();
                    testImage.onload = function() {
                        // If this loads, update the original element
                        if (e.target.tagName.toLowerCase() === 'img') {
                            e.target.src = path;
                        } else if (e.target.tagName.toLowerCase() === 'link') {
                            let newLink = document.createElement('link');
                            newLink.rel = 'stylesheet';
                            newLink.href = path;
                            document.head.appendChild(newLink);
                        } else if (e.target.tagName.toLowerCase() === 'script') {
                            let newScript = document.createElement('script');
                            newScript.src = path;
                            document.head.appendChild(newScript);
                        }
                        console.log('Resource fixed:', path);
                    };
                    testImage.src = path;
                }
            });
        });
    });
</script>
EOT;

// Output the script
echo $script;
?>