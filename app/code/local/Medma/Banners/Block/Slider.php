<?php
class Medma_Banners_Block_Slider extends Mage_Core_Block_Template{

	protected $_slides = array(); 

	public function addSlide($imgSrc, $caption, $sortOrder = 0){
		if($imgSrc != 'null'){
			$this->_slides[$sortOrder]['src'] = $imgSrc;
			
		}
		if($caption != 'null'){
			$this->_slides[$sortOrder]['caption'] = $caption;
		}
		
	}

	public function getSlides(){
		return $this->_slides;
	}
}
