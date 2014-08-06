<?php
/**
 * Post type related object.
 *
 * @todo Filter "extra_rules_top" after Polylang has create his translated version
 * of them. See $this->replace_extra_rules_top.
 */
class PLL_TRS_Post_Type {
	// The post type object.
	public $post_type_object;
	// The translated rewrite slugs.
	public $translated_slugs;

	/**
	 * Contructor.
	 */
	public function __construct($post_type_object, $translated_slugs) {
		$this->post_type_object = $post_type_object;
		$this->translated_slugs = $translated_slugs;

		// Replace "extra_rules_top", for archive.
		$this->replace_extra_rules_top();
		// Replace "permastruct", for single.
		$this->replace_permastruct();

		// Filter extra rewrite rules made by Polylang when we added "extra_rules_top".
		add_filter('rewrite_rules_array', array($this, 'rewrite_rules_array_filter'), 15);
	}

	/**
	 * Replace "extra_rules_top", for archive.
	 *
	 * This code simulate the code used in WordPress function "register_post_type"
	 * and execute it for each language. After that, Polylang will consider these
	 * rules like "individual" post types (one by lang) and will create the appropriated
	 * rules.
	 *
	 * @see Extra rules from WordPress (wp-include/post.php, register_post_type()).
	 */
	private function replace_extra_rules_top() {
		global $polylang, $wp_rewrite;

		$post_type = $this->post_type_object->name;

		if ( $this->post_type_object->has_archive ) {
			// Remove the original extra rules.
			$archive_slug = $this->post_type_object->has_archive === true ? $this->post_type_object->rewrite['slug'] : $this->post_type_object->has_archive;
			if ( $this->post_type_object->rewrite['with_front'] )
				$archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
			else
				$archive_slug = $wp_rewrite->root . $archive_slug;

			unset($wp_rewrite->extra_rules_top["{$archive_slug}/?$"]);
			if ( $this->post_type_object->rewrite['feeds'] && $wp_rewrite->feeds ) {
				$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
				unset($wp_rewrite->extra_rules_top["{$archive_slug}/feed/$feeds/?$"]);
				unset($wp_rewrite->extra_rules_top["{$archive_slug}/$feeds/?$"]);
			}
			if ( $this->post_type_object->rewrite['pages'] )
				unset($wp_rewrite->extra_rules_top["{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$"]);

			// Add the translated extra rules.
			foreach ($this->translated_slugs as $lang => $translated_slug) {
				/**
				 * @todo: Add support for "has_archive" slug -> https://github.com/KLicheR/wp-polylang-translate-rewrite-slugs/issues/2.
				 * Original line:
				 *   $archive_slug = $this->post_type_object->has_archive === true ? $this->post_type_object->rewrite['slug'] : $this->post_type_object->has_archive;
				 */
				$archive_slug = $translated_slug;
				if ( $this->post_type_object->rewrite['with_front'] )
					$archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
				else
					$archive_slug = $wp_rewrite->root . $archive_slug;

				add_rewrite_rule( "{$archive_slug}/?$", "index.php?post_type=$post_type", 'top' );
				if ( $this->post_type_object->rewrite['feeds'] && $wp_rewrite->feeds ) {
					$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
					add_rewrite_rule( "{$archive_slug}/feed/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top' );
					add_rewrite_rule( "{$archive_slug}/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top' );
				}
				if ( $this->post_type_object->rewrite['pages'] )
					add_rewrite_rule( "{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", "index.php?post_type=$post_type" . '&paged=$matches[1]', 'top' );
			}
		}
	}

	/**
	 * Replace "permastruct", for single.
	 *
	 * This code simulate the code used in WordPress function "register_post_type"
	 * and execute it for each language.
	 *
	 * @see Permstruct from WordPress (wp-include/post.php, register_post_type()).
	 */
	private function replace_permastruct() {
		global $polylang, $wp_rewrite;

		$post_type = $this->post_type_object->name;

		$permastruct_args = $this->post_type_object->rewrite;
		$permastruct_args['feed'] = $permastruct_args['feeds'];

		// Remove the original permastructs.
		unset($wp_rewrite->extra_permastructs[$post_type]);
		// Add the translated permastructs.
		foreach ($this->translated_slugs as $lang => $translated_slug) {
			// If "Hide URL language information for default language" option is
			// set to true the rules has to be different for the default language.
			if ($polylang->options['hide_default'] && $lang == pll_default_language()) {
				add_permastruct( $post_type.'_'.$lang, "{$translated_slug}/%$post_type%", $permastruct_args );
			} else {
				add_permastruct( $post_type.'_'.$lang, "%language%/{$translated_slug}/%$post_type%", $permastruct_args );
			}
		}
	}

	/**
	 * Filter extra rewrite rules, for archives.
	 */
	public function rewrite_rules_array_filter($rewrite_rules) {
		global $polylang, $wp_rewrite;
		// echo('<pre>');var_dump($wp_rewrite);exit;
		// echo('<pre>');var_dump($rewrite_rules);exit;

		// From Polylang (include/links-directory.php:180).
		$languages = $polylang->model->get_languages_list(array('fields' => 'slug'));

		if ($polylang->options['hide_default'])
			$languages = array_diff($languages, array($polylang->options['default_lang']));

		if (!empty($languages))
			$polylang_slug = $wp_rewrite->root . ($polylang->options['rewrite'] ? '' : 'language/') . '('.implode('|', $languages).')/';

		// Extra rules from WordPress (wp-include/post.php:1309).
		$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';

		$extra_rules_ends = array(
			"/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$",
			"/$feeds/?$",
			"/feed/$feeds/?$",
			"/?$",
		);
		foreach ($this->translated_slugs as $lang => $translated_slug) {
			$extra_rules_base = "{$polylang_slug}{$translated_slug}";
			foreach ($extra_rules_ends as $extra_rule_end) {
				$extra_rule_key = $extra_rules_base.$extra_rule_end;
				// echo('<pre>');var_dump($extra_rule_key);//exit;
				// If the rule exists.
				if (array_key_exists($extra_rule_key, $rewrite_rules)) {
					// Remove the rule.
					// unset($rewrite_rules[$extra_rule_key]);
				}
			}
		}
		// echo('<pre>');var_dump($rewrite_rules);exit;

		return $rewrite_rules;
	}
}
