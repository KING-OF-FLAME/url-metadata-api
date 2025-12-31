<?php
// -----------------------------------------------------------------------------
// 1. SAFETY & PERFORMANCE LIMITS (Critical for Shared Hosting)
// -----------------------------------------------------------------------------
// Force script to die after 20 seconds to prevent 502 Bad Gateway errors.
// This gives PHP 5 seconds of "breathing room" after our 15s cURL timeout.
set_time_limit(20); 

$startTime = microtime(true);

// -----------------------------------------------------------------------------
// 2. Load Dependencies
// -----------------------------------------------------------------------------
// We use require_once to ensure we have the latest updated files.
$config = require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Scraper.php';
require_once __DIR__ . '/../src/Parser.php';

use App\Response;
use App\Scraper;
use App\Parser;

// -----------------------------------------------------------------------------
// 3. Error Handling
// -----------------------------------------------------------------------------
if ($config['debug_mode']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// -----------------------------------------------------------------------------
// 4. Validate Request
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method Not Allowed. Use GET.', 405);
}

// Get 'url' Parameter
if (!isset($_GET['url']) || empty($_GET['url'])) {
    Response::error('Missing required parameter: url', 400);
}

$targetUrl = trim($_GET['url']);

// Add protocol if missing
if (!preg_match("~^(?:f|ht)tps?://~i", $targetUrl)) {
    $targetUrl = "http://" . $targetUrl;
}

// Validate URL format
if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
    Response::error('Invalid URL format provided.', 400);
}

// -----------------------------------------------------------------------------
// 5. Execute Scraper (The Heavy Lifting)
// -----------------------------------------------------------------------------
try {
    $scraper = new Scraper($config);
    $rawResult = $scraper->fetch($targetUrl);

    // Check for Scraper network errors (DNS, Timeout, etc)
    if (!$rawResult['success']) {
        // If it was a timeout (408) or server error (500+), pass that code along
        $statusCode = isset($rawResult['http_code']) ? $rawResult['http_code'] : 502;
        Response::error($rawResult['error'], $statusCode);
    }

    // -------------------------------------------------------------------------
    // 6. Execute Parser (The Logic)
    // -------------------------------------------------------------------------
    // Only parse if we actually got HTML content
    if (!empty($rawResult['html'])) {
        $parser = new Parser($rawResult['html'], $rawResult['url']);
        $metadata = $parser->getMetadata();
    } else {
        // Fallback for non-HTML content (like images or PDFs)
        $metadata = [];
    }

    // -------------------------------------------------------------------------
    // 7. Construct Final Response
    // -------------------------------------------------------------------------
    $responsePayload = [
        'url' => $rawResult['url'], // The final URL (after redirects)
        'status_code' => $rawResult['http_code'],
        'content_type' => $rawResult['content_type'],
        'size_bytes' => $rawResult['size'], // Uses the fixed size from Scraper
        'redirect_count' => $rawResult['redirect_count'],
        'meta' => $metadata
    ];

    // Send JSON Success
    Response::json($responsePayload, 200, $startTime);

} catch (Exception $e) {
    // Catch-all for unexpected crashes
    error_log("Critical Error: " . $e->getMessage());
    Response::error('Internal Server Error', 500);
}