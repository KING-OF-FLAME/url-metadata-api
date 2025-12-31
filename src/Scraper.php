<?php
namespace App;

/**
 * Scraper Class
 * Handles the raw HTTP request using cURL with strict shared-hosting optimizations.
 */
class Scraper
{
    private $config;

    /**
     * @param array $config The configuration array from config.php
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Fetch the URL content.
     *
     * @param string $url The target URL.
     * @return array Contains HTML, headers, and connection stats.
     */
    public function fetch($url)
    {
        $ch = curl_init();

        // ---------------------------------------------------------------------
        // Real Browser Headers (Bypass Firewalls)
        // ---------------------------------------------------------------------
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: no-cache',
            'Connection: keep-alive'
        ];

        // ---------------------------------------------------------------------
        // cURL Options
        // ---------------------------------------------------------------------
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,        // Reduced from 5 to 3 for speed
            CURLOPT_ENCODING       => "",       // Handle gzip/deflate
            CURLOPT_USERAGENT      => $this->config['user_agent'],
            CURLOPT_HTTPHEADER     => $headers, // Send the browser headers
            CURLOPT_AUTOREFERER    => true,
            
            // STRICT TIMEOUTS from config.php
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'],
            CURLOPT_TIMEOUT        => $this->config['timeout'],
            
            // SSL Settings (Permissive for Shared Hosting)
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            
            // Optimization: Abort download if file is too big
            CURLOPT_NOPROGRESS     => false,
            CURLOPT_PROGRESSFUNCTION => function(
                $resource, 
                $downloadSize, 
                $downloaded, 
                $uploadSize, 
                $uploaded
            ) {
                // If downloaded bytes exceed limit, abort
                if ($downloaded > $this->config['max_file_size']) {
                    return 1; 
                }
                return 0;
            }
        ];

        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $error   = curl_error($ch);
        $errno   = curl_errno($ch);
        
        $info = curl_getinfo($ch);
        curl_close($ch);

        // ---------------------------------------------------------------------
        // Error Handling
        // ---------------------------------------------------------------------
        
        // Timeout Error (Errno 28) - Handle gracefully
        if ($errno === 28) {
            return [
                'error' => "Request Timed Out (Limit: {$this->config['timeout']}s)",
                'success' => false,
                'http_code' => 408 // Request Timeout
            ];
        }

        // Aborted due to size limit (Errno 42) -> This is actually a SUCCESS for us
        // We likely have enough HTML to parse metadata.
        if ($errno === 42) {
            return [
                'success'        => true,
                'html'           => $content,
                'url'            => $info['url'],
                'http_code'      => $info['http_code'],
                'content_type'   => $info['content_type'],
                'redirect_count' => $info['redirect_count'],
                'total_time'     => $info['total_time'],
                'size'           => $info['size_download'] // FIXED: Use info array instead of local var
            ];
        }

        // Other Errors
        if ($errno) {
            return [
                'error' => "cURL Error: ($errno) $error",
                'success' => false,
                'http_code' => 500
            ];
        }

        // HTTP Errors (404, 500 on target site)
        if ($info['http_code'] >= 400) {
            return [
                'error' => "Target URL returned HTTP " . $info['http_code'],
                'success' => false,
                'http_code' => $info['http_code']
            ];
        }

        return [
            'success'        => true,
            'html'           => $content,
            'url'            => $info['url'],
            'http_code'      => $info['http_code'],
            'content_type'   => $info['content_type'],
            'redirect_count' => $info['redirect_count'],
            'total_time'     => $info['total_time'],
            'size'           => $info['size_download']
        ];
    }
}