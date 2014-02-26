<?php
/*
Plugin Name: Polylang - Translate URL Rewrite Slugs
Plugin URI: https://github.com/KLicheR/wp-polylang-translate-rewrite-slugs
Description: Help translate post types rewrite slugs.
Version: 0.1.1
Author: KLicheR
Author URI: https://github.com/KLicheR
License: GPLv2 or later
*/

/*  Copyright 2013  Kristoffer Laurin-Racicot  (email : kristoffer.lr@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('PLL_TRS_DIR', dirname(__FILE__));
define('PLL_TRS_INC', PLL_TRS_DIR . '/include');

/**
 * Translate rewrite slugs for post types by doing 3 things:
 * - Translate the rewrite rules for these post types;
 * - Stop Polylang from translating rewrite rules for these post types;
 * - Fix "get_permalink" for these post types.
 * - Fix "get_post_type_archive_link" for these post types.
 *
 * To translate a post type rewrite slug, add the filter "pll_translated_post_type_rewrite_slugs"
 * to your functions.php file or your plugin to add infos about translated slugs.
 *
 * Example:
 *  add_filter('pll_translated_post_type_rewrite_slugs', function($post_type_translated_slugs) {
 *  	// Add translation for "my_post_type".
 *  	$post_type_translated_slugs['my_post_type'] = array(	
 *  		'en' => 'my-english/rewrite-slug',
 *  		'fr' => 'my-french/rewrite-slug',
 *  	);
 *  	return $post_type_translated_slugs;
 *  });
 */
class Polylang_Translate_Rewrite_Slugs {
	// Array of custom post types handle by "Polylang - Translate URL Rewrite Slugs".
	public $post_types;
	// Array of taxonomies handle by "Polylang - Translate URL Rewrite Slugs".
	public $taxonomies;

	/**
	 * Contructor.
	 */
	public function __construct() {
		// Initiate the array that will contain the "PLL_TRS_Post_Type" object.
		$this->post_types = array();
		// Initiate the array that will contain the...
		$this->taxonomies = array();

		// If the Polylang plugin is active...
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if (is_plugin_active('polylang/polylang.php')) {
			add_action('init', array($this, 'init_action'), 20);
		}
	}

	/**
	 * Trigger on "init" action.
	 */
	public function init_action() {
		// Post types to handle.
		require_once(PLL_TRS_INC . '/post-type.php');
		$post_type_translated_slugs = apply_filters('pll_translated_post_type_rewrite_slugs', array());
		foreach ($post_type_translated_slugs as $post_type => $translated_slugs) {
			$this->add_post_type($post_type, $translated_slugs);
		}
		// Taxonomies to handle.
		require_once(PLL_TRS_INC . '/taxonomy.php');
		$taxonomy_translated_slugs = apply_filters('pll_translated_taxonomy_rewrite_slugs', array());
		foreach ($taxonomy_translated_slugs as $taxonomy => $translated_slugs) {
			$this->add_taxonomy($taxonomy, $translated_slugs);
		}
		// Fix "get_permalink" for these post types.
		add_filter('post_type_link', array($this, 'post_type_link_filter'), 10, 4);
		// Fix "get_post_type_archive_link" for these post types.
		add_filter('post_type_archive_link', array($this, 'post_type_archive_link_filter'), 10, 2);
		// Fix "get_term_link" for taxonomies.
		add_filter('term_link', array($this, 'term_link_filter'), 10, 3);

		// Fix "PLL_Frontend_Links->get_translation_url".
		add_filter('pll_translation_url', array($this, 'pll_translation_url_filter'), 10, 2);
		// Stop Polylang from translating rewrite rules for these post types.
		add_filter('pll_rewrite_rules', array($this, 'pll_rewrite_rules_filter'));
	}

	/**
	 * Create a "PLL_TRS_Post_Type" and add it to the handled post type list.
	 */
	public function add_post_type($post_type, $translated_slugs) {
		global $polylang;

		$languages = $polylang->model->get_languages_list();
		$post_type_object = get_post_type_object($post_type);
		if (!is_null($post_type_object)) {
			// Add non specified slug translation to post type default.
			foreach ($languages as $language) {
				if (!array_key_exists($language->slug, $translated_slugs)) {
					$translated_slugs[$language->slug] = $post_type_object->rewrite['slug'];
				}
			}
			$this->post_types[$post_type] = new PLL_TRS_Post_Type($post_type_object, $translated_slugs);
		}
	}

	/**
	 * ...
	 */
	public function add_taxonomy($taxonomy, $translated_slugs) {
		global $polylang;

		$languages = $polylang->model->get_languages_list();
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!is_null($taxonomy_object)) {
			// Add non specified slug translation to taxonomy default.
			foreach ($languages as $language) {
				if (!array_key_exists($language->slug, $translated_slugs)) {
					$translated_slugs[$language->slug] = $taxonomy_object->rewrite['slug'];
				}
			}
			$this->taxonomies[$taxonomy] = new PLL_TRS_Taxonomy($taxonomy_object, $translated_slugs);
		}
	}

	/**
	 * Fix "get_permalink" for this post type.
	 */
	public function post_type_link_filter($post_link, $post, $leavename, $sample) {
		global $polylang;

		// We always check for the post language. Otherwise, the current language.
		$post_language = $polylang->model->get_post_language($post->ID);
		if ($post_language) {
			$lang = $post_language->slug;
		} else {
			$lang = pll_default_language();
		}

		// Check if the post type and the language is handle.
		if (isset($this->post_types[$post->post_type]) && isset($this->post_types[$post->post_type]->translated_slugs[$lang])) {
			// Build URL. Lang prefix is already handle.
			return home_url('/'.$this->post_types[$post->post_type]->translated_slugs[$lang].'/'.($leavename?"%$post->post_type%":$post->post_name));
		}

		return $post_link;
	}

	/**
	 * Fix "get_post_type_archive_link" for this post type.
	 */
	public function post_type_archive_link_filter($link, $archive_post_type) {
		if (is_admin()) {
			global $polylang;
			$lang = $polylang->pref_lang->slug;
		} else {
			$lang = pll_current_language();
		}
		
		// Check if the post type and the language is handle.
		if (isset($this->post_types[$archive_post_type]) && isset($this->post_types[$archive_post_type]->translated_slugs[$lang])) {
			// Build URL. Lang prefix is already handle.
			return home_url('/'.$this->post_types[$archive_post_type]->translated_slugs[$lang]);
		}

		return $link;
	}

	/**
	 * Fix "get_term_link" for this taxonomy.
	 */
	public function term_link_filter($termlink, $term, $taxonomy) {
		// Check if the post type is handle.
		if (isset($this->taxonomies[$taxonomy])) {
			global $wp_rewrite, $polylang;

			if ( !is_object($term) ) {
				if ( is_int($term) ) {
					$term = get_term($term, $taxonomy);
				} else {
					$term = get_term_by('slug', $term, $taxonomy);
				}
			}

			if ( !is_object($term) )
				$term = new WP_Error('invalid_term', __('Empty Term'));

			if ( is_wp_error( $term ) )
				return $term;

			// Get the term language.
			$term_language = $polylang->model->get_term_language($term->term_id);
			if ($term_language) {
				$lang = $term_language->slug;
			} else {
				$lang = pll_default_language();
			}
			// Check if the language is handle.
			if (isset($this->taxonomies[$taxonomy]->translated_slugs[$lang])) {
				$taxonomy = $term->taxonomy;

				$termlink = $wp_rewrite->get_extra_permastruct($taxonomy);

				$slug = $term->slug;
				$t = get_taxonomy($taxonomy);

				if ( empty($termlink) ) {
					if ( 'category' == $taxonomy )
						$termlink = '?cat=' . $term->term_id;
					elseif ( $t->query_var )
						$termlink = "?$t->query_var=$slug";
					else
						$termlink = "?taxonomy=$taxonomy&term=$slug";
					$termlink = home_url($termlink);
				} else {
					if ( $t->rewrite['hierarchical'] ) {
						$hierarchical_slugs = array();
						$ancestors = get_ancestors($term->term_id, $taxonomy);
						foreach ( (array)$ancestors as $ancestor ) {
							$ancestor_term = get_term($ancestor, $taxonomy);
							$hierarchical_slugs[] = $ancestor_term->slug;
						}
						$hierarchical_slugs = array_reverse($hierarchical_slugs);
						$hierarchical_slugs[] = $slug;
						$termlink = $this->taxonomies[$taxonomy]->translated_slugs[$lang] . '/' . implode('/', $hierarchical_slugs);
					} else {
						$termlink = $this->taxonomies[$taxonomy]->translated_slugs[$lang] . '/' . $slug;
					}
					$termlink = home_url( user_trailingslashit($termlink, 'category') );
				}
				// Back Compat filters.
				if ( 'post_tag' == $taxonomy )
					$termlink = apply_filters( 'tag_link', $termlink, $term->term_id );
				elseif ( 'category' == $taxonomy )
					$termlink = apply_filters( 'category_link', $termlink, $term->term_id );
			}
		}

		return $termlink;
	}

	/**
	 * Fix "PLL_Frontend_Links->get_translation_url()".
	 */
	public function pll_translation_url_filter($url, $lang) {
		global $wp_query;

		if (is_category()) {
			$term = get_category_by_slug($wp_query->get('category_name'));
			$translated_term = get_term(pll_get_term($term->term_id, $lang), $term->taxonomy);
			return home_url('/'.$lang.'/'.$translated_term->slug);
		}
		elseif (is_archive()) {
			$post_type = $wp_query->query_vars['post_type'];
			if (isset($this->post_types[$post_type])) {
				return home_url('/'.$lang.'/'.$this->post_types[$post_type]->translated_slugs[$lang]);
			}
			return $url;
		}

		return $url;
	}

	/**
	 * Stop Polylang from translating rewrite rules for these post types.
	 */
	public function pll_rewrite_rules_filter($rules) {
		// We don't want Polylang to take care of these rewrite rules groups.
		foreach (array_keys($this->post_types) as $post_type) {
			$rule_key = array_search($post_type, $rules);
			if ($rule_key) {
				unset($rules[$rule_key]);
			}
		}
		foreach (array_keys($this->taxonomies) as $taxonomy) {
			$rule_key = array_search($taxonomy, $rules);
			if ($rule_key) {
				unset($rules[$rule_key]);
			}
		}

		return $rules;
	}
}
new Polylang_Translate_Rewrite_Slugs();
