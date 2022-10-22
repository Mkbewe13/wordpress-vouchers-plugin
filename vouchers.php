<?php
/**
 * Plugin Name:     Wordpress Vouchers Plugin
 * Description:     Woocommerce simple vouchers extension.
 * Author:          Mkbewe13
 * Text Domain:     wvp
 * Version:         1.0
 */


if (!defined('ABSPATH')) {
	exit;
}

const M13_APP_NAMESPACE = '\Vouchers';
define('M13_APP_DIR', dirname(__FILE__));

require_once 'vendor/autoload.php';

class Vouchers
{


	public function init()
	{
		// Activatin hook
		register_activation_hook(__FILE__, [$this, 'activation']);

		// Register custom post types for the app
		add_action('init', [$this, 'registerCPTs'], 0);

		// Register taxonomies for episodes
		add_action('init', [$this, 'registerTaxonomies'], 0);

		// Register custom data fields
		add_action('carbon_fields_register_fields', [$this, 'registerClassesWithCarbonFields']);

		add_action('woocommerce_loaded', [$this, 'registerClassesWithWoocommerce']);

		$this->registerFilters();

		// Other, non-typical stuff to be registered
		$this->registerOtherHooks();

		// Load custom meta fields UI framework
		$this->bootCarbonFields();

	}


	public function registerOtherHooks()
	{
		require_once 'inc/functions.php';
		require_once 'inc/woocommerce.php';


	}

	public function bootCarbonFields(): void
	{
		try {
			\Carbon_Fields\Carbon_Fields::boot();
		} catch (\Exception $e) {
			// do nothing
		}

	}


	public function registerCPTs(): void
	{

	}

	public function registerTaxonomies(): void
	{

	}

	public function registerClassesWithCarbonFields(): void
	{
		$option_page = new \Vouchers\Wordpress\CustomFields\PluginOptionsPage();
		$option_page->register();

		$custom_categories = new \Vouchers\Wordpress\Terms\CustomCategories();
		$custom_categories->register();
	}

	public function registerClassesWithWoocommerce(): void
	{
		$voucher_service = new \Vouchers\Vouchers\VoucherService();
		$voucher_service->register();
	}


	public function registerFilters()
	{

	}

	public function activation()
	{
		if (!class_exists('WooCommerce')) {
			die('Plugin obsługujący vouchery wymaga aktywnego pluginu Woocommerce.');
		}

		$packageChosenPage = new \Vouchers\Wordpress\Pages\PackageChosenPage();
		$packageChosenPage->setContent();
		$packageChosenPage->createPackageChosenPage();

	}

}

$ntl_plugin = new Vouchers();
$ntl_plugin->init();
