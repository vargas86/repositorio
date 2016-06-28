<?php
/*
 * is_featured attribute on catalog_product entity
 */

$installer = $this;
$entityTypeId = $installer->getEntityTypeId('catalog_product');
$attrCode = 'medma_is_featured';

$installer->startSetup();
$installer->removeAttribute($entityTypeId, $attrCode);
$installer->addAttribute($entityTypeId, $attrCode, array(
	'type'                       => 'int',
    'label'                      => 'Is Featured',
    'input'                      => 'select',
    'source'                     => 'eav/entity_attribute_source_boolean',
    //'sort_order'                 => 2,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'group'                      => 'General',	
)); 
$installer->endSetup();
