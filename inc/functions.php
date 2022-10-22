<?php

/**
 * Checks if there is at least one package in cart.
 *
 * @return bool
 * @since 1.0
 */
function validateCart(): bool
{

	foreach (WC()->cart->get_cart() as $cart_item) {
		if (\Vouchers\Wordpress\Terms\CustomCategories::checkIfProductIsAPackage($cart_item['product_id'])) {
			return true;
		}
	}
	return false;
}

/**
 * Checks if there is at least one package in order items.
 *
 * @return bool
 * @since 1.0
 */
function validateOrder($order_id): bool
{
	$order = new WC_Order($order_id);
	foreach ($order->get_items() as $order_item) {
		if (\Vouchers\Wordpress\Terms\CustomCategories::checkIfProductIsAPackage($order_item['product_id'])) {
			return true;
		}
	}
	return false;
}

/**
 * Checks if there is at least one package in cart, if true
 * and also if there are no extras in cart, then function return true.
 *
 * @return bool
 * @since 1.0
 */
function checkIfDisplayExtrasInfo(): bool
{
	$has_package = false;
	$has_extras = false;
	foreach (WC()->cart->get_cart() as $cart_item) {

		if (\Vouchers\Wordpress\Terms\CustomCategories::checkIfProductIsAPackage($cart_item['product_id'])) {
			$has_package = true;
		}

		if (\Vouchers\Wordpress\Terms\CustomCategories::checkIfProductIsASupplement($cart_item['product_id'])) {
			$has_extras = true;
		}

	}

	if ($has_package && !$has_extras) {
		return true;
	}

	return false;

}

/**
 * Handle download pdf voucher from thank you page
 *
 * @return void
 * @since 1.0
 */
function downloadVoucherPdf(): void
{

	if (empty($_POST['download_voucher']) || !wp_verify_nonce($_POST['download_voucher'], 'downloading_voucher_pdf')) {
		return;
	}

	if (isset($_POST['download_voucher_pdf'])) {
		\Vouchers\Vouchers\VoucherPdf::downloadVoucherPdf(absint($_POST['download_voucher_pdf']));
	}
}

add_action('init', 'downloadVoucherPdf');

/**
 * Handle download pdf voucher from PA edit order page.
 *
 * @return void
 * @since 1.0
 */
function adminDownloadVoucherPdf(): void
{

	if (empty($_GET['token']) || !wp_verify_nonce($_GET['token'], 'download_voucher_pdf')) {
		return;
	}

	if (isset($_GET['download_voucher_pdf']) && $_GET['download_voucher_pdf'] === 'true') {
		\Vouchers\Vouchers\VoucherPdf::downloadVoucherPdf(absint($_GET['post']));
	}
}

add_action('admin_init', 'adminDownloadVoucherPdf');

/**
 * Display notice in add new order page, about required package in order.
 *
 * @param WP_Post $post
 * @return void
 * @since 1.0
 */
function requiredPackageInNewOrderInfoDisplay(WP_Post $post): void
{

	if (!is_admin()) {
		return;
	}

	if ($post->post_type !== 'shop_order') {
		return;
	}

	global $pagenow;

	if ($pagenow != 'post-new.php') {
		return;
	}

	echo '<div class="notice notice-warning is-dismissible">
      <p>Zamówienie powinno zawierać przynajmniej jeden pakiet.</p>
      </div>';
}

add_action('submitpost_box', 'requiredPackageInNewOrderInfoDisplay');

/**
 * Add admin error notice if woocommerce is disabled and voucher plugin still is enabled.
 *
 * @return void
 * @since 1.0
 */
function addRequiredWoocommerceErrorNotice(): void
{
	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		echo '<div class="notice notice-error is-dismissible">
      <p>Plugin obsługujący Vouchery wymaga aktywowanego pluginu Woocommerce!</p>
      </div>';
	}
}

add_action('admin_notices', 'addRequiredWoocommerceErrorNotice');


add_filter( 'wp_robots', 'disableIndexingOnPage' );
function disableIndexingOnPage($robots){
	global $post;
	if(!empty($post) && !empty($post->ID) && $post->ID === \Vouchers\Wordpress\CustomFields\PluginOptionsPage::getPackageChosenPageId()){
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}

add_action('template_redirect', 'validatePackageChosenPageAccess');
function validatePackageChosenPageAccess(){
	global $post;
	if(empty($post) || empty($post->ID) || $post->ID !== \Vouchers\Wordpress\CustomFields\PluginOptionsPage::getPackageChosenPageId()){
		return;
	}
	$has_package = false;
	foreach (WC()->cart->get_cart() as $cart_item) {

		if (\Vouchers\Wordpress\Terms\CustomCategories::checkIfProductIsAPackage($cart_item['product_id'])) {
			$has_package = true;
		}

	}

	if(!$has_package){
		wp_safe_redirect(get_term_link(\Vouchers\Wordpress\Terms\CustomCategories::getPackagesCategoryId(), 'product_cat'));
	}

}

