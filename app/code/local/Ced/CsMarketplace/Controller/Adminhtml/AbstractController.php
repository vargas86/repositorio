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
 
class Ced_CsMarketplace_Controller_Adminhtml_AbstractController extends Mage_Adminhtml_Controller_Action
{
	/**
     * Action vendor shop url availability
     */
	public function checkAvailabilityAction() {
		/* if(Mage::app()->getRequest()->getParam('is_vendor')==1){ */
			$id = $this->getRequest()->getParam('id',0);
			$venderData = Mage::app()->getRequest()->getParam('vendor',array());
			$json = Mage::getModel('csmarketplace/vendor')->checkAvailability($venderData,$id);
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(json_encode($json));
		/* } */
		return;
	}
	
	/**
     * ACL check
     * @return bool
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getControllerName()) {
			case 'vendor' : return $this->vendorAcl(); break;
			case 'attributes' : return $this->attributesAcl(); break;
			case 'vproducts' : return $this->vproductsAcl(); break;
			case 'vendororder' : return $this->vendorordersAcl(); break;
			case 'vpayments' : return $this->vpaymentsAcl(); break;
			default : return Mage::getSingleton('admin/session')->isAllowed('csmarketplace'); break;
		}
    }
	
	/**
     * ACL check for VendorController.php
     * @return bool
     */
	protected function vendorAcl() {
		
		switch ($this->getRequest()->getActionName()) {
            default: return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/vendor'); break;
        }
	}
	
	/**
     * ACL check for AttributesController.php
     * @return bool
     */
	protected function attributesAcl() {
		
		switch ($this->getRequest()->getActionName()) {
            default: return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/attributes'); break;
        }
	}
	
	/**
     * ACL check for VproductsController.php
     * @return bool
     */
	protected function vproductsAcl() {
		
		switch ($this->getRequest()->getActionName()) {
			case 'index' : return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/vendorproduct/all'); break;
			case 'pending' : return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/vendorproduct/pending'); break;
			case 'approved' : return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/vendorproduct/approved'); break;
			default: return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/vendorproduct'); break;
        }
	}
	
	/**
     * ACL check for VendororderController.php
     * @return bool
     */
	protected function vendororderAcl() {
		
		switch ($this->getRequest()->getActionName()) {
           default: return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/vendororder'); break;
        }
	}
	
	/**
     * ACL check for VpaymentsController.php
     * @return bool
     */
	protected function vpaymentsAcl() {
		
		switch ($this->getRequest()->getActionName()) { 
            default: return Mage::getSingleton('admin/session')->isAllowed('csmarketplace/vendorcommission'); break;
        }
	}
}
