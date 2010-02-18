<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

header('Content-Type: text/xml');

print '<?xml version="1.0" encoding="UTF-8"?>
';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url>
    <loc><?php echo HTML_DOMAIN; ?></loc>
    <lastmod><?php echo date('Y-m-d'); ?></lastmod>
    <changefreq>hourly</changefreq>
    <priority>1</priority>
</url> 
</urlset>