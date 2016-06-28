<?php 
class Ced_CsMarketplace_Block_Vshops_Left extends Mage_Catalog_Block_Navigation
{
	public function getCategoriesHtml($category, $flag=false, $lvl=0) {
		if (is_numeric($category)) {
			$_category = Mage::getModel('catalog/category')->load($category);
			$_categories=$_category->getChildrenCategories();
		} elseif ($category && $category->getId()) {
			$_categories=$category->getChildrenCategories();
		}
		$category_filter = $this->getRequest()->getParam('cat-fil');
		$cat_fil = array();
		if(isset($category_filter))
			$cat_fil = explode(',', $category_filter);
		if(count($_categories)>0){
			$html = '<ul class="level-'.$lvl.' vshop-left-cat-filter">';
			$level = $lvl+1;
			foreach ($_categories as $value) {
				$html .= '<li>';
				$html .= '<input onchange="filterProductsByCategory(this)" type="checkbox" name="cat-fil" data-uncheckurl="'.$this->getUncheckFilterUrl($value->getId()).'" value="'.$this->getCheckFilterUrl($value->getId()).'" ';
				if(in_array($value->getId(), $cat_fil))
					$html .= 'checked="checked"';
				$html .= '>';
				$label = $value->getName()." (".Mage::getModel('csmarketplace/vproducts')->getProductCount($value->getId(),Ced_CsMarketplace_Model_Vproducts::AREA_FRONTEND).")";
				$html .= '<label>'.$label.'</label>';
				$html .= $this->getCategoriesHtml($value->getId(), true, $level);
				$html .= '</li>';
			}
			$html .= '</ul>';
			return $html;
		}
	}
	
	public function getCheckFilterUrl($category_id)
	{
		$urlParams = array('_current' => true, '_escape' => true, '_use_rewrite' => true);
		
		$category_filter = $this->getRequest()->getParam('cat-fil');
		
		if(isset($category_filter)){
			$cat_fil = explode(',', $category_filter);
			if(!in_array($category_id, $cat_fil)){
				$urlParams['_query'] = array('cat-fil'=> $category_filter.','.$category_id);
			}
		}
		else
			$urlParams['_query'] = array('cat-fil'=> $category_id);
		
		return $this->getUrl('*/*/*', $urlParams);
	}
	
	public function getUncheckFilterUrl($category_id)
	{
		$urlParams = array('_current' => true, '_escape' => true, '_use_rewrite' => true);
		
		$category_filter = $this->getRequest()->getParam('cat-fil');
		
		if(isset($category_filter)){
			$cat_fil = explode(',', $category_filter);
			if(in_array($category_id, $cat_fil)){
				$cat_fil = $this->remove_array_item($cat_fil, $category_id);
				if(!count($cat_fil))
					return trim($this->getBaseUrl(), '/').rtrim($this->getRequest()->getOriginalPathInfo(),'/');
				elseif(count($cat_fil)>0)
					$urlParams['_query'] = array('cat-fil'=> implode(',',$cat_fil));
			}
		}
		return $this->getUrl('*/*/*', $urlParams);
	}
	
	public function remove_array_item( $array, $item ) {
		$index = array_search($item, $array);
		if ( $index !== false ) {
			unset( $array[$index] );
		}
	
		return $array;
	}
}
?>