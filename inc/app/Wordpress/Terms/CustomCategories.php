<?php

namespace Vouchers\Wordpress\Terms;

/**
 * Class for custom categories of voucher products
 *
 * @package Vouchers\Wordpress\Terms
 * @since 1.0
 */
class CustomCategories
{
	const CATEGORY_PACKAGE_SLUG = 'packages';
	const CATEGORY_EXTRAS_SLUG = 'extras';
	const CATEGORY_PACKAGES_OPTION_KEY = '_m13_packages_category_id';
	const CATEGORY_EXTRAS_OPTION_KEY = '_m13_extras_category_id';

	/**
	 * Register class
	 *
	 * @return void
	 */
	public function register()
	{
		$custom_cat = new self();
		add_action('wp_loaded', [$custom_cat, 'registerCustomProductCategories']);
	}

	/**
	 * Register custom categories for voucher products.
	 *
	 * @return void
	 */
	public function registerCustomProductCategories(): void
	{
		if (get_option(self::CATEGORY_PACKAGES_OPTION_KEY) !== false && get_option(self::CATEGORY_EXTRAS_OPTION_KEY) !== false) {
			return;
		}

		$packages_cat_id = (int)get_option(self::CATEGORY_PACKAGES_OPTION_KEY);
		$extras_cat_id = (int)get_option(self::CATEGORY_EXTRAS_OPTION_KEY);
		$packages_result = null;
		$extras_result = null;

		if (!term_exists($packages_cat_id, 'product_term')) {
			$packages_result = wp_insert_term(
				'Pakiety',
				'product_cat',
				array(
					'description' => 'Pakiety do voucherów.',
					'slug' => self::CATEGORY_PACKAGE_SLUG
				)
			);
		}


		if (!term_exists($extras_cat_id, 'product_term')) {
			$extras_result = wp_insert_term(
				'Dodatki', // the term
				'product_cat', // the taxonomy
				array(
					'description' => 'Dodatki do pakietów',
					'slug' => self::CATEGORY_EXTRAS_SLUG
				)
			);
		}


		if ($packages_result !== null && !is_wp_error($packages_result)) {
			add_option(self::CATEGORY_PACKAGES_OPTION_KEY, $packages_result['term_id']);
		}

		if ($extras_result !== null && !is_wp_error($extras_result)) {
			add_option(self::CATEGORY_EXTRAS_OPTION_KEY, $extras_result['term_id']);
		}

	}


	/**
	 * Check if given product is a package (belongs to the package category)
	 *
	 * @param int $product_id
	 * @return bool
	 */
	public static function checkIfProductIsAPackage(int $product_id): bool
	{
		$terms = get_the_terms($product_id, 'product_cat');

		foreach ($terms as $term) {
			if ($term->term_id === self::getPackagesCategoryId()) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Check if given product is a order supplement (belongs to the extras category)
	 *
	 * @param int $product_id
	 * @return bool
	 */
	public static function checkIfProductIsASupplement(int $product_id): bool
	{
		$terms = get_the_terms($product_id, 'product_cat');

		foreach ($terms as $term) {
			if ($term->term_id === self::getExtrasCategoryId()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return packages category id (int) or null if term not exists
	 *
	 * @return int|null
	 */
	public static function getPackagesCategoryId(): ?int
	{
		$result = get_option(self::CATEGORY_PACKAGES_OPTION_KEY);

		if (empty($result)) {
			return null;
		}

		return (int)$result;
	}

	/**
	 *  Return extras category id (int) or null if term not exists
	 *
	 * @return int|null
	 */
	public static function getExtrasCategoryId(): ?int
	{
		$result = get_option(self::CATEGORY_EXTRAS_OPTION_KEY);

		if (empty($result)) {
			return null;
		}

		return $result;
	}


}
