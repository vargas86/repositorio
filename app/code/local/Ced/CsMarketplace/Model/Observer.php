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

/**
 * Observer model
 *
 * @category    Ced
 * @package     Ced_CsMarketplace
 * @author 		CedCommerce Core Team <coreteam@cedcommerce.com>
 */
 
Class Ced_CsMarketplace_Model_Observer
{
	
	/**
	 * Predispath admin action controller
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function preDispatch(Varien_Event_Observer $observer)
	{
		if (Mage::getSingleton('admin/session')->isLoggedIn()) {
			$feedModel  = Mage::getModel('csmarketplace/feed');
			/* @var $feedModel Ced_Core_Model_Feed */
			$feedModel->checkUpdate();
	
		}
	}
	
	/**
	 * Get current store
	 * @return Mage_Core_Model_Store
	 */
	 public function getStore() {
		$storeId = (int) Mage::app()->getRequest()->getParam('store', 0);
        if($storeId)
			return Mage::app()->getStore($storeId);
		else 
			return Mage::app()->getStore();
	 }
	 
	 /**
     * Get customer seesion
     *
     * @return Mage_Customer_Model_Session
     */
	 protected function _getSession() {
		return Mage::getSingleton('customer/session');
	 }
	 
	 
	 /**
	  *Notify Customer Account share Change 
	  *
	  */
	 public function coreConfigSaveAfter($observer)
	 {
	 	$groups = $observer->getEvent()->getDataObject()->getGroups();
	 	$customer_share=isset($groups['account_share']['fields']['scope']['value'])?$groups['account_share']['fields']['scope']['value']:Mage::getStoreConfig(Mage_Customer_Model_Config_Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE);
	 	$config = new Mage_Core_Model_Config();
	 	if($customer_share!=''&&$customer_share!=Mage::getStoreConfig(Mage_Customer_Model_Config_Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE))
    		$config->saveConfig(Ced_CsMarketplace_Model_Vendor::XML_PATH_VENDOR_WEBSITE_SHARE,1);
	 }
	 
	 /**
	  *Vendor registration 
	  *
	  */
	public function VendorRegistration($observer){
		if(Mage::app()->getRequest()->getParam('is_vendor')==1){
			$venderData = Mage::app()->getRequest()->getParam('vendor');
			$customerData = $observer->getCustomer();
			try {
				$vendor = Mage::getModel('csmarketplace/vendor')
						   ->setCustomer($customerData)
						   ->register($venderData);
				if(!$vendor->getErrors()) {
					$vendor->save();
					if($vendor->getStatus() == Ced_CsMarketplace_Model_Vendor::VENDOR_NEW_STATUS) {
						$this->_getSession()->addSuccess(Mage::helper('csmarketplace')->__('Your vendor application has been Pending.'));
					} else if ($vendor->getStatus() == Ced_CsMarketplace_Model_Vendor::VENDOR_APPROVED_STATUS) {
						$this->_getSession()->addSuccess(Mage::helper('csmarketplace')->__('Your vendor application has been Approved.'));
					}
				} elseif ($vendor->getErrors()) {
					foreach ($vendor->getErrors() as $error) {
						$this->_getSession()->addError($error);
					}
					$this->_getSession()->setFormData($venderData);
				} else {
					$this->_getSession()->addError(Mage::helper('csmarketplace')->__('Your vendor application has been denied'));
				}
			} catch (Exception $e) {
				Mage::helper('csmarketplace')->logException($e);
			}
		}
	}	
	
	/**
	 *Set Vendor id in item table
	 *
	 */
	public function salesQuoteItemSetVendorId($observer)
		{
			$quoteItem = $observer->getQuoteItem();
			$product = $observer->getProduct();
			$cs_vendor_id = Mage::getModel('csmarketplace/vproducts')->getVendorIdByProduct($product->getId());
			if($cs_vendor_id)
				$quoteItem->setVendorId($cs_vendor_id);
		}
	/**
    * Save Vendor Order Information 
    */
	public function setVendorSalesOrder($observer)
		{
			$order = $observer->getOrder();
			$vorder=Mage::getModel('csmarketplace/vorders')->getCollection()->addFieldToFilter('order_id',$order->getIncrementId())->getFirstItem();
			if($vorder->getId())
			{
				return $this;
			}
			$products = $order->getAllItems();
			$baseToGlobalRate=$order->getBaseToGlobalRate()?$order->getBaseToGlobalRate():1;
			$vendorsBaseOrder = array();
			$vendorQty = array();
			

			Mage::helper('csmarketplace')->logProcessedData($order->getData('increment_id'), Ced_CsMarketplace_Helper_Data::SALES_ORDER_CREATE);
			 foreach ($products as $item) {
			    if($item->getVendorId() > 0) {
					$price = 0;
					$price = $item->getBaseRowTotal()
							+ $item->getBaseTaxAmount()
							+ $item->getBaseHiddenTaxAmount()
							+ $item->getBaseWeeeTaxAppliedRowAmount()
							- $item->getBaseDiscountAmount();
					$vendorsBaseOrder[$item->getVendorId()]['order_total'] = isset($vendorsBaseOrder[$item->getVendorId()]['order_total'])?($vendorsBaseOrder[$item->getVendorId()]['order_total'] + $price) : $price;
					$vendorsBaseOrder[$item->getVendorId()]['item_commission'][$item->getId()] = $price;									;
					$vendorsBaseOrder[$item->getVendorId()]['order_items'][] = $item;
					$vendorQty[$item->getVendorId()] = isset($vendorQty[$item->getVendorId()])?$vendorQty[$item->getVendorId()] + $item->getQty() :  $item->getQty();
				   
					$logData = $item->getData();
					unset($logData['product']);
					Mage::helper('csmarketplace')->logProcessedData($logData, Ced_CsMarketplace_Helper_Data::SALES_ORDER_ITEM);
				}
			 }
			
			
			 
			 
			foreach($vendorsBaseOrder  as $vendorId => $baseOrderTotal){
				
			 try{	
					/* $order->setVendorItemsData($baseOrderTotal['order_items']); */
					$qty = isset($vendorQty[$vendorId])? $vendorQty[$vendorId] : 0;
					$vorder = Mage::getModel('csmarketplace/vorders');
					$vorder->setVendorId($vendorId);
					$vorder->setOrder($order);
					$vorder->setOrderId($order->getIncrementId());
					$vorder->setCurrency($order->getGlobalCurrencyCode());
					$vorder->setOrderTotal(Mage::helper('directory')->currencyConvert($baseOrderTotal['order_total'], $order->getBaseCurrencyCode(), $order->getGlobalCurrencyCode()));
					$vorder->setBaseCurrency($order->getBaseCurrencyCode());
					$vorder->setBaseOrderTotal($baseOrderTotal['order_total']);
					$vorder->setBaseToGlobalRate($baseToGlobalRate);
					$vorder->setProductQty($qty);
					$vorder->setBillingCountryCode($order->getBillingAddress()->getData('country_id'));
					if($order->getShippingAddress())
						$vorder->setShippingCountryCode($order->getShippingAddress()->getData('country_id'));
					$vorder->setItemCommission($baseOrderTotal['item_commission']);
					$vorder->collectCommission();
					
					Mage::dispatchEvent('ced_csmarketplce_vorder_shipping_save_before',array('vorder'=>$vorder));
					
					$vorder->save();
					//Mage::helper('csmarketplace')->logProcessedData($vorder->getData(), Ced_CsMarketplace_Helper_Data::VORDER_CREATE);
				}
				catch(Exception $e){
					Mage::helper('csmarketplace')->logException($e);
				}
				
				
				
			}	
			try {
				if($order){
					$vorders = Mage::getModel('csmarketplace/vorders')->getCollection()->addFieldToFilter('order_id',array('eq'=>$order->getIncrementId()));
					if (count($vorders) > 0)
						Mage::helper('csmarketplace/mail')->sendOrderEmail($order,Ced_CsMarketplace_Model_Vorders::ORDER_NEW_STATUS);
				}
				$orders = $observer->getOrders();
				if($orders && is_array($orders)){
					foreach($orders as $order){
						if($order){
							$vorders = Mage::getModel('csmarketplace/vorders')->getCollection()->addFieldToFilter('order_id',array('eq'=>$order->getIncrementId()));
							if (count($vorders) > 0)
								Mage::helper('csmarketplace/mail')->sendOrderEmail($order,Ced_CsMarketplace_Model_Vorders::ORDER_NEW_STATUS);
						}
					}
				}
			}
			catch(Exception $e) {
				Mage::helper('csmarketplace')->logException($e);
			}		
			
		}
	/**
     * Cancel the asscociated vendor order
     *
     * @param Varien_Object $observer
     * @return Ced_CsMarketplace_Model_Observer
     */
	public function orderCancelAfter($observer){
		$order = $observer->getEvent()->getOrder();
		Mage::helper('csmarketplace')->logProcessedData($order->getData('increment_id'), Ced_CsMarketplace_Helper_Data::SALES_ORDER_CANCELED);
		try {
			$vorders = Mage::getModel('csmarketplace/vorders')
							->getCollection()
							->addFieldToFilter('order_id',array('eq'=>$order->getIncrementId()));
			if (count($vorders) > 0) {
				foreach ($vorders as $vorder) {
					if($vorder->canCancel()) {
						$vorder->setOrderPaymentState(Mage_Sales_Model_Order_Invoice::STATE_CANCELED);
						$vorder->setPaymentState(Ced_CsMarketplace_Model_Vorders::STATE_CANCELED);
						$vorder->save();
					} else if ($vorder->canMakeRefund()) {
						$vorder->setPaymentState(Ced_CsMarketplace_Model_Vorders::STATE_REFUND);
						$vorder->save();
					}
					Mage::helper('csmarketplace')->logProcessedData($vorder->getData(), Ced_CsMarketplace_Helper_Data::VORDER_CANCELED);
				}
				Mage::helper('csmarketplace/mail')->sendOrderEmail($order,Ced_CsMarketplace_Model_Vorders::ORDER_CANCEL_STATUS);

			}
			return $this;
		} catch(Exception $e) {
			Mage::helper('csmarketplace')->logException($e);
		}
		
		
	}
	
	/**
     * Refund the asscociated vendor order
     *
     * @param Varien_Object $observer
     * @return Ced_CsMarketplace_Model_Observer
     */
	public function orderCreditmemoRefund($observer){
		$order = $observer->getDataObject();
		try {
			if ($order->getState() == Mage_Sales_Model_Order::STATE_CLOSED || ((float)$order->getBaseTotalRefunded() && (float)$order->getBaseTotalRefunded() >= (float)$order->getBaseTotalPaid())) {
				$vorders = Mage::getModel('csmarketplace/vorders')
								->getCollection()
								->addFieldToFilter('order_id',array('eq'=>$order->getIncrementId())); 
				if (count($vorders) > 0) {
					foreach ($vorders as $vorder) {
						if($vorder->canCancel()) {
							$vorder->setOrderPaymentState(Mage_Sales_Model_Order_Invoice::STATE_CANCELED);
							$vorder->setPaymentState(Ced_CsMarketplace_Model_Vorders::STATE_CANCELED);
							$vorder->save();
						} else if($vorder->canMakeRefund()) {
							$vorder->setPaymentState(Ced_CsMarketplace_Model_Vorders::STATE_REFUND);
							$vorder->save();
						}
					}
					
				}
			}
			return $this;
		} catch(Exception $e) {
			Mage::helper('csmarketplace')->logException($e);
		}
	}
		
	/**
	 * Send new order notification email to vendor
	 * @param Varien_Event_Observer $observer
	 */
	/* public function checkoutSubmitAllAfter(Varien_Event_Observer $observer){
		$order = $observer->getOrder();
		try {
			if($order){
				$vorders = Mage::getModel('csmarketplace/vorders')->getCollection()->addFieldToFilter('order_id',array('eq'=>$order->getIncrementId()));
				if (count($vorders) > 0) 
					Mage::helper('csmarketplace/mail')->sendOrderEmail($order,Ced_CsMarketplace_Model_Vorders::ORDER_NEW_STATUS);
			}
			$orders = $observer->getOrders();
			if($orders && is_array($orders)){
				foreach($orders as $order){
					if($order){
						$vorders = Mage::getModel('csmarketplace/vorders')->getCollection()->addFieldToFilter('order_id',array('eq'=>$order->getIncrementId()));
						if (count($vorders) > 0) 
							Mage::helper('csmarketplace/mail')->sendOrderEmail($order,Ced_CsMarketplace_Model_Vorders::ORDER_NEW_STATUS);
					}
				}
			}
		}
		catch(Exception $e) {
			Mage::helper('csmarketplace')->logException($e);
		}
	} */

		
	protected function _vendorForm($attribute) {
		$store = $this->getStore();
		return Mage::getModel('csmarketplace/vendor_form')
							->getCollection()
							->addFieldToFilter('attribute_id',array('eq'=>$attribute->getAttributeId()))
							->addFieldToFilter('attribute_code',array('eq'=>$attribute->getAttributeCode()))
							->addFieldToFilter('store_id',array('eq'=>$store->getId()));
	}
	
	
	/**
	 *Reflect Product data to Vproducts table
	 *
	 */
	public function saveVproductData($observer) {
		$productData=Mage::app()->getRequest()->getParams();
		$store = (int)Mage::app()->getRequest()->getParam('store')?(int)Mage::app()->getRequest()->getParam('store'):0;
		if(isset($productData['id'])){
			$product = $observer->getProduct();
			Mage::getModel('csmarketplace/vproducts')->setStoreId($store)->processPostSave(Ced_CsMarketplace_Model_Vproducts::EDIT_PRODUCT_MODE,$product,$productData);
		}
				
	}
	
	/**
	 *Reflect Product status Changes
	 *
	 */
	public function saveStatusChange($observer) {
		$productIds=(array)Mage::app()->getRequest()->getParam('product');
		$status = (int)Mage::app()->getRequest()->getParam('status');
		$store = (int)Mage::app()->getRequest()->getParam('store')?(int)Mage::app()->getRequest()->getParam('store'):0;
		if($status){
 			$collection=Mage::getModel('csmarketplace/vproducts')->getCollection()->addFieldToFilter('product_id',array('in'=>$productIds));
 			if(count($collection)>0){
				foreach ($collection as $row){
						$row->setProductId($row->getProductId());
						$row->setStoreId($store);
						$row->setStatus($status);
				}
 			}
		}
	}
	
	/**
	 *Reflect Product data to Vproducts table
	 *
	 */
	public function saveVproductAttributesData($observer) {	
		$productIds=Mage::helper('adminhtml/catalog_product_edit_action_attribute')->getProductIds();
		if(is_array($productIds)){
			$inventoryData      = Mage::app()->getRequest()->getParam('inventory', array());
			$attributesData     = Mage::app()->getRequest()->getParam('attributes', array());
			$websiteRemoveData  = Mage::app()->getRequest()->getParam('remove_website_ids', array());
			$websiteAddData     = Mage::app()->getRequest()->getParam('add_website_ids', array());
			if($attributesData)
				$productData['product']=$attributesData;
			if($inventoryData)
				$productData['product']['stock_data']=$inventoryData;
			$vproductsModel=Mage::getModel('csmarketplace/vproducts');
			$collection=$vproductsModel->getCollection()->addFieldToFilter('product_id',array('in'=>$productIds));
			if(count($collection)>0){
				foreach ($collection as $row){					
						$oldWebsiteIds=explode(',',$row->getWebsiteIds());
						$websiteIds=implode(',',array_unique(array_filter(array_merge(array_diff($oldWebsiteIds,$websiteRemoveData),$websiteAddData))));
						$row->addData ( $productData['product'] );
						$row->addData ( $productData['product']['stock_data'] );
						if(isset($productData['product']['status'])){
							$row->setproductId($row->getProductId());
							$row->setStoreId(Mage::app()->getRequest()->getParam('store',0));
							$row->setStatus($productData['product']['status']);
						}
						$vproductsModel->extractNonEditableData($row);
						$row->addData(array('website_ids'=>$websiteIds));
						$row->save();
				}
			}
		}
	}
	
	/**
	 *Delete Vproduct data
	 *
	 */
	public function deleteVproductData($observer) {
		$productId=Mage::app()->getRequest()->getParam('id');
		if($productId)
			Mage::getModel('csmarketplace/vproducts')->changeVproductStatus(array($productId),Ced_CsMarketplace_Model_Vproducts::DELETED_STATUS);
	}
	
	/**
	 *Reflect Product data to Vproducts table
	 *
	 */
	public function deleteMassVproductData($observer) {
		$productIds=Mage::app()->getRequest()->getParam('product');
		if(is_array($productIds))
			Mage::getModel('csmarketplace/vproducts')->changeVproductStatus($productIds,Ced_CsMarketplace_Model_Vproducts::DELETED_STATUS);
	}
	
	
	/**
	 *Change Order State on invoice
	 *
	 */
	public function changeOrderPaymentState($observer) {
		$invoice = $observer->getDataObject();
		$order = $invoice->getOrder();
		Mage::helper('csmarketplace')->logProcessedData($order->getData('increment_id'), Ced_CsMarketplace_Helper_Data::SALES_ORDER_PAYMENT_STATE_CHANGED);
		if ($order->getBaseTotalDue() == 0) {
			$vorders = Mage::getModel('csmarketplace/vorders')
							->getCollection()
							->addFieldToFilter('order_id',array('eq'=>$order->getIncrementId()));
			if (count($vorders) > 0) {
				foreach ($vorders as $vorder) {
					try{
						$vorder->setOrderPaymentState(Mage_Sales_Model_Order_Invoice::STATE_PAID);
						$vorder->save();
						Mage::helper('csmarketplace')->logProcessedData($vorder->getData(), Ced_CsMarketplace_Helper_Data::VORDER_PAYMENT_STATE_CHANGED);

						}
						catch(Exception $e){
						Mage::helper('csmarketplace')->logException($e);
						}
				}
			}					 
		}
		return $this;
		//$invocies = $order->getInvoiceCollection(); 
	}
	
	/**
	 *Delete Vendor and assoiciated Product
	 *
	 */
	public function deleteVendor($observer){
		$customerId=Mage::app()->getRequest()->getParam('id');
		if($customerId){
			$vendor= Mage::getModel('csmarketplace/vendor')->loadByCustomerId($customerId);
			if($vendor && $vendor->getId()){
				Mage::getModel('csmarketplace/vproducts')->deleteVendorProducts($vendor->getId());
				Mage::helper('csmarketplace/mail')->sendAccountEmail(Ced_CsMarketplace_Model_Vendor::VENDOR_DELETED_STATUS,'',$vendor);
				$vendor->delete();
				
			}
		}
	}
	
	/**
	 *mass delete Vendor
	 *
	 */
	public function massDeleteVendor($observer){
		$customerids=Mage::app()->getRequest()->getParam('customer');
		foreach ($customerids as $customerId){
			$vendor= Mage::getModel('csmarketplace/vendor')->loadByCustomerId($customerId);
			if($vendor && $vendor->getId()){
				Mage::getModel('csmarketplace/vproducts')->deleteVendorProducts($vendor->getId());
				Mage::helper('csmarketplace/mail')->sendAccountEmail(Ced_CsMarketplace_Model_Vendor::VENDOR_DELETED_STATUS,'',$vendor);
				$vendor->delete();
			}
		}
	
	}
	
	/**
     * setVendorEmail
     *
     */
	public function setVendorEmail($observer){
	
		$customer = $observer->getCustomer();
		$vendor=Mage::getModel('csmarketplace/vendor')->loadByCustomerId($customer->getId());
		if($vendor)
		$vendor->setSettingFromCustomer(true)->setEmail($customer->getEmail())->save();
	}
		
}