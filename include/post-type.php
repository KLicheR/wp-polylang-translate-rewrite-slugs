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

		// Translate the rewrite rules.
		add_filter($this->post_type_object->name.'_rewrite_rules', array($this, 'post_type_rewrite_rules_filter'));
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
}