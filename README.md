Polylang - Translate URL Rewrite Slugs
===============================================================================
WordPress plugin that add rewrite url slugs translation feature to Polylang.

Work in progress ;)

Upgrade notice < 0.3.0
-------------------------------------------------------------------------------
If you used a version prior to 0.3.0, the plugin will probably crash as the structure of the param for the "pll_translated_post_type_rewrite_slugs" filter has changed.

Translate Post Type URLs
-------------------------------------------------------------------------------
Translate rewrite slugs for post types by doing 5 things:
- Remove original extra rewrite rules and permastruct for these post types;
- Translate the extra rewrite rules and permastruct for these post types;
- Stop Polylang from translating rewrite rules for these post types;
- Fix "get_permalink" for these post types.
- Fix "get_post_type_archive_link" for these post types.

To translate a post type rewrite slug, add the filter "pll_translated_post_type_rewrite_slugs" to your functions.php file or your plugin and add the "has_archive" and "rewrite" key has you normally do for the params of the "register_post_type" Wordpress function but add it for each post type and language you want.

Example
~~~php
<?php
add_filter('pll_translated_post_type_rewrite_slugs', function($post_type_translated_slugs) {
	// Add translation for "product" post type.
	$post_type_translated_slugs = array(
		'product' => array(
			'fr' => array(
				'has_archive' => true,
				'rewrite' => array(
					'slug' => 'produit',
				),
			),
			'en' => array(
				'has_archive' => true,
				'rewrite' => array(
					'slug' => 'product',
				),
			),
		),
	);
	return $post_type_translated_slugs;
});
?>
~~~