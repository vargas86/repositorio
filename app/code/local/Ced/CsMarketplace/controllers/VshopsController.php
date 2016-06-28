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
class Ced_CsMarketplace_VshopsController extends Mage_Core_Controller_Front_Action {
	/**
     * Initialize requested vendor object
     *
     * @return Ced_CsMarketplace_Model_Vendor
     */
    protected function _initVendor()
    {
        Mage::dispatchEvent('csmarketplace_controller_vshops_init_before', array('controller_action' => $this));
        
        if(!Mage::helper('csmarketplace/acl')->isEnabled())
        	return false;
        $shopUrl = Mage::getModel('csmarketplace/vendor')->getShopUrlKey($this->getRequest()->getParam('shop_url',''));
        if (!strlen($shopUrl)) {
            return false;
        }

        $vendor = Mage::getModel('csmarketplace/vendor')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->loadByAttribute('shop_url',$shopUrl);
        if (!Mage::helper('csmarketplace')->canShow($vendor)) {
            return false;
        }
        else if(!Mage::helper('csmarketplace')->isShopEnabled($vendor)){
        	return false;
        }
        //Mage::getSingleton('catalog/session')->setLastVisitedCategoryId($category->getId());
        Mage::register('current_vendor', $vendor);
        //Mage::register('current_entity_key', $category->getPath());

        try {
            Mage::dispatchEvent(
                'csmarketplace_controller_vshops_init_after',
                array(
                    'vendor' => $vendor,
                    'controller_action' => $this
                )
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $vendor;
    }
	
	/**
     * Vendor Shop list action
     */
	public function indexAction() {	
		if(!Mage::helper('csmarketplace/acl')->isEnabled()){
			$this->_forward('noRoute');
			return;
		}
		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
		$title = Mage::getStoreConfig('ced_vshops/general/vshoppage_title',Mage::app()->getStore()->getId())?Mage::getStoreConfig('ced_vshops/general/vshoppage_title',Mage::app()->getStore()->getId()):"CsMarketplace Vendor Shops";
		$this->getLayout()->getBlock('head')->setTitle(Mage::helper('csmarketplace')->__($title));
		if($meta_description = Mage::getStoreConfig('ced_vseo/general/meta_description',Mage::app()->getStore()->getId()))
			$this->getLayout()->getBlock('head')->setDescription($meta_description);
		if($meta_keywords = Mage::getStoreConfig('ced_vseo/general/meta_keywords',Mage::app()->getStore()->getId()))
			$this->getLayout()->getBlock('head')->setKeywords($meta_keywords);
		$this->renderLayout();	
	}
	
	/**
     * Vendor Shop view action
     */
	public function viewAction() {
		if ($vendor = $this->_initVendor()) {
			if(Mage::registry('current_category')==null){
				$category=Mage::getModel('catalog/category')
							->setStoreId(Mage::app()->getStore()->getId())
							->load(Mage::app()->getStore()->getRootCategoryId());
				Mage::register('current_category',$category);
			}
			$this->loadLayout();
			$this->getLayout()->getBlock('head')->setTitle($vendor->getPublicName()." ".Mage::helper('csmarketplace')->__('Shop'));
			if($vendor->getMetaDescription())
				$this->getLayout()->getBlock('head')->setDescription($vendor->getMetaDescription());
			if($vendor->getMetaKeywords())
				$this->getLayout()->getBlock('head')->setKeywords($vendor->getMetaKeywords());
			$this->renderLayout();
		}
        else
        	$this->_forward('noRoute');
		
	}

}
