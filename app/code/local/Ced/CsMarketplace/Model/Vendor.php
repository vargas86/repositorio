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
 * Vendor model
 *
 * @category    Ced
 * @package     Ced_CsMarketplace
 * @author 		CedCommerce Core Team <coreteam@cedcommerce.com>
 */
class Ced_CsMarketplace_Model_Vendor extends Ced_CsMarketplace_Model_Abstract
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'ced_csmarketplace_vendor';
	
	protected $_customer = false;
	const VENDOR_NEW_STATUS = 'new';
	const VENDOR_APPROVED_STATUS = 'approved';
	const VENDOR_DISAPPROVED_STATUS = 'disapproved';
	const VENDOR_DELETED_STATUS = 'deleted';
	const VENDOR_SHOP_URL_SUFFIX = '.html';
	const DEFAULT_SORT_BY = 'name';
	
	const XML_PATH_VENDOR_WEBSITE_SHARE = "ced_csmarketplace/vendor/customer_share";
	public $_vendorstatus=null;
	/**
     * Initialize csmarketplace model
     */
    public function _construct() {
        $this->_init('csmarketplace/vendor');
    }

	public function getUrlSuffix() {
		return Mage::getStoreConfig('ced_vseo/general/marketplace_url_suffix');
	}
	
	public function getUrlPath() {
		return Mage::getStoreConfig('ced_vseo/general/marketplace_url_key');
	}
	
	/**
	 * Load vendor by customer id
	 * @params int $customerId
	 * @return Ced_CsMarketplace_Model_Vendor
	 */
	public function loadByCustomerId($customerId) {
		return $this->loadByAttribute('customer_id', $customerId);
	}
	
	/**
	 * Load vendor by vendor/customer email
	 * @params String $email
	 * @return Ced_CsMarketplace_Model_Vendor
	 */
	public function loadByEmail($email) {
		return $this->loadByAttribute('email', $email);
	}
	
	/**
	 * Set customer
	 */
	public function setCustomer($customer) {
		$this->_customer = $customer;
		return $this;
	}
	
	/**
	 * Get customer
	 */
	public function getCustomer() {
		if(!$this->_customer && $this->getCustomerId()) {
			$this->_customer = Mage::getModel('customer/customer')->load($this->getCustomerId());
		}
		return $this->_customer;
	}
	
	/**
     * Check vendor is active|approved
     *
     * @return  bool
     */
    public function getIsActive() { 
		if($this->getData('status') == self::VENDOR_APPROVED_STATUS) return true;
		return false;
    }
	
	/**
     * get vendor shop url key
     *
	 * @param string $shop_url
     * @return  string
     */
	public function getShopUrlKey($shop_url = '') {
		if (strlen($shop_url)) {
			return str_replace($this->getUrlSuffix(),'',trim($shop_url));
		} elseif ($this->getId()) {
			return str_replace($this->getUrlSuffix(),'',trim($this->getShopUrl()));
		} else {
			return $shop_url;
		}
	}
	
	/**
     * get vendor shop url
     *
     * @return  string
     */
	public function getVendorShopUrl() {
		/* $baseUrl = trim(Mage::getUrl('',array('_secure'=>true)));
		if (substr($baseUrl, -1) != '/') $baseUrl .= '/'; */
		$urlpath = $this->getUrlPath();
		$url = $urlpath.'/'.trim($this->getShopUrl()).$this->getUrlSuffix();
		$urlModel = Mage::getModel('core/url');
		
		/* Start */ 
		$customer = $this->getCustomer();
		if($customer && $customer->getId()){
			$customerSharedWebsiteIds = $customer->getSharedWebsiteIds();
			if(isset($customerSharedWebsiteIds[0])) {
				$website = Mage::getModel('core/website')->load($customerSharedWebsiteIds[0]);
				$storeGroup = Mage::getModel('core/store_group')->load($website->getData('default_group_id'));
						
				if($storeGroup && $storeGroup->getId()) {
					$urlModel->setStore($storeGroup->getData('default_store_id'));
				}
			}
		}
		/* End */
		
		$url = $urlModel->getUrl($url,array('_secure'=>true));
		return rtrim(trim("{$url}"),'/');
	}

	
	/**
	 * Register a vendor
	 *
	 */
	public function register($vendorData = array()) {
		
		$customer = $this->getCustomer();
		if($customer && isset($vendorData['public_name']) && isset($vendorData['shop_url'])) {
			//$vendor = Mage::getModel('csmarketplace/vendor')->loadByAttribute('shop_url',$vendorData['shop_url']);
			//if(!$vendor || !$vendor->getId()) {
				if($vendorData && count($vendorData))
					$vendorData = array_merge($vendorData, Mage::helper('csmarketplace/acl')->getDefultAclValues());
				else
					$vendorData = Mage::helper('csmarketplace/acl')->getDefultAclValues();
				$vendorData['name']        = $customer->getName();
				$vendorData['gender']      = $customer->getGender();
				$vendorData['email']       = $customer->getEmail();
				$vendorData['customer_id'] = $customer->getId();
				$vendorData['created_at'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
				$this->addData($vendorData);
				//$this->validate(array_keys($vendorData));
				
				if($this->validate(array_keys($vendorData))) {
					$this->setErrors('');
					return $this;
				} else {
					return $this;
				}
			//}
		}
		
		return false;		
	}
	
	/**
     * Processing object before save data
     *
     * @return Ced_CsMarketplace_Model_Vendor
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
		
		$images = Mage::helper('csmarketplace/image')->UploadImage();
		
		$this->addData($images);
		$customer = $this->getCustomer();
		if($customer) {
			$this->addData(array('website_id'=>$customer->getWebsiteId()));
			
			if($this->getData('email') && !$this->getSettingFromCustomer())
				$customer->setEmail($this->getData('email'))->save();
		}
		if(!$this->getMassFlag()){
			$previousStatus=$this->getOrigData('status');
			if(!$previousStatus) {
				Mage::helper('csmarketplace/mail')->sendAccountEmail($this->getStatus(),'',$this);
			}
			if($previousStatus!=''&&$this->getStatus()!=$previousStatus){
				Mage::getModel('csmarketplace/vproducts')->changeProductsStatus(array($this->getId()),$this->getStatus());
				Mage::helper('csmarketplace/mail')->sendAccountEmail($this->getStatus(),'',$this);
			}
		}
		if ($this->getData('shop_url'))
			$this->setData('shop_url',$this->formatShopUrl($this->getData('shop_url')));
		
		return $this;
    }
	
	
    /**
     * Check for empty values for provided Attribute Code on each entity
     *
     * @param  array $entityIds
     * @param  String $attributeCode
     * @return boolean|null
     */
    public function saveMassAttribute(array $entityIds,array $values)
    {
    	if($values['code']=="status"){
    		if(!isset($values['code']) || !isset($values['value'])) {
    			throw new Mage_Core_Exception(
    				Mage::helper('csmarketplace')->__('New values was missing.')
    			);
    		}
    		if($this->_massCollection == null) {
    			$collection = $this->getResourceCollection()
    			->addAttributeToSelect($values['code'])
    			->addAttributeToFilter('entity_id', array('in'=>$entityIds));
    		} else {
    			$collection = $this->_massCollection;
    		}
    		if (count($collection)) {
    			$vendorIds=array();
    			$this->_massCollection = $collection;
    			foreach ($collection as $model) {
    				$model->load($model->getId());
    				$vendorstatus = $model->getStatus();
    				$model->setData($values['code'],$values['value'])->setMassFlag(true);
					if(!$model->validate(array($values['code']))) {
						if($model->getErrors()) {
							foreach ($model->getErrors() as $error)
								Mage::getSingleton('adminhtml/session')->addError($error);
						}
						continue;
					}
					$model->save();
					if($vendorstatus!=''&&$model->getStatus()!=$vendorstatus){
						$vendorIds[]=$model->getId();
						 Mage::dispatchEvent('vendor_status_changed',
							array('vendor' => $model)
						   );
    					Mage::helper('csmarketplace/mail')->sendAccountEmail($model->getStatus(),'',$model);
    				}
    			}
    			if(count($vendorIds)>0)
    				Mage::getModel('csmarketplace/vproducts')->changeProductsStatus($vendorIds,$values['value']);
    			return true;
    		}
    		return null;
    	}
    	else
    		parent::saveMassAttribute($entityIds,$values);
    }
	
	public function delete() {
		Mage::dispatchEvent($this->_eventPrefix.'_delete_before', array('vendor' => $this));
		parent::delete();
		Mage::dispatchEvent($this->_eventPrefix.'_delete_after', array('vendor' => $this));
	}
    
	/**
     * Return Entity Type instance
     *
     * @return Mage_Eav_Model_Entity_Type
     */
    public function getEntityType() {
        return $this->_getResource()->getEntityType();
    }

    /**
     * Return Entity Type ID
     *
     * @return int
     */
    public function getEntityTypeId() {
        $entityTypeId = $this->getData('entity_type_id');
        if (!$entityTypeId) {
            $entityTypeId = $this->getEntityType()->getId();
            $this->setData('entity_type_id', $entityTypeId);
        }
        return $entityTypeId;
    }
	
	/**
     * Retrieve vendor attributes
     * if $groupId is null - retrieve all vendor attributes
     *
     * @param int  $groupId   Retrieve attributes of the specified group
     * @param bool $skipSuper Not used
     * @return array
     */
    public function getAttributes($groupId = null, $skipSuper = false, $storeId = 0, $visibility = null)
    {
        $typeId = $this->getEntityTypeId();
		//echo 'Entity Type Id: '.$typeId;
        if ($groupId) {
			$vendorAttributes = Mage::getModel('eav/entity_attribute')->getCollection()->setAttributeGroupFilter($groupId);
			if($storeId) $vendorAttributes->setStoreId($storeId);
			if($visibility != null) $vendorAttributes->addFieldToFilter('is_visible',array('gt'=>$visibility));
			Mage::dispatchEvent('ced_csmarketplace_vendor_group_wise_attributes_load_after',array('groupId'=>$groupId,'vendorattributes'=>$vendorAttributes));
			$attributes = array();
            foreach ($vendorAttributes as $attribute) {
                if ($attribute->getData('entity_type_id') == $typeId && $attribute->getData('attribute_code') != 'website_id') {
                    $attributes[] = $attribute;
                }
            }
        } else {
            $attributes = $vendorAttributes;
        }
        return $attributes;
    }
	
	/**
	 * Retrieve All vendor Attributes
	 * @return Ced_CsMarketplace_Model_Resource_Vendor_Attribute_Collection 
	 */
	public function getVendorAttributes() {
		return Mage::getModel('eav/entity_attribute')
								->setEntityTypeId($this->getEntityTypeId())
								->setStoreId(Mage::app()->getStore()->getId())
								->getCollection()
								->addFieldToFilter('entity_type_id',$this->getEntityTypeId());
	}
	
	/**
	 * Retrieve Frontend vendor Attributes
	 * @return Ced_CsMarketplace_Model_Resource_Vendor_Attribute_Collection 
	 */
	public function getFrontendVendorAttributes($editable = 0,$sort = 'ASC') {
		$vendorAttributes = Mage::getModel('csmarketplace/vendor_attribute')
								->setStoreId(Mage::app()->getStore()->getId())
								->getCollection()
								->addFieldToFilter('is_visible',array('eq'=>$editable))
								->setOrder('sort_order',$sort);
		return $vendorAttributes;
	}
	
	/**
     * Retrieve vendor Orders
     *
     * @param int  $vendorId   Retrieve orders
     * @param int $vendorId Not used
     * @return Ced_CsMarketplace_Model_Resource_Vorders_Collection
     */
 	public function getAssociatedOrders($vendorId = 0) { 
		  if(!$vendorId && $this->getId()) $vendorId = $this->getId();
		  $orderGridTable = Mage::getSingleton('core/resource')->getTableName('sales/order_grid');
		  $collection = Mage::getModel('csmarketplace/vorders')->getCollection()->addFieldToFilter('vendor_id',array('eq'=>$vendorId));
		  $collection->getSelect()->join($orderGridTable ,'main_table.order_id LIKE  CONCAT("%",'.$orderGridTable.".increment_id".' ,"%")',array('*'));
		  return $collection;
	 }
	
	public function savePaymentMethods($groups = array(), $vendor_id = 0) {
		if(!$vendor_id && $this->getId()) $vendor_id = $this->getId();
		$section = Ced_CsMarketplace_Model_Vsettings::PAYMENT_SECTION;
		if(strlen($section) > 0 && $vendor_id && count($groups)>0) {
			foreach ($groups as $code=>$values) {
				foreach ($values as $name=>$value) {
					$serialized = 0;
					$key = strtolower($section.'/'.$code.'/'.$name);
					if (is_array($value)){  $value = serialize($value); $serialized = 1; }
					$vendor_id_tmp=Mage::helper('csmarketplace')->getTableKey('vendor_id');
					$key_tmp=Mage::helper('csmarketplace')->getTableKey('key');
					/* print_r(Mage::getModel('csmarketplace/vsettings')->loadByField('key',$key)->getData());die;*/
					$setting = Mage::getModel('csmarketplace/vsettings')->loadByField(array($key_tmp,$vendor_id_tmp),array($key,$vendor_id));
					if ($setting && $setting->getId()) {
						$setting->setVendorId($vendor_id)
								->setGroup($section)
								->setKey($key)
								->setValue($value)
								->setSerialized($serialized)
								->save();
					} else {
						$setting = Mage::getModel('csmarketplace/vsettings');
						$setting->setVendorId($vendor_id)
							->setGroup($section)
							->setKey($key)
							->setValue($value)
							->setSerialized($serialized)
							->save();
					}
				}
			}
		}
	}
	
	/**
     * Retrieve vendor Payment Methods
     *
     * @param int  $vendorId
     * @return Array $methods
     */
	public function getPaymentMethods($vendorId = 0) {
		
		if(!$vendorId && $this->getId()) $vendorId = $this->getId();
		$availableMethods = Mage::getModel('csmarketplace/system_config_source_paymentmethods')->toOptionArray();
		$methods = array();
		if (count($availableMethods)>0) {
			foreach($availableMethods as $method) {
				if (isset($method['value'])) {
					$object = Mage::getModel('csmarketplace/vendor_payment_methods_'.$method['value']);
					if(is_object($object)) {
						$methods[$method['value']] = $object;
					}
				}
			}
		}
		return $methods;
	}
	
	/**
     * Retrieve vendor Payment Methods
     *
     * @param int  $vendorId
     * @return Array $methods
     */
	public function getPaymentMethodsArray($vendorId = 0,$all = true) {
		if(!$vendorId && $this->getId()) $vendorId = $this->getId();
		$methods = $this->getPaymentMethods($vendorId);
		$options = array();
		$flag = true;
		if($all) $options[''] = '';
		if(count($methods) > 0) {
			foreach($methods as $code=>$method) {
				$vendor_id_tmp=Mage::helper('csmarketplace')->getTableKey('vendor_id');
				$key_tmp=Mage::helper('csmarketplace')->getTableKey('key');
				$key = strtolower(Ced_CsMarketplace_Model_Vsettings::PAYMENT_SECTION.'/'.$method->getCode().'/active');
				$setting = Mage::getModel('csmarketplace/vsettings')->loadByField(array($key_tmp,$vendor_id_tmp),array($key,(int)$vendorId));
				if($setting && $setting->getId() &&  $setting->getValue()) {
					$options[$code] = $method->getLabel('label');
				}
			}
		}
		if($all) $options['other'] = Mage::helper('csmarketplace')->__('Other');
		/* print_r($options);die; */
		return $options;
	}
	
	public function getAssociatedPayments($vendorId = 0) {
		
		if(!$vendorId) $vendorId = $this->getId();
		return Mage::getModel('csmarketplace/vpayment')->getCollection()
					->addFieldToFilter('vendor_id',array('eq'=>$vendorId))
					->setOrder('created_at','DESC');
	}
	
	
	
	/**
     * Validate customer attribute values.
     * For existing customer password + confirmation will be validated only when password is set (i.e. its change is requested)
     *
     * @return bool
     */
    public function validate($attribute = null)
    {
		
        $errors = array();
		if ($attribute != null)
			if (!is_array($attribute)) $attribute = array($attribute);
		
		$attributes = $this->getVendorAttributes();
		if(is_array($attribute) && count($attribute) > 0) {
			$attributes->addFieldToFilter('attribute_code',array('in'=>$attribute));
		}
		$tmp = array();
		foreach($attributes as $attribute) {
			$tmp[] = array('Attribute Label'=>$attribute->getFrontend()->getLabel(),'Attribute Code'=>$attribute->getAttributeCode(),'Value'=>$this->getData($attribute->getAttributeCode()));
			$terrors = $this->zendValidate($attribute->getFrontend()->getLabel(),$this->getData($attribute->getAttributeCode()), $attribute->getFrontend()->getClass(), $attribute->getIsRequired());
			foreach($terrors as $terror) {
				$errors[] = $terror;
			}
		}
	
        if (count($errors) == 0) {
            return true;
        } else {
			$this->setErrors($errors);
		}
		
        return false;
    }
	
	/**
	 * Extract non editable vendor attribute data
	 * 
	 */
	public function extractNonEditableData() {
		if ($this->getId()) {
			$nonEditableAttributes = $this->getFrontendVendorAttributes(0,'ASC')->getSelect();
			foreach ($nonEditableAttributes as $attribute) {
				$this->setData($attribute->getAttributeCode(),$this->getOrigData($attribute->getAttributeCode()));
			}
			foreach (Ced_CsMarketplace_Model_Form::$VENDOR_FORM_NONEDITABLE_ATTRIBUTES as $attribute_code) {
				$this->setData($attribute_code,$this->getOrigData($attribute_code));
			}
		}
		return $this;
	}
	
	/**
     * Retrieve vendor Payments
     *
     * @param int  $vendorId   Retrieve payments
     * @return Ced_CsMarketplace_Model_Resource_Vpayment_Collection
     */
	public function getVendorPayments($vendorId = 0) {
		
		if(!$vendorId) $vendorId = $this->getId();
		
		$collection =  Mage::getModel('csmarketplace/vpayment')->getCollection()->addFieldToFilter('vendor_id',array('eq'=>$vendorId));
		 return $collection;
	}
	
	public function getDefaultSortBy() {
		return self::DEFAULT_SORT_BY;
	}
	/**
     * Retrieve Attributes Used for Sort by as array
     * key = code, value = name
     *
     * @return array
     */
    public function getAttributeUsedForSortByArray()
    {
        $options = array(
            self::DEFAULT_SORT_BY  => Mage::helper('csmarketplace')->__('Name')
        );
        // foreach ($this->getAttributesUsedForSortBy() as $attribute) {
            // /* @var $attribute Mage_Eav_Model_Entity_Attribute_Abstract */
            // $options[$attribute->getAttributeCode()] = $attribute->getStoreLabel();
        // }

        return $options;
    }
    
    
    /**
     *Retrieve Website Ids
     *@param $vendor
     * @return array $websiteIds
     */
    public function getWebsiteIds($vendor=null)
    {
    	if(!$vendor && $this->getId()) 
    		$vendor = $this;
    	if (is_numeric($vendor)) {
    		$vendor = $this->load($vendor);
    	} 
    	if ($vendor && $vendor->getId()) {
    		if(Mage::helper('csmarketplace')->isSharingEnabled()){
    			return array_keys(Mage::app()->getWebsites());
    		}
    		else  			
    			return array($vendor->getWebsiteId());
    	}
    	return array();
    }
    
	public function deleteFromGroup()
    {
        $this->_getResource()->deleteFromGroup($this);
        return $this;
    }
	
	public function groupVendorExists()
    {
        $result = $this->_getResource()->groupVendorExists($this);
        return ( is_array($result) && count($result) > 0 ) ? true : false;
    }
	
	public function add()
    {
        $this->_getResource()->add($this);
        return $this;
    }

    public function vendorExists()
    {
        $result = $this->_getResource()->vendorExists($this);
        return ( is_array($result) && count($result) > 0 ) ? true : false;
    }
	
	 /**
     * Get vendor ACL group
     *
     * @return string
     */
    public function getAclGroup()
    {
        return 'U' . $this->getId();
    }
	
	

}
