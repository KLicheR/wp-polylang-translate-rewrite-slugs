<?php
/**
 * Post type related object.
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

		// Translate the rewrite rules of the post type.
		add_filter($this->post_type_object->name.'_rewrite_rules', array($this, 'post_type_rewrite_rules_filter'));
		// Translate extra rewrite rules set when the post type is registered, for archives.
		add_filter('rewrite_rules_array', array($this, 'rewrite_rules_array_filter'), 15);
	}

	/**
	 * Translate the rewrite rules.
	 */
	public function post_type_rewrite_rules_filter($rewrite_rules) {
		$translated_rules = array();

		// For each lang.
		foreach ($this->translated_slugs as $lang => $translated_slug) {
			// For each rule.
			foreach ($rewrite_rules as $rule_key => $rule_value) {
				// Shift the matches up cause "lang" will be the first.
				$translated_rules['('.$lang.')/'.str_replace($this->post_type_object->rewrite['slug'], $translated_slug, $rule_key)] = str_replace(
					array('[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]'),
					array('[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]'),
					$rule_value
				);
			}
		}

		return $translated_rules;
	}

	/**
	 * Translate extra rewrite rules set when the post type is registered, for archives.
	 */
	public function rewrite_rules_array_filter($rewrite_rules) {
		global $polylang, $wp_rewrite;

		// From Polylang (include/links-directory.php:180).
		$languages = $polylang->model->get_languages_list(array('fields' => 'slug'));
		if ($polylang->options['hide_default'])
			$languages = array_diff($languages, array($polylang->options['default_lang']));

		if (!empty($languages))
			$polylang_slug = $wp_rewrite->root . ($polylang->options['rewrite'] ? '' : 'language/') . '('.implode('|', $languages).')/';

		// Extra rules from WordPress (wp-include/post.php:1309).
		$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
		$extra_rules_base = "{$polylang_slug}{$this->post_type_object->rewrite['slug']}";
		$extra_rules_ends = array(
			"/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$",
			"/$feeds/?$",
			"/feed/$feeds/?$",
			"/?$",
		);

		foreach ($extra_rules_ends as $extra_rule_end) {
			$extra_rule_key = $extra_rules_base.$extra_rule_end;
			// If the rule exists.
			if (array_key_exists($extra_rule_key, $rewrite_rules)) {
				// Save the value.
				$extra_rule_value = $rewrite_rules[$extra_rule_key];
				// Remove the rule.
				unset($rewrite_rules[$extra_rule_key]);
				// Recreated it for each translation.
				$translated_rules = array();
				foreach ($this->translated_slugs as $lang => $translated_slug) {
					$translated_rules["({$lang})/{$translated_slug}".$extra_rule_end] = $extra_rule_value;
				}
				// Add them to the top of rewrite rules.
				$rewrite_rules = array_merge($translated_rules, $rewrite_rules);
			}
		}

		return $rewrite_rules;
	}
}