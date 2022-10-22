<?php

/**
 * Sets vitual product type as default in new product creation/edit
 *
 * @param $data
 * @return array
 *
 * @since 1.0
 */
function alwaysSetProductsAsVirtual($data): array
{
	$data['virtual']     ['default'] = "yes";
	return $data;
}

add_filter('product_type_options', 'alwaysSetProductsAsVirtual');


/**
 * Disable order button in checkout if there are no packages in cart
 *
 * @param $order_button
 * @return array|mixed|string|string[]
 *
 * @since 1.0
 */
function disableOrderButtonInCheckout($order_button)
{
	if (validateCart()) {
		return $order_button;
	}

	$style = ' style="color:#fff;cursor:not-allowed;background-color:#999;"';
	$pos = strpos($order_button, "<button");
	$pos += strlen('<button') + 1;

	return substr_replace($order_button, ' disabled ' . $style . ' ', $pos, 0);
}

add_filter('woocommerce_order_button_html', 'disableOrderButtonInCheckout', 10, 2);

/**
 * Displays notice about wrong cart items in checkout and cart page
 *
 * @return void
 *
 * @since 1.0
 */
function displayCustomNoticeInCheckoutAndCart(): void
{
	if (!validateCart()) {

		$msg = 'Żeby złożenie zamówienia było możliwe, w koszyku musi znajdować się przynajmniej jeden <a href="' . get_term_link(\Vouchers\Wordpress\Terms\CustomCategories::getPackagesCategoryId(), 'product_cat') . '">pakiet</a>. ';

		if (is_checkout() || is_cart()) {
			wc_add_notice($msg, 'notice');
		}
	}

	if (checkIfDisplayExtrasInfo()) {
		$msg = 'Do wybranego pakietu można domówić <a href="' . get_term_link(\Vouchers\Wordpress\Terms\CustomCategories::getExtrasCategoryId(), 'product_cat') . '">dodatki</a>. ';

		if (is_checkout() || is_cart()) {
			wc_add_notice($msg, 'notice');
		}
	}


}

add_action('woocommerce_check_cart_items', 'displayCustomNoticeInCheckoutAndCart', 50, 1);


/**
 * Adds radio for gift orders and field for recipient name
 *
 * @since 1.0
 */
function addCustomFieldsInCheckout(): void
{
	woocommerce_form_field('is_gift', array(
		'type' => 'radio',
		'options' => array('false' => 'Kupuję dla siebie', 'true' => 'Kupuję jako prezent'),
		'required' => true,
		'default' => 'false',
	), WC()->checkout->get_value('is_gift'));


	//@TODO tutaj trzeba chowac/pokazywac zależenie od powyższego radio
	woocommerce_form_field('recipient_name', array(
		'type' => 'text',
		'required' => false,
		'class' => array('recipient-name'),
		'label' => __('Wpisz imię i nazwisko obdarowywanej osoby'),
	), WC()->checkout->get_value('recipient_name'));
}

add_action('woocommerce_review_order_before_submit', 'addCustomFieldsInCheckout', 10);

/**
 * Remove "optional" label from recipient name field in checkout
 *
 * @param $field
 * @param $key
 * @param $args
 * @param $value
 *
 * @since 1.0
 */
function removeCheckoutOptionalFieldsLabel($field, $key, $args, $value)
{

	if (is_checkout() && !is_wc_endpoint_url() && $key === 'recipient_name') {
		$optional = '&nbsp;<span class="optional">(' . esc_html__('optional', 'woocommerce') . ')</span>';
		$field = str_replace($optional, '', $field);
	}
	return $field;
}

add_filter('woocommerce_form_field', 'removeCheckoutOptionalFieldsLabel', 10, 4);

/**
 * Add recipient name field in the order meta.
 *
 * @since 1.0
 **/
function saveVoucherOrderRecipientMeta($order_id): void
{
	if (!isset($_POST['is_gift']) || $_POST['is_gift'] !== 'true') {
		return;
	} else if (!empty($_POST['recipient_name'])) {
		update_post_meta($order_id, \Vouchers\Vouchers\VoucherService::VOUCHER_RECIPIENT_POST_META_KEY, esc_attr($_POST['recipient_name']));
	}
}

add_action('woocommerce_checkout_update_order_meta', 'saveVoucherOrderRecipientMeta');


function addAdminOrdersListCustomColumns($columns)
{
	$reordered_columns = array();

	foreach ($columns as $key => $column) {
		$reordered_columns[$key] = $column;
		if ($key == 'order_status') {
			$reordered_columns['recipient'] = 'Właściciel';
			$reordered_columns['voucher'] = 'Voucher';
			$reordered_columns['voucher_state'] = 'Stan Vouchera';
		}
	}
	return $reordered_columns;
}

add_filter('manage_edit-shop_order_columns', 'addAdminOrdersListCustomColumns', 20);

/**
 * Load voucher codes for custom voucher column in orders admin page
 *
 * @param $column
 * @param $post_id
 * @return void
 * @since 1.0
 */
function loadAdminOrdersListCustomColumnValues($column, $post_id): void
{
	if ($column !== 'voucher' && $column !== 'recipient' && $column !== 'voucher_state') {
		return;
	}

	switch ($column) {
		case 'voucher':
			$voucher_code = \Vouchers\Vouchers\VoucherService::getVoucherCodeByOrderId($post_id);
			if (!empty($voucher_code)) {
				echo $voucher_code;
			} else {
				echo '<small>(<em>brak kodu</em>)</small>';
			}
			break;
		case 'recipient':
			$recipient = \Vouchers\Vouchers\VoucherService::getVoucherReceiverByOrderId($post_id);
			if (!empty($recipient)) {
				echo $recipient;
			} else {
				echo '<small>(<em>brak danych</em>)</small>';
			}
			break;
		case 'voucher_state':
			$state = \Vouchers\Vouchers\VoucherService::getVoucherState($post_id);
			echo $state;
			break;
	}

}

add_action('manage_shop_order_posts_custom_column', 'loadAdminOrdersListCustomColumnValues', 20, 2);

/**
 * Add searching by voucher code in orders page in PA
 *
 * @param $search_fields
 * @return mixed
 * @since 1.0
 */
function addOrderSearchByVoucher($search_fields)
{
	$search_fields[] = \Vouchers\Vouchers\VoucherService::VOUCHER_CODE_POST_META_KEY;
	return $search_fields;
}

add_filter('woocommerce_shop_order_search_fields', 'addOrderSearchByVoucher');

/**
 * Hide unnecesary checkout page fields
 *
 * @param $fields
 * @return mixed
 * @since 1.0
 */
function modifyCheckoutFields($fields)
{
	unset($fields['billing']['billing_company']);
	unset($fields['billing']['billing_address_1']);
	unset($fields['billing']['billing_address_2']);
	unset($fields['billing']['billing_city']);
	unset($fields['billing']['billing_postcode']);
	unset($fields['billing']['billing_country']);
	unset($fields['billing']['billing_state']);
	unset($fields['billing']['billing_phone']);
	unset($fields['account']['account_username']);
	unset($fields['account']['account_password']);
	unset($fields['account']['account_password-2']);

	return $fields;
}

add_filter('woocommerce_checkout_fields', 'modifyCheckoutFields');

/**
 * Hide order notes field in checkout
 */
add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999);

/**
 * Add order validation notice in admin order creation in PA
 *
 * @param $order
 * @return void
 * @since 1.0
 */
function addAdminOrderValidationNotice($order_id)
{

	if (!validateOrder($order_id)) {
		echo '<b>Aby utworzyć zamówienie musi ono zawierać przynajmniej jeden pakiet</b>';
	}
}

add_action('woocommerce_admin_order_totals_after_total', 'addAdminOrderValidationNotice');

/**
 * Add download voucher button on thank you page
 *
 * @param $order_id
 * @return void
 * @since 1.0
 */
function pdfDownloadButtonOnThankYouPage($order_id)
{
	global $wp;

	$order = new WC_Order($order_id);

	if (!$order->get_status()) {
		return;
	}
	$disabled = $order->get_status() !== 'completed' ? 'disabled' : '';
	if ($order->get_status() === 'completed') {
		echo ' <form action="' . home_url($wp->request) . '"  method="post">
  <input type="hidden" id="download_voucher_pdf" name="download_voucher_pdf" value="' . $order_id . '"><br>
  ' . wp_nonce_field('downloading_voucher_pdf', 'download_voucher') . '
  <input type="submit" class="button" value="Pobierz voucher">
	</form> ';
	} else {

		echo ' <form action="' . home_url($wp->request) . '"  method="post">
  <input type="submit" class="button" ' . $disabled . ' value="Pobierz voucher"><br>
  <small>Opłać zamówienie, aby móc pobrać i zrealizować voucher.</small>
	</form> ';
	}


}

add_action('woocommerce_thankyou', 'pdfDownloadButtonOnThankYouPage');

/**
 * Add custom meta fields in admin order edit/add page
 *
 * @param $order
 * @return void
 * @since 1.0
 */
function addCustomFieldsInOrderEditPage($order): void
{
	$order_id = $order->get_id();
	$is_gift = \Vouchers\Vouchers\VoucherService::checkVoucherIsAGift($order_id);
	if ($is_gift) {
		$recipient_name = get_post_meta($order_id, \Vouchers\Vouchers\VoucherService::VOUCHER_RECIPIENT_POST_META_KEY, true);
	} else {
		$recipient_name = '';
	}
	$is_used = (int)get_post_meta($order_id, \Vouchers\Vouchers\VoucherService::VOUCHER_USED_POST_META_KEY, true);
	$validity_date = \Vouchers\Vouchers\VoucherService::getVoucherValidityDateByOrderId($order_id);
	$is_gift_info = $is_gift ? 'Tak' : 'Nie';


	echo '	<br class="clear" />
	<h3>Voucher <a href="#" class="edit_address">Edit</a></h3>
	<div class="address">
		<p><strong>Data ważności:</strong>' . $validity_date . '</p>
		<p><strong>Kupiony na prezent?</strong>' . $is_gift_info . '</p>';
	if ($is_gift) {
		echo '<p><strong>Recipient name:</strong>' . $recipient_name . '</p>';
	}

	echo '<br><p><strong>Stan Vouchera:</strong>';
	echo \Vouchers\Vouchers\VoucherService::getVoucherState($order->get_id());
	echo '</p>';


	echo '</div>
	<div class="edit_address">';

	woocommerce_wp_text_input(array(
		'id' => 'validity_date',
		'label' => 'Data ważności:',
		'value' => $validity_date,
		'placeholder' => 'DD-MM-YYYY',
		'wrapper_class' => 'form-field-wide'
	));

	woocommerce_wp_radio(array(
		'id' => 'is_gift',
		'label' => 'Kupiony na prezent?',
		'value' => $is_gift,
		'options' => array(
			'' => 'Nie',
			'1' => 'Tak'
		),
		'style' => 'width:16px', // required for checkboxes and radio buttons
		'wrapper_class' => 'form-field-wide' // always add this class
	));

	woocommerce_wp_text_input(array(
		'id' => 'recipient_name',
		'label' => 'Osoba obdarowywana:',
		'value' => $recipient_name,
		'wrapper_class' => 'form-field-wide'
	));

	woocommerce_wp_radio(array(
		'id' => 'is_used',
		'label' => 'Stan vouchera?',
		'value' => $is_used,
		'options' => array(
			'0' => 'Aktywny',
			'1' => 'Wykorzystany',
			'2' => 'Anulowany',
		),
		'style' => 'width:16px', // required for checkboxes and radio buttons
		'wrapper_class' => 'form-field-wide' // always add this class
	));


	echo '</div>';

}

add_action('woocommerce_admin_order_data_after_shipping_address', 'addCustomFieldsInOrderEditPage');

/**
 * Save order meta while editing/adding via admin panel
 *
 * @param $order_id
 * @return void
 * @since 1.0
 */
function saveAdminOrderMeta($order_id)
{

	$is_gift = !empty($_POST['is_gift']) ? absint($_POST['is_gift']) : 0;
	$recipient = !empty($_POST['recipient_name']) ? sanitize_text_field($_POST['recipient_name']) : null;

	if ($is_gift === 1 && $recipient !== null) {
		update_post_meta($order_id, \Vouchers\Vouchers\VoucherService::VOUCHER_RECIPIENT_POST_META_KEY, $recipient);
	} else {
		delete_post_meta($order_id, \Vouchers\Vouchers\VoucherService::VOUCHER_RECIPIENT_POST_META_KEY, $recipient);
	}
	$voucher_state = absint($_POST['is_used']);

	$validity_date = !empty($_POST['validity_date']) ? sanitize_text_field($_POST['validity_date']) : null;
	if ($validity_date !== null && preg_match('/\d{2}-\d{2}-\d{4}/', $validity_date)) {
		update_post_meta($order_id, \Vouchers\Vouchers\VoucherService::VOUCHER_CODE_VALIDITY_POST_META_KEY, $validity_date);
	}else{
		update_post_meta($order_id, '_m13_wrong_date_format', '1');
	}

	if ($voucher_state === 0 || $voucher_state === 1) {
		\Vouchers\Vouchers\VoucherService::restoreVoucher($order_id);
		update_post_meta($order_id, \Vouchers\Vouchers\VoucherService::VOUCHER_USED_POST_META_KEY, $voucher_state);
		if(!\Vouchers\Vouchers\VoucherService::isVoucherExpired($order_id)){
			addVoucherStateAdminNote($order_id,\Vouchers\Vouchers\VoucherService::getVoucherState($order_id));
		}else{
			addVoucherStateAdminNote($order_id,'<p style="color:indianred;">Przeterminowany</p>');
		}
	} else if ($voucher_state === 2) {
		\Vouchers\Vouchers\VoucherService::cancelVoucher($order_id);
		addVoucherStateAdminNote($order_id,\Vouchers\Vouchers\VoucherService::getVoucherState($order_id));
	}

}
add_action('woocommerce_process_shop_order_meta', 'saveAdminOrderMeta');

add_action('admin_notices', 'custom_order_edit_meta_notice');
function custom_order_edit_meta_notice()
{
	global $post;

	if(!$post){
		return;
	}

	if ($post->post_type != 'shop_order') {
		return;
	}

	if (empty($_GET['post'])) {
		return;
	}

	$post_id = absint($_GET['post']);

	$wrong_date_format_meta = get_post_meta($post_id, '_m13_wrong_date_format', true);

	if ($wrong_date_format_meta !== '1') {
		return;
	}
	delete_post_meta($post_id, '_m13_wrong_date_format');

	echo '<div class="notice is-dismissible notice-error">
		<p>Data ważności vouchera nie została zapisana - błędny format</p>
</div>';
}

function addVoucherOwnerInfoInOrderEditPage($order)
{
	if (empty($_GET['action']) || $_GET['action'] !== 'edit') {
		return;
	}

	if (!\Vouchers\Vouchers\VoucherService::checkVoucherIsAGift($order->get_id())) {
		return;
	}

	echo '<p><strong>Właściciel Vouchera: </strong>' . \Vouchers\Vouchers\VoucherService::getVoucherReceiverByOrderId($order->get_id()) . '</p>';
}

add_action('woocommerce_admin_order_data_after_billing_address', 'addVoucherOwnerInfoInOrderEditPage', 9);

/**
 * Add download pdf button in order edit page
 *
 * @param $order
 * @return void
 * @since 1.0
 */
function addDownloadPdfButtonInOrderEditPage($order)
{

	if (empty($_GET['action']) || $_GET['action'] !== 'edit') {
		return;
	}

	$order_id = $order->get_id();

	if (\Vouchers\Vouchers\VoucherService::checkIfVoucherIsCanceled($order_id)) {
		return;
	}

	if ($order->get_status() !== 'completed') {
		echo '<a href="" class="button" disabled>Pobierz Voucher</a><br><small>Voucher niedostępny (zamówienie nieopłacone)</small>';
		return;
	}

	$nonce = wp_create_nonce('download_voucher_pdf');
	$url = get_admin_url() . 'post.php?post=' . $order_id . '&action=edit';
	$url .= '&token=' . $nonce;
	$url .= '&download_voucher_pdf=true';


	echo '<a href="' . $url . '" class="button">Pobierz Voucher</a>';

}

add_action('woocommerce_admin_order_data_after_billing_address', 'addDownloadPdfButtonInOrderEditPage');


/**
 * Add attachment to emails sended by woocommerce
 *
 * @param $attachments
 * @param $email_id
 * @param $order
 * @return mixed
 * @since 1.0
 */
function addAttachmentToWoocommerceEmail($attachments, $email_id, $order)
{
	if (!is_a($order, 'WC_Order') || !isset($email_id)) {
		return $attachments;
	}
	if ($email_id !== 'customer_completed_order') {
		return $attachments;
	}

	if (!file_exists(WP_CONTENT_DIR . '/uploads/tmp-vouchers')) {
		mkdir(WP_CONTENT_DIR . '/uploads/tmp-vouchers', 0755, true);
	}

	$file = \Vouchers\Vouchers\VoucherPdf::getVoucherPdfOutput($order->get_id());

	$voucher_code = \Vouchers\Vouchers\VoucherService::getVoucherCodeRandomPartByOrderId($order->get_id());

	if(!$voucher_code){
		error_log('Plik pdf vouchera nie może zostać utworzony.');
	}

	$file_path = WP_CONTENT_DIR . '/uploads/tmp-vouchers/' . sanitize_text_field($voucher_code) . '.pdf';

	file_put_contents($file_path, $file);

	$attachments[] = $file_path;
	return $attachments;
}

add_filter('woocommerce_email_attachments', 'addAttachmentToWoocommerceEmail', 10, 3);

/**
 * Delete temporary vocher pdf file
 *
 * @param $order_id
 * @return void
 * @since 1.0
 */
function deleteTmpVouchers($order_id)
{
	if (!file_exists(WP_CONTENT_DIR . '/uploads/tmp-vouchers/' . \Vouchers\Vouchers\VoucherService::getVoucherCodeRandomPartByOrderId($order_id) . '.pdf')) {
		return;
	}
	$file_path = WP_CONTENT_DIR . '/uploads/tmp-vouchers/' . \Vouchers\Vouchers\VoucherService::getVoucherCodeRandomPartByOrderId($order_id) . '.pdf';
	wp_delete_file($file_path);
}

add_action('woocommerce_thankyou', 'deleteTmpVouchers', 99);

function addVoucherStateAdminNote(int $order_id,$voucher_state){
	$order = wc_get_order(  $order_id );

	$note = 'Status vouchera został zmieniony na: ';
	$note .= $voucher_state;

	$order->add_order_note( $note );
}


add_action('woocommerce_add_to_cart', 'redirectToPackageChosenPage');
function redirectToPackageChosenPage()
{

	if(!empty($_POST['add-to-cart'])){
		$product_id = absint($_POST['add-to-cart']);
	}elseif (!empty($_GET['add-to-cart'])){
		$product_id = absint($_GET['add-to-cart']);
	}else{
		return;
	}

	if(!$product_id){
		return;
	}

	if (\Vouchers\Wordpress\Terms\CustomCategories::checkIfProductIsAPackage($product_id)) {
		wp_safe_redirect(get_permalink(\Vouchers\Wordpress\CustomFields\PluginOptionsPage::getPackageChosenPageId()));
	}
}

add_filter('woocommerce_thankyou_order_received_text', 'customThankYouPageText',2,99);
function customThankYouPageText(string $message,\Automattic\WooCommerce\Admin\Overrides\Order $order)
{
	if(!$order->get_id()){
		return $message;
	}

	$order_id = $order->get_id();
	$order = new WC_Order(absint($order_id));

	if(!$order->get_status()){
		return $message;
	}

	if ($order->get_status() === 'completed') {
		$new_message = \Vouchers\Wordpress\CustomFields\PluginOptionsPage::getSuccesfulPurchaseMessage();
		$message = $new_message !== '' ? $new_message : $message;
	} elseif ($order->get_status() === 'pending') {
		$new_message = \Vouchers\Wordpress\CustomFields\PluginOptionsPage::getOrderReceivedMessage();
		$message = $new_message !== '' ? $new_message : $message;
	}

	return $message;
}

