<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
  * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Ced
 * @package     Ced_CsMarketplace
 * @author 		CedCommerce Core Team <coreteam@cedcommerce.com>
 * @copyright   Copyright CedCommerce (http://cedcommerce.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ced_CsMarketplace_Block_Vorders_View_Totals_Tax extends Mage_Tax_Block_Sales_Order_Tax
{
    /**
     * Get full information about taxes applied to order
     *
     * @return array
     */
    public function getFullTaxInfo()
    {
        /** @var $source Mage_Sales_Model_Order */
        $source = $this->getOrder();
        $info = array();
        if ($source instanceof Mage_Sales_Model_Order) {

            $rates = Mage::getModel('sales/order_tax')->getCollection()->loadByOrder($source)->toArray();
            $info  = Mage::getSingleton('tax/calculation')->reproduceProcess($rates['items']);

            /**
             * Set right tax amount from invoice
             * (In $info tax invalid when invoice is partial)
             */
            /** @var $blockInvoice Mage_Adminhtml_Block_Sales_Order_Invoice_Totals */
            $blockInvoice = $this->getLayout()->getBlock('tax');
            /** @var $invoice Mage_Sales_Model_Order_Invoice */
            $invoice = $blockInvoice->getSource();
            $items = $invoice->getItemsCollection();
            $i = 0;
            /** @var $item Mage_Sales_Model_Order_Invoice_Item */
            foreach ($items as $item) {
                $info[$i]['hidden']           = $item->getHiddenTaxAmount();
                $info[$i]['amount']           = $item->getTaxAmount();
                $info[$i]['base_amount']      = $item->getBaseTaxAmount();
                $info[$i]['base_real_amount'] = $item->getBaseTaxAmount();
                $i++;
            }
        }

        return $info;
    }

    /**
     * Display tax amount
     *
     * @return string
     */
    public function displayAmount($amount, $baseAmount)
    {
        return $this->displayPrices(
            $this->getSource(), $baseAmount, $amount, false, '<br />'
        );
    }
    
    /**
     * Get "double" prices html (block with base and place currency)
     *
     * @param   Varien_Object $dataObject
     * @param   float $basePrice
     * @param   float $price
     * @param   bool $strong
     * @param   string $separator
     * @return  string
     */
    public function displayPrices($dataObject, $basePrice, $price, $strong = false, $separator = '<br/>')
    {
    	$order = false;
    	if ($dataObject instanceof Mage_Sales_Model_Order) {
    		$order = $dataObject;
    	} else {
    		$order = $dataObject->getOrder();
    	}
    
    	if ($order && $order->isCurrencyDifferent()) {
    		$res.= $order->formatBasePrice($basePrice);
    		$res.= $separator;
    		$res.= '['.$order->formatPrice($price).']';
    	} elseif ($order) {
    		$res = $order->formatPrice($price);
    		if ($strong) {
    			$res = $res;
    		}
    	} else {
    		$res = Mage::app()->getStore()->formatPrice($price);
    		if ($strong) {
    			$res = $res;
    		}
    	}
    	return $res;
    }

    /**
     * Get store object for process configuration settings
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::app()->getStore();
    }
}
