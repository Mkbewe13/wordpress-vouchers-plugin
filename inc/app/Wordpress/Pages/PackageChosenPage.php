<?php

namespace Vouchers\Wordpress\Pages;


/**
 * Class for custom "package chosen" page
 *
 * @package VouchersFSO\Wordpress\Pages
 * @since 1.0
 */
class PackageChosenPage
{
	protected const TEMPLATES = ['restrict.php'];
	protected const PAGE_TITLE = 'Strona "Wybrano pakiet"';
	public const OPTION_KEY = '_m13_package_chosen_page_id';
	protected string $content = 'Test Chosen Page Content';


	public function register(): void
	{
		$package_chosen_page = new self();
	}

	public function deleteConfiguratorPageOption($postid)
	{
		$configurator_page_id = get_option(self::OPTION_KEY);

		if ( ! $configurator_page_id || $configurator_page_id != $postid) {
			return;
		}

		delete_option(self::OPTION_KEY);
	}

	public function detect(): bool
	{
		$configurator_page_id = get_option(self::OPTION_KEY);

		return (bool) ( get_the_ID() && (int) $configurator_page_id === get_the_ID() );
	}

	public function registerProcessing()
	{
		if( ! $this->detect()) {
			return;
		}
		add_filter('template_include',[$this,'setTemplate']);
	}

	public function setTemplate( string $template ) : string
	{
		$custom_templates = static::TEMPLATES;

		foreach ($custom_templates as $custom_template ) {
			$path = locate_template($custom_template);
			if( $path ) {
				return $path;
			}
		}

		return $template;
	}

	public function createPackageChosenPage(): void {
		if ( get_option(self::OPTION_KEY) === false ) {
			$page_id = wp_insert_post(
				array(
					'post_author'  => 1,
					'post_title'   => ucwords( self::PAGE_TITLE ),
					'post_name'    => sanitize_title( self::PAGE_TITLE ),
					'post_status'  => 'publish',
					'post_content' => $this->content,
					'post_type'    => 'page',
				)
			);

			if ( $page_id !== 0 && ! is_wp_error( $page_id ) ) {
				add_option( self::OPTION_KEY, $page_id );
			}
		}
	}


	public function setContent()
	{
		$extras_url = get_term_link(\Vouchers\Wordpress\Terms\CustomCategories::getExtrasCategoryId(), 'product_cat');
		if(is_wp_error($extras_url)){
			$extras_url = null;
		}

		$packages_url = get_term_link(\Vouchers\Wordpress\Terms\CustomCategories::getPackagesCategoryId(), 'product_cat');
		if(is_wp_error($packages_url)){
			$packages_url = null;
		}


		$cart_url = wc_get_cart_url();
		$order_url = wc_get_checkout_url();


		$html = '<a href="'. $cart_url  .'">Koszyk</a><br>';
		$html .= '<a href="'. $order_url  .'">Zamówienie</a><br>';
		$html .= '<a href="'. $packages_url  .'">Pakiety</a><br>';
		$html .= '<a href="'. $extras_url  .'">Dodatki</a><br>';



		$this->content = $html;
	}



}
