<?php
/**
 * URL Metadata API Configuration
 * * This file contains all environment settings, scraper limits,
 * and security configurations.
 */

// Prevent direct access if the script is not running through the index
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access forbidden.');
}

return [
    // -------------------------------------------------------------------------
    // General Settings
    // -------------------------------------------------------------------------
    'app_name'    => 'UrlMetadataAPI',
    'version'     => '1.0.0',
    'debug_mode'  => false, // Set to true only during development
    
    // -------------------------------------------------------------------------
    // Scraper Limits (Shared Hosting Safety)
    // -------------------------------------------------------------------------
    // Maximum time (seconds) to wait for a connection
    'connect_timeout' => 5, 
    
    // Maximum time (seconds) to allow the whole request to take
    'timeout'         => 10,
    
    // Max download size in bytes (2MB) - We only need the HTML head/body
    // This prevents your server from crashing if a user inputs a 10GB file URL.
    'max_file_size'   => 2 * 1024 * 1024, 
    
    // -------------------------------------------------------------------------
    // Spoofing & Headers
    // -------------------------------------------------------------------------
    // We pretend to be a modern browser to avoid 403 Forbidden errors
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
    
    // -------------------------------------------------------------------------
    // RapidAPI Security
    // -------------------------------------------------------------------------
    // If you want to restrict calls so they ONLY come from RapidAPI servers,
    // you can verify specific headers here later. For now, we leave this open.
    'require_rapidapi_header' => false,
];