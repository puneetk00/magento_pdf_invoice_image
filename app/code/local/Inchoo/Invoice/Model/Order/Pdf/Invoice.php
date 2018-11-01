<?php

/**
 * Inchoo PDF rewrite to add products images
 * Original: Sales Order Invoice PDF model
 *
 * @category   Inchoo
 * @package    Inhoo_Invoice
 * @author     Mladen Lotar - Inchoo <mladen.lotar@inchoo.net>
 */
class Inchoo_Invoice_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice
{
	protected function insertImage($image, $x1, $y1, $x2, $y2, $width, $height, &$page)
	{
		if (!is_null($image)) {
			try{
				$width = (int) $width;
				$height = (int) $height;

				//Get product image and resize it
				$imagePath = Mage::helper('catalog/image')->init($image, 'image')
					->keepAspectRatio(true)
					->keepFrame(false)
					->resize($width, $height)
					->__toString();

				$imageLocation = substr($imagePath,strlen(Mage::getBaseUrl()));
				$image = Zend_Pdf_Image::imageWithPath($imageLocation);
				//Draw image to PDF
				$page->drawImage($image, $x1, $y1, $x2, $y2);
			}
			catch (Exception $e) {
				return false;
			}
		}
	}

	public function getPdf($invoices = array())
	{
		$width = 1000;
		$height = 1000;
		$this->_beforeGetPdf();
		$this->_initRenderer('invoice');

		$pdf = new Zend_Pdf();
		$this->_setPdf($pdf);
		$style = new Zend_Pdf_Style();
		$this->_setFontBold($style, 10);

		foreach ($invoices as $invoice) {
			if ($invoice->getStoreId()) {
				Mage::app()->getLocale()->emulate($invoice->getStoreId());
			}
			$page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
			$pdf->pages[] = $page;

			$order = $invoice->getOrder();

			/* Add image */
			$this->insertLogo($page, $invoice->getStore());

			/* Add address */
			$this->insertAddress($page, $invoice->getStore());

			/* Add head */
			$this->insertOrder($page, $order, Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId()));

			$page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
			$this->_setFontRegular($page);
			$page->drawText(Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId(), 35, 780, 'UTF-8');

			/* Add table */
			$page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
			$page->setLineWidth(0.5);

			$page->drawRectangle(25, $this->y, 570, $this->y -15);
			$this->y -=10;

			/* Add table head */
			$page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
			$page->drawText(Mage::helper('sales')->__('Products'), 35, $this->y, 'UTF-8');
			//Added for product image
			$page->drawText(Mage::helper('sales')->__('Product Image'), 245, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('SKU'), 325, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Price'), 380, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Qty'), 430, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Tax'), 480, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Subtotal'), 535, $this->y, 'UTF-8');

			$this->y -=15;

			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

			/* Add body */
			foreach ($invoice->getAllItems() as $item){
				if ($item->getOrderItem()->getParentItem()) {
					continue;
				}

				if ($this->y < 15) {
					$page = $this->newPage(array('table_header' => true));
				}

				/* Draw item */
				$page = $this->_drawItem($item, $page, $order);

				/* Draw product image */
				$productId = $item->getOrderItem()->getProductId();
				$image = Mage::getModel('catalog/product')->load($productId);
				$this->insertImage($image, 245, (int)($this->y + 15), 310, (int)($this->y+65), $width, $height, $page);
			}

			/* Add totals */
			$page = $this->insertTotals($page, $invoice);

			if ($invoice->getStoreId()) {
				Mage::app()->getLocale()->revert();
			}
		}
		$this->_afterGetPdf();

		return $pdf;
	}

	public function newPage(array $settings = array())
	{
		/* Add new table head */
		$page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
		$this->_getPdf()->pages[] = $page;
		$this->y = 800;

		if (!empty($settings['table_header'])) {
			$this->_setFontRegular($page);
			$page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
			$page->setLineWidth(0.5);
			$page->drawRectangle(25, $this->y, 570, $this->y-15);
			$this->y -=10;

			$page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
			$page->drawText(Mage::helper('sales')->__('Product'), 35, $this->y, 'UTF-8');
			//Added for product image
			$page->drawText(Mage::helper('sales')->__('Product Image'), 245, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('SKU'), 325, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Price'), 380, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Qty'), 430, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Tax'), 480, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Subtotal'), 535, $this->y, 'UTF-8');

			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
			$this->y -=20;
		}

		return $page;
	}

}
