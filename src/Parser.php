<?php
namespace App;

use DOMDocument;
use DOMXPath;

/**
 * Parser Class
 * Extracts metadata from raw HTML using DOMDocument and XPath.
 */
class Parser
{
    private $html;
    private $url; // The final URL (after redirects) used to resolve relative links
    private $xpath;
    private $dom;

    /**
     * @param string $html The raw HTML content.
     * @param string $url  The base URL for resolving relative links.
     */
    public function __construct($html, $url)
    {
        $this->html = $html;
        $this->url = $url;
        $this->initializeDom();
    }

    /**
     * Load HTML into DOMDocument safely.
     */
    private function initializeDom()
    {
        $this->dom = new DOMDocument();
        
        // Suppress warnings for malformed HTML (very common on the web)
        libxml_use_internal_errors(true);
        
        // Load HTML, forcing UTF-8
        // The mb_convert_encoding part ensures we don't have charset issues
        $loaded = $this->dom->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'));
        
        libxml_clear_errors();
        
        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * Main method to get all metadata.
     * @return array
     */
    public function getMetadata()
    {
        return [
            'title'         => $this->getTitle(),
            'description'   => $this->getDescription(),
            'image'         => $this->getImage(),
            'favicon'       => $this->getFavicon(),
            'canonical'     => $this->getCanonical(),
            'lang'          => $this->getLanguage(),
            'charset'       => $this->getCharset(),
            'keywords'      => $this->getMetaContent('keywords'),
            'author'        => $this->getMetaContent('author'),
        ];
    }

    // -------------------------------------------------------------------------
    // Extraction Logic
    // -------------------------------------------------------------------------

    private function getTitle()
    {
        // Priority: OG > Twitter > <title>
        $val = $this->getMetaContent('og:title') 
            ?? $this->getMetaContent('twitter:title') 
            ?? $this->getTagValue('title');

        return $this->cleanText($val);
    }

    private function getDescription()
    {
        // Priority: OG > Twitter > meta description
        $val = $this->getMetaContent('og:description') 
            ?? $this->getMetaContent('twitter:description') 
            ?? $this->getMetaContent('description');

        return $this->cleanText($val);
    }

    private function getImage()
    {
        // Priority: OG > Twitter > <link rel="image_src"> > first <img> tag
        $img = $this->getMetaContent('og:image') 
            ?? $this->getMetaContent('twitter:image')
            ?? $this->getAttribute('link[@rel="image_src"]', 'href');

        // Fallback: Find first significant image
        if (!$img) {
            $nodes = $this->xpath->query('//img[@src]');
            if ($nodes->length > 0) {
                $img = $nodes->item(0)->getAttribute('src');
            }
        }

        return $this->resolveUrl($img);
    }

    private function getFavicon()
    {
        // Look for typical favicon rel tags
        $query = '//link[contains(@rel, "icon") or @rel="shortcut icon"]';
        $href = $this->getAttribute($query, 'href');
        
        // Fallback: default /favicon.ico at root
        if (!$href) {
            $parsed = parse_url($this->url);
            return $parsed['scheme'] . '://' . $parsed['host'] . '/favicon.ico';
        }

        return $this->resolveUrl($href);
    }
    
    private function getCanonical()
    {
        $can = $this->getAttribute('//link[@rel="canonical"]', 'href');
        return $can ? $this->resolveUrl($can) : $this->url;
    }

    private function getLanguage()
    {
        $nodes = $this->xpath->query('//html[@lang]');
        return ($nodes->length > 0) ? $nodes->item(0)->getAttribute('lang') : null;
    }

    private function getCharset()
    {
        // <meta charset="utf-8">
        $nodes = $this->xpath->query('//meta[@charset]');
        if ($nodes->length > 0) {
            return $nodes->item(0)->getAttribute('charset');
        }
        // <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Get content attribute from meta tags.
     * Supports both name="..." and property="..." (for OG tags)
     */
    private function getMetaContent($name)
    {
        $query = "//meta[@name='$name' or @property='$name']";
        $nodes = $this->xpath->query($query);
        
        if ($nodes->length > 0) {
            return $nodes->item(0)->getAttribute('content');
        }
        return null;
    }

    private function getTagValue($tag)
    {
        $nodes = $this->xpath->query("//$tag");
        return ($nodes->length > 0) ? $nodes->item(0)->nodeValue : null;
    }

    private function getAttribute($xpathQuery, $attrName)
    {
        $nodes = $this->xpath->query($xpathQuery);
        return ($nodes->length > 0) ? $nodes->item(0)->getAttribute($attrName) : null;
    }

    private function cleanText($text)
    {
        return $text ? trim(preg_replace('/\s+/', ' ', $text)) : null;
    }

    /**
     * Resolve relative URLs (e.g. "/foo.png") to absolute (e.g. "http://site.com/foo.png")
     */
    private function resolveUrl($rel)
    {
        if (empty($rel)) return null;
        
        // Return if already absolute
        if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

        // Simple resolution logic
        $base = parse_url($this->url);
        $scheme = isset($base['scheme']) ? $base['scheme'] : 'http';
        $host = isset($base['host']) ? $base['host'] : '';
        $path = isset($base['path']) ? $base['path'] : '';

        // If starts with // (protocol relative)
        if (substr($rel, 0, 2) === '//') {
            return $scheme . ':' . $rel;
        }

        // If starts with / (root relative)
        if ($rel[0] === '/') {
            return $scheme . '://' . $host . $rel;
        }

        // Path relative (simple implementation)
        return $scheme . '://' . $host . dirname($path) . '/' . $rel;
    }
}