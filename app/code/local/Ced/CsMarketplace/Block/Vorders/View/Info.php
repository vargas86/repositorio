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
class Ced_CsMarketplace_Block_Vorders_View_Info extends Mage_Sales_Block_Order_Info
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('csmarketplace/vorders/view/info.phtml');
    }

    public function getLinks()
    {
        $this->checkLinks();
		 unset($this->_links['invoice']);
        return $this->_links;
    }

 	private function checkLinks()
    {
        $order = $this->getOrder();
        if (!$order->hasInvoices()) {
            unset($this->_links['invoice']);
        }
        if (!$order->hasShipments()) {
            unset($this->_links['shipment']);
        }
        if (!$order->hasCreditmemos()) {
            unset($this->_links['creditmemo']);
        }
    }
    
    public function getOrderStoreName()
    {
    	if ($this->getOrder()) {
    		$storeId = $this->getOrder()->getStoreId();
    		if (is_null($storeId)) {
    			$deleted = Mage::helper('adminhtml')->__(' [deleted]');
    			return nl2br($this->getOrder()->getStoreName()) . $deleted;
    		}
    		$store = Mage::app()->getStore($storeId);
    		$name = array(
    				$store->getWebsite()->getName(),
    				$store->getGroup()->getName(),
    				$store->getName()
    		);
    		return implode('<br/>', $name);
    	}
    	return null;
    }
}
