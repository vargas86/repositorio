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
 * @category    Ced;
 * @package     Ced_CsMarketplace
 * @author 		CedCommerce Core Team <coreteam@cedcommerce.com>
 * @copyright   Copyright CedCommerce (http://cedcommerce.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ced_CsMarketplace_VpaymentsController extends Ced_CsMarketplace_Controller_AbstractController {
	
	 /**
     * Check order view availability
     *
     * @param   Mage_Sales_Model_Order $order
     * @return  bool
     */
    protected function _canViewPayment($payment)
    {
		if(!$this->_getSession()->getVendorId()) return;
		$vendorId = Mage::getSingleton('customer/session')->getVendorId();
		$paymentId = $payment->getId();
		
		
		$collection = Mage::getModel('csmarketplace/vpayment')->getCollection();
		$collection->addFieldToFilter('id', $paymentId)
					->addFieldToFilter('vendor_id', $vendorId);

		if(count($collection)>0){
			return true;
		}else{
			return false;
		}
    }
	
	/**
     * Try to load valid order by order_id and register it
     *
     * @param int $orderId
     * @return bool
     */
    protected function _loadValidPayment($paymentId = null)
    {
		if(!$this->_getSession()->getVendorId()) return;
        if (null === $paymentId) {
            $paymentId = (int) $this->getRequest()->getParam('payment_id');
        }
        if (!$paymentId) {
            $this->_forward('noRoute');
            return false;
        }
		$payment = Mage::getModel('csmarketplace/vpayment')->load($paymentId);
        
        if ($this->_canViewPayment($payment)) {
	        Mage::register('current_vpayment', $payment);
            return true;
        } else {
            $this->_redirect('*/*');
        }
        return false;
    }
	
	
	/**
	 * Default vendor products list page
	 */
	public function indexAction() {
		if(!$this->_getSession()->getVendorId()) return;
		$this->loadLayout ();
		$this->_initLayoutMessages ( 'customer/session' );
		$this->_initLayoutMessages ( 'catalog/session' );		
		$this->getLayout ()->getBlock ( 'head' )->setTitle ( Mage::helper('csmarketplace')->__ ( 'Transactions' ) );
		$this->renderLayout ();
	}
	
	/**
	 * Payments view page
	 */
	public function viewAction() {
        if(!$this->_getSession()->getVendorId()) return;
		if (!$this->_loadValidPayment()) {
            return;
        }
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');
				$this->getLayout ()->getBlock ( 'head' )->setTitle ( Mage::helper('csmarketplace')->__ ( 'Transaction Details' ) );

        $navigationBlock = $this->getLayout()->getBlock('csmarketplace_vendor_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('csmarketplace/vpayments/');
        }

        $this->renderLayout();
	}
	
	
	
	/**
     * Export Payment Action
     */
    public function exportCsvAction()
    {
		if(!$this->_getSession()->getVendorId()) return;
		$filename = 'vendor_transactions.csv';
        $content = Mage::helper('csmarketplace/payment')->getVendorCommision();
        $this->_prepareDownloadResponse($filename, $content);

	}
	
	
	
	/**
     * Print Order Action
     */
    public function filterAction()
    {
		if(!$this->_getSession()->getVendorId()) return;
		$reset_filter = $this->getRequest()->getParam('reset_filter');
		$params = $this->getRequest()->getParams();
		
		if($reset_filter==1)
			Mage::getSingleton('core/session')->uns('payment_filter');
		else if(!isset($params['p']) && !isset($params['limit']) &&  is_array($params) ){
			Mage::getSingleton('core/session')->setData('payment_filter',$params);
		 }

         $this->loadLayout();
         $this->renderLayout();
    }
}
