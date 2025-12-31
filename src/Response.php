<?php
namespace App;

/**
 * Response Helper Class
 * Handles JSON output formatting and HTTP status codes.
 */
class Response
{
    /**
     * Send a JSON response and exit.
     *
     * @param mixed $data       The payload to send (metadata or error message).
     * @param int   $statusCode HTTP status code (default 200).
     * @param float $startTime  Timestamp when the script started (for performance tracking).
     */
    public static function json($data, $statusCode = 200, $startTime = null)
    {
        // Clear any previous output to ensure clean JSON
        if (ob_get_length()) {
            ob_clean();
        }

        // Set Headers
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *"); // CORS friendly
        header("Access-Control-Allow-Methods: GET");
        
        http_response_code($statusCode);

        // Calculate execution time if start time is provided
        $meta = [
            'status' => $statusCode,
            'timestamp' => time()
        ];

        if ($startTime) {
            // Calculate time in milliseconds
            $executionTime = (microtime(true) - $startTime) * 1000;
            $meta['response_time_ms'] = round($executionTime, 2);
        }

        // Structure the final response
        $response = [
            'meta' => $meta,
            'data' => $data
        ];

        // Output JSON
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Kill the script immediately after sending response
        exit;
    }

    /**
     * Helper for error responses
     */
    public static function error($message, $statusCode = 400)
    {
        self::json(['error' => $message], $statusCode);
    }
}