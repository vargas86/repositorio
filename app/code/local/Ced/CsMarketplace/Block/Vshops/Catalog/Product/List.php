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
 * CsMarketplace profile product list Blpck
 *
 * @category   Ced
 * @package    Ced_CsMarketplace
 * @author 	   CedCommerce Core Team <coreteam@cedcommerce.com>
 */
class Ced_CsMarketplace_Block_Vshops_Catalog_Product_List extends Mage_Catalog_Block_Product_List
{
	
	 protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
        	$layer = $this->getLayer();
        	
            $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            
			// if this is a product view page
			if (Mage::registry('product')) {
				// get collection of categories this product is associated with
				$categories = Mage::registry('product')->getCategoryCollection()
				->setPage(1, 1)
				->load();
				// if the product is associated with any category
				if ($categories->count()) {
					// show products from this category
					$this->setCategoryId(current($categories->getIterator()));
				}
			}
			
			$origCategory = null;
			if ($this->getCategoryId()) {
				$category = Mage::getModel('catalog/category')->load($this->getCategoryId());
				if ($category->getId()) {
					$origCategory = $layer->getCurrentCategory();
					$layer->setCurrentCategory($category);
				}
			}
			$vendorId=Mage::registry('current_vendor')->getId();
			$collection = Mage::getModel('csmarketplace/vproducts')->getVendorProducts(Ced_CsMarketplace_Model_Vproducts::APPROVED_STATUS,$vendorId,0);
			$products=array();
			$statusarray=array();
			foreach($collection as $data){
				/* if($data->getIsMultiseller() && $data->getParentId())
					array_push($products,$data->getParentId());
				else */
				array_push($products,$data->getProductId());
			}
			//print_r($products);die;
			/*$productcollection = Mage::getModel('catalog/product')
								->getCollection()*/
			$productcollection = $layer->getProductCollection()
								->addAttributeToSelect(Mage::getSingleton('catalog/config')
								->getProductAttributes())
								->addAttributeToFilter('entity_id',array('in'=>$products))
								->addStoreFilter(Mage::app()->getStore()->getId())
								->addAttributeToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
			
			//Custom Code Start
			$cat_id = $this->getRequest()->getParam('cat-fil');
			if(isset($cat_id)) {
				$productcollection->joinField(
						'category_id', 'catalog/category_product', 'category_id',
						'product_id = entity_id', null, 'left'
				)
				->addAttributeToSelect('*')
				->addAttributeToFilter('category_id', array(
						array('finset', array('in'=>explode(',', $cat_id)))
				));
			}
			//end
			
            $this->_productCollection = $productcollection;
            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
            
            if ($origCategory) {
            	$layer->setCurrentCategory($origCategory);
            }
        }
        $layer = $this->getLayer();
        $layer->prepareProductCollection($this->_productCollection);

        return $this->_productCollection;
    }
	
	/**
     * Prepare Sort By fields from Category Data
     *
     * @return Mage_Catalog_Block_Product_List
     */
   /*  public function prepareSortableFieldsByCategory1() {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($this->_getConfig()->getAttributeUsedForSortByArray());
        }
        $availableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($categorySortBy = Mage::getStoreConfig('catalog/frontend/default_sort_by', $this->getStoreId())) {
                if (!$availableOrders) {
                    $availableOrders = $this->_getConfig()->getAttributeUsedForSortByArray();
                }
                if (isset($availableOrders[$categorySortBy])) {
                    $this->setSortBy($categorySortBy);
                }
            }
        }

        return $this;
    } */
	
}
