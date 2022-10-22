<?php

namespace Vouchers\Vouchers;

use Automattic\WooCommerce\Admin\Overrides\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Vouchers\Vouchers\VoucherService;

/**
 * Class for generating and downloading voucher pdf
 *
 * @package Vouchers\Vouchers
 * @since 1.0
 */
class VoucherPdf
{

	/**
	 * Default options
	 */
	const DEFAULT_FONT = 'DejaVu Sans';
	const DEFAULT_PAPER_SIZE = 'a5';

	/**
	 * @var \WC_Order
	 */
	protected \WC_Order $voucher_order;

	/**
	 * @var string
	 */
	protected string $voucher_code;

	/**
	 * @var string
	 */
	protected string $voucher_owner;


	/**
	 * @var string
	 */
	protected string $voucher_validity_date;

	/**
	 * @param int $voucher_order_id
	 */
	public function __construct(int $voucher_order_id)
	{
		$this->voucher_order = new \WC_Order($voucher_order_id);

		$this->voucher_code = VoucherService::getVoucherCodeByOrderId($this->voucher_order->get_id());
		$this->voucher_owner = VoucherService::getVoucherReceiverByOrderId($this->voucher_order->get_id());
		$this->voucher_validity_date = VoucherService::getVoucherValidityDateByOrderId($this->voucher_order->get_id()) ?? '';
	}

	/**
	 * @return string
	 */
	public function getVoucherCode(): string
	{
		return $this->voucher_code;
	}

	/**
	 * @return string
	 */
	public function getVoucherOwner(): string
	{
		return $this->voucher_owner;
	}

	/**
	 * @return string
	 */
	public function getVoucherValidityDate(): string
	{
		return $this->voucher_validity_date;
	}


	/**
	 * Generate and download voucher pdf file.
	 *
	 * @return void
	 */
	public function generateVoucherPdf(): void
	{

		$dompdf = new Dompdf();

		$html = $this->getVoucherPdfHtml();
		$dompdf->loadHtml($html);

		$dompdf->setPaper('A4', 'landscape');

		$pdf_options = new Options();
		$pdf_options->set('defaultFont', self::DEFAULT_FONT);
		$pdf_options->set('defaultPaperSize', self::DEFAULT_PAPER_SIZE);

		$dompdf->render();

		$dompdf->stream($this->getVoucherFilename() . '.pdf');

	}

	/**
	 * Get voucher pdf html content
	 *
	 * @return string
	 */
	private function getVoucherPdfHtml()
	{

		return '
<!DOCTYPE html>
<html lang="pl">
	<html>
		<head>
    		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<style>
  			body { font-family: DejaVu Sans, sans-serif; }
		</style>
		</head>
		<body>
		<div style="width:800px; margin:0 auto;" >
		<br>
		<br>
		    <h1>
     	  ' . $this->getVoucherCode() . '
    		</h1>
    		<br>
    		<h2>
     	   Właściciel vouchera: ' . $this->getVoucherOwner() . '
    		</h2>
    		 <h2>
     	   Data ważności: ' . $this->getVoucherValidityDate() . '
    		</h2>
    		<br>
    		    		 <h4>
     	   Opis: ' . VoucherService::getVouchersDescription() . '
    		</h4>
    		</div>
		</body>
	</html>';

	}

	/**
	 * Get voucher pdf filename
	 *
	 * @return string
	 */
	private function getVoucherFilename(): string
	{
		return 'voucher-' . VoucherService::getVoucherCodeRandomPartByOrderId($this->voucher_order->get_id());
	}

	/**
	 * Generate and download voucher pdf.
	 *
	 * @param int $order_id
	 * @return void
	 */
	public static function downloadVoucherPdf(int $order_id): void
	{
		$voucher_pdf = new self($order_id);
		$voucher_pdf->generateVoucherPdf();
	}

	/**
	 * Return voucher pdf output.
	 *
	 * @param int $order_id
	 * @return string|null
	 */
	public static function getVoucherPdfOutput(int $order_id): ?string
	{
		$voucher_pdf = new self($order_id);
		$dompdf = new Dompdf();

		$html = $voucher_pdf->getVoucherPdfHtml();
		$dompdf->loadHtml($html);

		$dompdf->setPaper('A4', 'landscape');

		$pdf_options = new Options();
		$pdf_options->set('defaultFont', self::DEFAULT_FONT);
		$pdf_options->set('defaultPaperSize', self::DEFAULT_PAPER_SIZE);

		$dompdf->render();

		return $dompdf->output();

	}


}
