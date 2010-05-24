<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_sitemap()
{
	return array(
		'name' => 'Site Map',
		'description' => 'Prints out a list of pages that Bots like Google should access.',
		'privilage' => 1,
		'path' => __FILE__,
		'template' => false
	);
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_sitemap($request)
{
	
	header('Content-Type: text/xml');
	
	print '<?xml version="1.0" encoding="UTF-8"?>
	';
	?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc><?php echo url('', true, true); ?></loc>
		<lastmod><?php echo date('Y-m-d'); ?></lastmod>
		<changefreq>hourly</changefreq>
		<priority>1</priority>
	</url> 
	</urlset>
	<?php

}
