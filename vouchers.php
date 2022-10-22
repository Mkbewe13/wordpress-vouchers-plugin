<?php
/**
 * Plugin Name:     Wordpress Vouchers Plugin
 * Description:     Woocommerce simple vouchers extension.
 * Author:          Mkbewe13
 * Text Domain:     wvp
 * Version:         0.1
 */


if (!defined('ABSPATH')) {
	exit;
}

const M13_APP_NAMESPACE = '\wordpress-vouchers-plugin';
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

	}

	public function registerClassesWithWoocommerce(): void
	{

	}


	public function registerFilters()
	{

	}

	public function activation()
	{

	}

}

$ntl_plugin = new Vouchers();
$ntl_plugin->init();
