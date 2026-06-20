<?php
/**
 * INDEX PAGE - Configuration Helper for All Sections
 * 
 * This file ensures all sections have access to proper configuration
 * Load this in the main index.php or at the top of each section file
 * 
 * File: /index/config-helper.php
 * Last Updated: 2026-06-20
 */

// Prevent multiple loads
if (defined('INDEX_CONFIG_LOADED')) {
    return;
}
define('INDEX_CONFIG_LOADED', true);

// Load main configuration
$config_file = dirname(__DIR__) . '/config_infinityfree.php';
if (!file_exists($config_file)) {
    $config_file = dirname(__DIR__) . '/config.php';
}

if (!file_exists($config_file)) {
    die('Configuration file not found. Please check your deployment setup.');
}

require_once $config_file;

// Ensure all required constants are defined
if (!defined('BASE_URL')) {
    die('BASE_URL not defined in configuration.');
}

// Set up commonly used paths for the index page
if (!defined('INDEX_ASSETS_CSS')) define('INDEX_ASSETS_CSS', BASE_URL . '/assets/css');
if (!defined('INDEX_ASSETS_JS')) define('INDEX_ASSETS_JS', BASE_URL . '/assets/js');
if (!defined('INDEX_ASSETS_IMAGES')) define('INDEX_ASSETS_IMAGES', BASE_URL . '/assets/images');
if (!defined('INDEX_SECTION_PATH')) define('INDEX_SECTION_PATH', dirname(__FILE__) . '/sections');

// Global helper function for section files
if (!function_exists('getSectionPath')) {
    function getSectionPath($section_name) {
        return INDEX_SECTION_PATH . '/' . $section_name . '.php';
    }
}

// Helper function to safely include sections
if (!function_exists('includeSection')) {
    function includeSection($section_name) {
        $file = getSectionPath($section_name);
        if (file_exists($file)) {
            include $file;
            return true;
        } else {
            error_log("Warning: Section not found - {$section_name}.php");
            return false;
        }
    }
}

// Export all constants to global scope for easy access in sections
global $app_constants;
$app_constants = [
    'base_url' => BASE_URL,
    'app_name' => APP_NAME,
    'assets_css' => INDEX_ASSETS_CSS,
    'assets_js' => INDEX_ASSETS_JS,
    'assets_images' => INDEX_ASSETS_IMAGES,
    'environment' => ENVIRONMENT,
];
?>
