<?php

namespace Vouchers\Wordpress\CustomFields;

use Carbon_Fields\Container;
use Carbon_Fields\Field\Field;
use Vouchers\Wordpress\Pages\PackageChosenPage;

class PluginOptionsPage
{

	public function register(): void
	{

		$categories = $this->getCategories();
		$pages = $this->getPages();

		Container::make('theme_options', 'Vouchery')
			->add_fields(array(
				Field::make('number', 'm13_voucher_validity', 'Ważność vouchera')->set_width(1)->set_help_text(
					'Liczba dni'
				)->set_default_value( 365 ),
				Field::make('textarea', 'm13_voucher_description', 'Opis')
					->set_help_text(
						'Umieszczany na voucherze.'
					),
				Field::make('select', 'm13_packages_category_id', 'Kategoria reprezentująca pakiety')
					->add_options($categories),
				Field::make('select', 'm13_extras_category_id', 'Kategoria reprezentująca dodatki')
					->add_options($categories),
				Field::make('select', 'm13_package_chosen_page_id', 'Strona "Wybrano pakiet"')
					->add_options($pages),
				Field::make('textarea', 'm13_successful_purchase_msg', 'Wiadomość dla klienta po udanym opłaconym zamówieniu'),
				Field::make('textarea', 'm13_order_received_msg', 'Wiadomość dla klienta po złożonym ale nieopłaconym zamówieniu'),
			));
	}


	private function getCategories(): array
	{
		$categories = get_terms(array('hide_empty' => false));
		$categories_options = array();

		foreach ($categories as $category) {
			if ($category->taxonomy === 'product_cat') {
				$categories_options[$category->term_id] = $category->name;
			}
		}

		return $categories_options;
	}


	private function getPages()
	{
		$pages = get_pages();

		$pages_options = array();

		foreach ($pages as $page) {
			$pages_options[$page->ID] = $page->post_title;
		}

		return 	$pages_options;
	}



	/**
	 * Returns voucher validity days number from options, if option not exist return default value
	 *
	 * @return int
	 */
	public static function getVoucherValidityDays(): int
	{
		$voucher_validity = get_option('_m13_voucher_validity');

		if ($voucher_validity === false || $voucher_validity === '') {
			return 365;
		}

		return (int)$voucher_validity;
	}


	/**
	 * Returns voucher description from options, if option not exist return empty string
	 *
	 * @return string
	 */
	public static function getVoucherDescription(): string
	{
		$voucher_description = get_option('_m13_voucher_description');

		if ($voucher_description === false || $voucher_description === '') {
			return "";
		}

		return (string)$voucher_description;
	}

	/**
	 * Returns succesful purchase message from options, if option not exist return empty string
	 *
	 * @return string
	 */
	public static function getSuccesfulPurchaseMessage(): string
	{
		$voucher_description = get_option('_m13_successful_purchase_msg');

		if ($voucher_description === false || $voucher_description === '') {
			return "";
		}

		return (string)$voucher_description;
	}

	/**
	 * Returns order received message from options, if option not exist return empty string
	 *
	 * @return string
	 */
	public static function getOrderReceivedMessage(): string
	{
		$voucher_description = get_option('_m13_order_received_msg');

		if ($voucher_description === false || $voucher_description === '') {
			return "";
		}

		return (string)$voucher_description;
	}


	/**
	 * Returns package chosen page id from options, if option not exist return 0
	 *
	 * @return int
	 */
	public static function getPackageChosenPageId(): int
	{
		$package_chosen_page_id = get_option(PackageChosenPage::OPTION_KEY);

		if ($package_chosen_page_id === false || $package_chosen_page_id === '') {
			return 0;
		}

		return (int)$package_chosen_page_id;
	}
}
