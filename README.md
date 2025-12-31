#  URL Metadata & Preview API

<p align="center">
  <a href="https://github.com/KING-OF-FLAME/url-metadata-api">
    <img src="https://i.ibb.co/yny4dWkD/URL-Metadata-Preview-API.jpg" alt="Logo" width="150" height="150">
  </a>
</p>

<p align="center">
  A blazing fast, lightweight <b>PHP API designed to extract rich metadata</b> from any public URL. It parses OpenGraph tags, technical stats, and SEO details to generate beautiful link previews in milliseconds.
  <br><br>
  <i>(Stop building custom scrapers. Get instant website intelligence.)</i>
</p>

<p align="center">
  <a href="https://www.php.net/">
    <img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  </a>
  <a href="LICENSE">
    <img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" alt="License">
  </a>
  <a href="https://github.com/KING-OF-FLAME">
    <img src="https://img.shields.io/badge/Maintained%3F-yes-green.svg?style=flat-square" alt="Maintenance">
  </a>
</p>

---

##  About The Project

Building link previews (like WhatsApp or Slack) is harder than it looks. Developers waste hours handling redirects, parsing broken HTML, and fighting 502 errors.

**This API solves that.** It acts as a robust middleware that fetches any URL, cleans the data, and returns a standardized JSON response ready for your application.

<p align="center">
  <img src="https://via.placeholder.com/800x400/101010/FFFFFF/?text=API+Response+Preview" alt="API Response Example" width="800">
  <br>
  <i>(Clean, structured data extraction in &lt; 800ms)</i>
</p>

**Key Concepts:**

* **Speed First:** Engineered for shared hosting environments with strict timeout handling.
* **Smart Fallbacks:** If OpenGraph tags are missing, it intelligently finds standard meta tags.
* **Technical Health:** Returns HTTP Status codes, Page Size, and Response Time.

---

##  Features

### Core Analysis Engine

* **1) Social Previews:** Extracts `og:title`, `og:description`, `og:image`.
* **2) Smart Canonicalization:** Converts relative URLs into absolute paths.
* **3) Security & Stability:** Prevents infinite redirects & large payload abuse.
* **4) Performance Metrics:** Response time and page size tracking.

###  Architecture & Tech

* **1) cURL Optimized:** Uses browser‑like headers.
* **2) Fail-Safe Execution:** Prevents server overload using timeout guards.
* **3) No Database Required:** Plug‑and‑play API.

---

##  Tech Stack

* **Core:** PHP 8.0+
* **Network:** cURL
* **Parsing:** DOMDocument & XPath
* **Structure:** Lightweight MVC-style architecture

---

##  Installation Guide

###  Prerequisites

1. PHP 8.0+
2. php-curl enabled
3. php-xml enabled

###  Setup Steps

```bash
git clone https://github.com/KING-OF-FLAME/url-metadata-api.git
```

Move files into your server root:

```
/public_html/api
```

Apache users: `.htaccess` is already included.

Nginx example:

```nginx
root /path/to/project/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Permissions:

```bash
chmod -R 755 logs
```

---

##  Configuration

Edit `config/config.php`

```php
return [
    'connect_timeout' => 3,
    'timeout' => 15,
    'max_file_size' => 2 * 1024 * 1024,
];
```

---

##  API Usage

### Endpoint

```
GET /index.php?url=https://example.com
```

### Sample Response

```json
{
  "meta": {
    "status": 200,
    "timestamp": 1709221234,
    "response_time_ms": 145.2
  },
  "data": {
    "url": "https://github.com",
    "status_code": 200,
    "content_type": "text/html",
    "size_bytes": 45021,
    "meta": {
      "title": "GitHub: Let’s build from here",
      "description": "GitHub is where over 100 million developers shape the future of software...",
      "image": "https://github.githubassets.com/images/modules/site/social-cards/github-social.png",
      "favicon": "https://github.githubassets.com/favicons/favicon.svg",
      "canonical": "https://github.com",
      "lang": "en"
    }
  }
}
```

---

## Folder Structure

```
url-metadata-api/
├── config/
│   └── config.php
├── public/
│   ├── index.php
│   └── .htaccess
├── src/
│   ├── Scraper.php
│   ├── Parser.php
│   └── Response.php
├── logs/
│   └── error.log
└── .gitignore
```

---

## Contributions

1. Fork the repo
2. Create your branch
3. Commit changes
4. Push & open PR

---

## Contact

Github: [KING OF FLAME](https://github.com/KING-OF-FLAME)
Instagram: [yash.developer](https://instagram.com/yash.developer)
