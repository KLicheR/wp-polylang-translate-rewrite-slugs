<?php
/**
 * Taxonomy related object.
 */
class PLL_TRS_Taxonomy {
	// The post type object.
	public $taxonomy_object;
	// The translated rewrite slugs.
	public $translated_slugs;

	/**
	 * Contructor.
	 */
	public function __construct($taxonomy_object, $translated_slugs) {
		$this->taxonomy_object = $taxonomy_object;
		$this->translated_slugs = $translated_slugs;

		// Translate the rewrite rules of the post type.
		add_filter($this->taxonomy_object->name.'_rewrite_rules', array($this, 'taxonomy_rewrite_rules_filter'));
	}

	/**
	 * Translate the rewrite rules.
	 */
	public function taxonomy_rewrite_rules_filter($rewrite_rules) {
		$translated_rules = array();

		// For each lang.
		foreach ($this->translated_slugs as $lang => $translated_slug) {
			// For each rule.
			foreach ($rewrite_rules as $rule_key => $rule_value) {
				// Shift the matches up cause "lang" will be the first.
				$translated_rules['('.$lang.')/'.str_replace(trim($this->taxonomy_object->rewrite['slug'], '/'), $translated_slug, $rule_key)] = str_replace(
					array('[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]'),
					array('[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]'),
					$rule_value
				);
			}
		}

		return $translated_rules;
	}
}