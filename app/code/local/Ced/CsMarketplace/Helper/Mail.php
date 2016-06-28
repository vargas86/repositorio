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
 
class Ced_CsMarketplace_Helper_Mail extends Mage_Core_Helper_Abstract
{
	
	const XML_PATH_ACCOUNT_EMAIL_IDENTITY 		= 'ced_csmarketplace/vendor/email_identity';
	const XML_PATH_ACCOUNT_CONFIRMED_EMAIL_TEMPLATE       = 'ced_csmarketplace/vendor/account_confirmed_template';
	const XML_PATH_ACCOUNT_REJECTED_EMAIL_TEMPLATE     = 'ced_csmarketplace/vendor/account_rejected_template';
	const XML_PATH_ACCOUNT_DELETED_EMAIL_TEMPLATE     = 'ced_csmarketplace/vendor/account_deleted_template';
	
	const XML_PATH_SHOP_ENABLED_EMAIL_TEMPLATE       = 'ced_csmarketplace/vendor/shop_enabled_template';
	const XML_PATH_SHOP_DISABLED_EMAIL_TEMPLATE     = 'ced_csmarketplace/vendor/shop_disabled_template';
	
	const XML_PATH_PRODUCT_EMAIL_IDENTITY 		= 'ced_vproducts/general/email_identity';
	const XML_PATH_PRODUCT_CONFIRMED_EMAIL_TEMPLATE       = 'ced_vproducts/general/product_approved_template';
	const XML_PATH_PRODUCT_REJECTED_EMAIL_TEMPLATE     = 'ced_vproducts/general/product_rejected_template';
	const XML_PATH_PRODUCT_DELETED_EMAIL_TEMPLATE     = 'ced_vproducts/general/product_deleted_template';
		
	const XML_PATH_ORDER_EMAIL_IDENTITY 		= 'ced_vorders/general/email_identity';
	const XML_PATH_ORDER_NEW_EMAIL_TEMPLATE       = 'ced_vorders/general/order_new_template';
	const XML_PATH_ORDER_CANCEL_EMAIL_TEMPLATE       = 'ced_vorders/general/order_cancel_template';
	
	/**
	 * Can send new order notification email
	 * @param int $storeId
	 * @return boolean
	 */
	public function canSendNewOrderEmail($storeId){
		return Mage::getStoreConfig('ced_vorders/general/order_email_enable',Mage::app()->getStore()->getStoreId());
	}
	
	/**
	 * Can send new order notification email
	 * @param int $storeId
	 * @return boolean
	 */
	public function canSendCancelOrderEmail($storeId){
		return Mage::getStoreConfig('ced_vorders/general/order_cancel_email_enable',Mage::app()->getStore()->getStoreId());
	}
	

	/**
	 * Send account status change email to vendor
	 *
	 * @param string $type
	 * @param string $backUrl
	 * @param string $storeId
	 * @throws Mage_Core_Exception
	 * @return Mage_Customer_Model_Customer
	 */
	public function sendAccountEmail($status, $backUrl = '', $vendor, $storeId = '0')
	{
		$types = array(
				Ced_CsMarketplace_Model_Vendor::VENDOR_APPROVED_STATUS   => self::XML_PATH_SHOP_ENABLED_EMAIL_TEMPLATE,  
				Ced_CsMarketplace_Model_Vendor::VENDOR_DISAPPROVED_STATUS => self::XML_PATH_ACCOUNT_REJECTED_EMAIL_TEMPLATE,
				Ced_CsMarketplace_Model_Vendor::VENDOR_DELETED_STATUS => self::XML_PATH_ACCOUNT_DELETED_EMAIL_TEMPLATE,
		);
		if (!isset($types[$status])) 
			return;
	
		if (!$storeId) {
			$customer = Mage::getModel('customer/customer')->load( $vendor->getCustomerId());
			$storeId=$customer->getStoreId();
		}
	
		$this->_sendEmailTemplate($types[$status], self::XML_PATH_ACCOUNT_EMAIL_IDENTITY,
				array('vendor' => $vendor, 'back_url' => $backUrl), $storeId);
		return $this;
	}
	
	/**
	 * Send shop enable/disable to vendor
	 *
	 * @param string $type
	 * @param string $backUrl
	 * @param string $storeId
	 * @throws Mage_Core_Exception
	 * @return Mage_Customer_Model_Customer
	 */
	public function sendShopEmail($status, $backUrl = '', $vendor, $storeId = '0')
	{
		$types = array(
				Ced_CsMarketplace_Model_Vshop::ENABLED   => self::XML_PATH_SHOP_ENABLED_EMAIL_TEMPLATE,
				Ced_CsMarketplace_Model_Vshop::DISABLED => self::XML_PATH_SHOP_DISABLED_EMAIL_TEMPLATE,
		);
		if (!isset($types[$status]))
			return;
	
		if (!$storeId) {
			$customer = Mage::getModel('customer/customer')->load( $vendor->getCustomerId());
			$storeId=$customer->getStoreId();
		}
	
		$this->_sendEmailTemplate($types[$status], self::XML_PATH_ACCOUNT_EMAIL_IDENTITY,
				array('vendor' => $vendor, 'back_url' => $backUrl), $storeId);
		return $this;
	}
	
	/**
	 * Send order notification email to vendor
	 * @param Mage_Sales_Model_Order $order
	 */
	public function sendOrderEmail(Mage_Sales_Model_Order $order,$type){
		
		$types = array(
				Ced_CsMarketplace_Model_Vorders::ORDER_NEW_STATUS   =>self::XML_PATH_ORDER_NEW_EMAIL_TEMPLATE,
				Ced_CsMarketplace_Model_Vorders::ORDER_CANCEL_STATUS => self::XML_PATH_ORDER_CANCEL_EMAIL_TEMPLATE,
		);
		if (!isset($types[$type]))
			return;
		$storeId = $order->getStore()->getId();
		if($type==Ced_CsMarketplace_Model_Vorders::ORDER_NEW_STATUS){
			if (!$this->canSendNewOrderEmail($storeId)) {
				return;
			}
		}
		if($type==Ced_CsMarketplace_Model_Vorders::ORDER_CANCEL_STATUS){
			if (!$this->canSendCancelOrderEmail($storeId)) {
				return;
			}
		}
	
		$vendorIds = array();
		foreach($order->getAllItems() as $item){
			if(!in_array($item->getVendorId(), $vendorIds)) $vendorIds[] = $item->getVendorId();
		}
		if($type==Ced_CsMarketplace_Model_Vorders::ORDER_CANCEL_STATUS){
			// Start store emulation process
			$storeId =Mage::app()->getStore()->getId();
			$appEmulation = Mage::getSingleton('core/app_emulation');
			$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
		}
		
		try {
			// Retrieve specified view block from appropriate design package (depends on emulated store)
			$paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
			->setIsSecureMode(true);
			$paymentBlock->getMethod()->setStore($storeId);
			$paymentBlockHtml = $paymentBlock->toHtml();
		} catch (Exception $exception) {
			// Stop store emulation process
			if($type==Ced_CsMarketplace_Model_Vorders::ORDER_CANCEL_STATUS)
				$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
			throw $exception;
		}
		
		
		
		foreach($vendorIds as $vendorId){
			$vendor = Mage::getModel('csmarketplace/vendor')->load($vendorId);
			if(!$vendor->getId()){
				continue;
			}

			$vorder = Mage::getModel('csmarketplace/vorders')->loadByField(array('order_id','vendor_id'),array($order->getIncrementId(),$vendorId));
			if(Mage::registry('current_order')!='')
				Mage::unregister('current_order');
			if(Mage::registry('current_vorder')!='')
				Mage::unregister('current_vorder');
			Mage::register('current_order', $order);
			Mage::register('current_vorder', $vorder);
				
			$this->_sendEmailTemplate($types[$type], self::XML_PATH_ORDER_EMAIL_IDENTITY,
					array('vendor' => $vendor,'order' => $order, 'billing' => $order->getBillingAddress(),'payment_html'=>$paymentBlockHtml),null);
		}
		if($type==Ced_CsMarketplace_Model_Vorders::ORDER_CANCEL_STATUS){
			// Stop store emulation process
			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
		}
	
	}
	
	
	/**
	 * Send product status change notification email to vendor
	 * @param Mage_Catalog_Model_Product $product,int $status
	 */
	public function sendProductNotificationEmail($ids,$status){
		$types = array(
				Ced_CsMarketplace_Model_Vproducts::APPROVED_STATUS   => self::XML_PATH_PRODUCT_CONFIRMED_EMAIL_TEMPLATE,  
				Ced_CsMarketplace_Model_Vproducts::NOT_APPROVED_STATUS => self::XML_PATH_PRODUCT_REJECTED_EMAIL_TEMPLATE, 
				Ced_CsMarketplace_Model_Vproducts::DELETED_STATUS => self::XML_PATH_PRODUCT_DELETED_EMAIL_TEMPLATE,
		);
		
		if (!isset($types[$status]))
			return;
		
		$vendorIds = array();
		foreach($ids as $productId){
			$vendorId=Mage::getModel('csmarketplace/vproducts')->getVendorIdByProduct($productId);
			$vendorIds[$vendorId][] = $productId;
		}
		
		foreach($vendorIds as $vendorId=>$productIds){
			$vendor = Mage::getModel('csmarketplace/vendor')->load($vendorId);
			if(!$vendor->getId()){
				continue;
			}
			$products=array();
			$vproducts=array();
			foreach($productIds as $productId){
				if($status!=Ced_CsMarketplace_Model_Vproducts::DELETED_STATUS){
					$product=Mage::getModel('catalog/product')->load($productId);
					if($product && $product->getId())
						$products[0][]=$product;
				}
				$products[1][$productId]=Mage::getModel('csmarketplace/vproducts')->getCollection()->addFieldToFilter('product_id',array('eq'=>$productId))->getFirstItem();
			}		
			$customer = Mage::getModel('customer/customer')->load( $vendor->getCustomerId());
			$storeId=$customer->getStoreId();
			$this->_sendEmailTemplate($types[$status], self::XML_PATH_PRODUCT_EMAIL_IDENTITY,
					array('vendor' => $vendor,'products' => $products),$storeId);
		}
	}
	
	/**
	 * Send corresponding email template
	 *
	 * @param string $emailTemplate configuration path of email template
	 * @param string $emailSender configuration path of email identity
	 * @param array $templateParams
	 * @param int|null $storeId
	 * @return Mage_Customer_Model_Customer
	 */
	protected function _sendEmailTemplate($template, $sender, $templateParams = array(), $storeId = null)
	{
		/** @var $mailer Mage_Core_Model_Email_Template_Mailer */
		$vendor=$templateParams['vendor'];
		$mailer = Mage::getModel('core/email_template_mailer');
		$emailInfo = Mage::getModel('core/email_info');
		$emailInfo->addTo($vendor->getEmail(), $vendor->getName());
		$mailer->addEmailInfo($emailInfo);
	
		// Set all required params and send emails
		$mailer->setSender(Mage::getStoreConfig($sender, $storeId));
		$mailer->setStoreId($storeId);
		$mailer->setTemplateId(Mage::getStoreConfig($template, $storeId));
		$mailer->setTemplateParams($templateParams);
		$mailer->send();
		return $this;
	}
	
	
	
}

