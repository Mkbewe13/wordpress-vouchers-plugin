<?php

namespace Vouchers\Vouchers;

use DateTime;
use Vouchers\Wordpress\CustomFields\PluginOptionsPage;
use WC_Order;

/**
 * Class for functions related with Vouchers.
 *
 * @package Vouchers\Vouchers
 * @since x.x.x
 */
class VoucherService
{
	const VOUCHER_CODE_POST_META_KEY = '_m13_voucher_code';
	const VOUCHER_CODE_VALIDITY_POST_META_KEY = '_m13_voucher_code_validity';
	const VOUCHER_USED_POST_META_KEY = '_m13_voucher_used';
	const VOUCHER_RECIPIENT_POST_META_KEY = '_m13_voucher_recipient_name';
	const VOUCHER_CANCEL_POST_META_KEY = '_m13_voucher_cancel';

	public function register(): void
	{
		$voucher_service = new self();
		add_action('woocommerce_new_order', [$voucher_service, 'generateVoucher'], 10, 3);
		add_action('woocommerce_new_order', [$voucher_service, 'saveVoucherValidity'], 12, 3);
	}


	/**
	 * Generate voucher code for given order by order date and random unique voucher code
	 *
	 * @param $order_id
	 * @return void
	 * @throws \Exception
	 */
	public static function generateVoucher($order_id): void
	{

		$order = new WC_Order($order_id);
		$date = $order->get_date_created()->date('dmy');
		$random_code = self::generateVoucherCode(10);
		$voucher_code = $date . '/' . $random_code;
		add_post_meta($order->get_id(), self::VOUCHER_CODE_POST_META_KEY, $voucher_code);
		add_post_meta($order->get_id(), self::VOUCHER_USED_POST_META_KEY, false);

	}

	/**
	 * Save voucher validity date based on voucher settings. Result saved in postmeta
	 *
	 * @param $order_id
	 * @return void
	 * @throws \Exception
	 */
	public static function saveVoucherValidity($order_id): void
	{

		$order = new WC_Order($order_id);

		$validity_date = new DateTime();


		$validity_date->setTimestamp($order->get_date_created()->getTimestamp());


		$voucher_active_days = PluginOptionsPage::getVoucherValidityDays();

		if (!$voucher_active_days) {
			return;
		}

		$validity_date->add(new \DateInterval('P' . $voucher_active_days . 'D'));

		add_post_meta($order->get_id(), self::VOUCHER_CODE_VALIDITY_POST_META_KEY, $validity_date->format('d-m-Y'));

	}

	/**
	 * Generate random and unique voucher code of given length
	 *
	 * @throws \Exception
	 */
	private static function generateVoucherCode(int $length = 10): string
	{
		$validated = false;

		while ($validated == false) {
			$code = substr(str_shuffle(str_repeat($chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($chars)))), 1, $length);
			if (self::validateCode($code)) {
				$validated = true;
			}
		}

		if (empty($code)) {
			throw new \Exception('Wystąpił błąd podczas generowania kodu vouchera');
		}
		return $code;

	}

	/**
	 * Checks if given code is unique (has not been used so far)
	 *
	 * @param string $code
	 * @return bool
	 */
	private static function validateCode(string $code): bool
	{
		$orders = wc_get_orders([]);
		$codes = [];


		foreach ($orders as $order) {
			$voucher_code = get_post_meta($order->get_id(), self::VOUCHER_CODE_POST_META_KEY, true);
			if ($voucher_code === '') {
				continue;
			}
			$code_random_part = explode('/', $voucher_code)[1];
			$codes[] = $code_random_part;
		}

		if (in_array($code, $codes)) {
			return false;
		}

		return true;
	}

	/**
	 * Return voucher code of order given by order_id
	 *
	 * @param int $order_id
	 * @return string|null
	 */
	public static function getVoucherCodeByOrderId(int $order_id): ?string
	{

		$code = get_post_meta($order_id, self::VOUCHER_CODE_POST_META_KEY, true);

		if (!$code) {
			return null;
		}
		return $code;
	}

	/**
	 * Get voucher receiver by given order_id.
	 *
	 * @param int $order_id
	 * @return string
	 */
	public static function getVoucherReceiverByOrderId(int $order_id): string
	{
		$recipient = get_post_meta($order_id, self::VOUCHER_RECIPIENT_POST_META_KEY, true);

		if ($recipient) {
			return $recipient;
		} else {
			$order = new WC_Order($order_id);

			return $order->get_formatted_billing_full_name();
		}
	}

	/**
	 * Return voucher validity date by given order id.
	 *
	 * @param int $order_id
	 * @return string|null
	 */
	public static function getVoucherValidityDateByOrderId(int $order_id): ?string
	{

		$validity_date = get_post_meta($order_id, self::VOUCHER_CODE_VALIDITY_POST_META_KEY, true);

		if (!$validity_date) {
			return null;
		}

		return $validity_date;

	}

	/**
	 * Check if voucher is bought as a gift
	 *
	 * @param int $order_id
	 * @return bool
	 */
	public static function checkVoucherIsAGift(int $order_id): bool
	{
		$recipient = get_post_meta($order_id, self::VOUCHER_RECIPIENT_POST_META_KEY, true);

		if ($recipient) {
			return true;
		}
		return false;
	}

	/**
	 * Return voucher description from option page
	 *
	 * @return string
	 */
	public static function getVouchersDescription(): string
	{
		$description = PluginOptionsPage::getVoucherDescription();
		return $description ? sanitize_text_field($description) : '';
	}

	/**
	 * Return voucher state: used, unused, expired
	 *
	 * @param int $order_id
	 * @return string
	 */
	public static function getVoucherState(int $order_id): string
	{
		if (self::checkIfVoucherIsCanceled($order_id)) {
			return '<p style="color:darkred;">Anulowany</p>';
		}

		$voucher_code = \Vouchers\Vouchers\VoucherService::getVoucherCodeByOrderId($order_id);
		if (empty($voucher_code)) {
			return '<small>(<em>brak kodu</em>)</small>';
		}

		if (self::isVoucherExpired($order_id)) {
			return '<p style="color:indianred;">Przeterminowany</p>';
		}

		if (!metadata_exists('post', $order_id, self::VOUCHER_USED_POST_META_KEY)) {
			return 'Brak Danych';
		}

		$used = get_post_meta($order_id, self::VOUCHER_USED_POST_META_KEY, true);

		if ((int)$used === 0) {
			return '<p style="color:green;">Aktywny</p>';
		} elseif ((int)$used === 1) {
			return '<p style="color:dodgerblue;">Wykorzystany</p>';
		}

		return 'Brak Danych';
	}


	/**
	 * Check if voucher validation date is expired
	 *
	 * @param int $order_id
	 * @return bool
	 */
	public static function isVoucherExpired(int $order_id): bool
	{
		$validity_date = self::getVoucherValidityDateByOrderId($order_id);

		if (!$validity_date) {
			return true;
		}

		$validity_date = DateTime::createFromFormat('d-m-Y', $validity_date);
		$current_date = DateTime::createFromFormat('d-m-Y', date('d-m-Y'));


		if ($validity_date >= $current_date) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Return true if voucher state is "Canceled" false othrewise
	 *
	 * @param int $order_id
	 * @return bool
	 */
	public static function checkIfVoucherIsCanceled(int $order_id): bool
	{
		if (metadata_exists('post', $order_id, self::VOUCHER_CANCEL_POST_META_KEY)) {
			return true;
		}
		return false;
	}

	/**
	 * Set voucher state as canceled.
	 *
	 * @param int $order_id
	 * @return void
	 */
	public static function cancelVoucher(int $order_id): void
	{
		update_post_meta($order_id, self::VOUCHER_CANCEL_POST_META_KEY, '1');
	}

	/**
	 * If voucher is canceled then delete canceled info form post meta.
	 *
	 * @param int $order_id
	 * @return void
	 */
	public static function restoreVoucher(int $order_id): void
	{
		if (!metadata_exists('post', $order_id, self::VOUCHER_CANCEL_POST_META_KEY)) {
			return;
		}
		delete_post_meta($order_id, self::VOUCHER_CANCEL_POST_META_KEY);
	}


	/**
	 * Return voucher code of order given by order_id
	 *
	 * @param int $order_id
	 * @return string|null
	 */
	public static function getVoucherCodeRandomPartByOrderId(int $order_id): ?string
	{

		$code = get_post_meta($order_id, self::VOUCHER_CODE_POST_META_KEY, true);

		if (!$code) {
			return null;
		}

		$code_random_part = explode('/', $code)[1];

		return $code_random_part;
	}
}
