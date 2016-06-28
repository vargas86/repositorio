<?php

class Medma_Catalog_Block_Catalog_Product_Featured extends Mage_Catalog_Block_Product_List{

	public function isFeatured($product){

		$collection = Mage::getResourceModel('catalog/product_collection');
		$collection->addAttributeToSelect('medma_is_featured');
		$collection->addAttributeToFilter('entity_id', $product->getId());
		
		$item = $collection->getFirstItem();
		if($item->hasData('medma_is_featured') && $item->getData('medma_is_featured')){
			return true;
		}
		return false;	
	}

     
}
