<?php
/**
 * Taxonomy related object.
 */
class PLL_TRS_Taxonomy {
	// The post type object.
	public $taxonomy_object;
	// The translated rewrite slugs.
	public $translated_slugs;
	// The translated object struct.
	public $translated_struct;

	/**
	 * Contructor.
	 */
	public function __construct($taxonomy_object, $translated_slugs, $translated_struct) {
		$this->taxonomy_object = $taxonomy_object;
		$this->translated_slugs = $translated_slugs;
		$this->translated_struct = $translated_struct;

		// Translate the rewrite rules of the post type.
		add_filter($this->taxonomy_object->name.'_rewrite_rules', array($this, 'taxonomy_rewrite_rules_filter'));
	}

	/**
	 * Translate the rewrite rules.
	 */
	public function taxonomy_rewrite_rules_filter($rewrite_rules) {
		global $polylang, $wp_rewrite;

		$translated_rules = array();

		// For each lang.
		foreach ($this->translated_slugs as $lang => $translated_slug) {
			// If "Hide URL language information for default language" option is
			// set to true the rules has to be different for the default language.
			if ($polylang->options['hide_default'] && $lang == pll_default_language()) {
				// For each rule.
				foreach ($rewrite_rules as $rule_key => $rule_value) {
					// Only translate the rewrite slug.
					$translated_rules[str_replace(trim($this->taxonomy_object->rewrite['slug'], '/'), $translated_slug, $rule_key)] = $rule_value;
				}
			} else {
				// For each rule.
				foreach ($rewrite_rules as $rule_key => $rule_value) {
					$taxonomy_rewrite_slug = $this->taxonomy_object->rewrite['slug'];

					// Replace the rewrite tags in slugs.
					foreach ($wp_rewrite->rewritecode as $position => $code) {
						$taxonomy_rewrite_slug = str_replace($code, $wp_rewrite->rewritereplace[$position], $taxonomy_rewrite_slug);
						$translated_slug = str_replace($code, $wp_rewrite->rewritereplace[$position], $translated_slug);
					}

					// Shift the matches up cause "lang" will be the first.
					$translated_rules['('.$lang.')/'.str_replace(trim($taxonomy_rewrite_slug, '/'), $translated_slug, $rule_key)] = str_replace(
						array('[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]'),
						array('[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]'),
						$rule_value
					);
				}
			}
		}

		return $translated_rules;
	}
}
