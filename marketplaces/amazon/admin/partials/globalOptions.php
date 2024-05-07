<?php


$options = array(
	'item_sku'                 => array(
		'name'    => 'Seller Sku',
		'type'    => 'input',
		'tooltip' => 'Stock keeping unit (SKU) is an ID code for tracking products in inventory, often including details like color, size, and fit',
	),
	'brand_name'               => array(
		'name'    => 'Brand Name',
		'type'    => 'input',
		'tooltip' => 'Enter the brand name which you have registered with Amazon',
	),
	'manufacturer'             => array(
		'name'    => 'Manufacturer',
		'type'    => 'input',
		'tooltip' => 'The manufacturer\'s company name should match the UPC assigned to the product found on the item itself',
	),
	'model'                    => array(
		'name'    => 'Model Number',
		'type'    => 'input',
		'tooltip' => 'Amazon Standard Identification Numbers (ASINs) are distinct combinations of 10 letters and/or numbers used for item identification. You can locate the ASIN on the product information page of Amazon.com',
	),
	'part_number'              => array(
		'name'    => 'Part Number',
		'type'    => 'input',
		'tooltip' => 'A Manufacturer Part Number (MPN) is a unique code manufacturers give to identify particular products uniquely',
	),
	'external_product_id'      => array(
		'name'    => 'Product ID',
		'type'    => 'input',
		'tooltip' => 'The ASIN or Product ID acts as a digital fingerprint, enabling seamless navigation through vast inventories and aiding in identifying and categorising items',
	),
	'external_product_id_type' => array(
		'name'    => ' Product ID Type',
		'type'    => 'input',
		'tooltip' => 'Select one of the following options: GCID, UPC, EAN ASIN or ISBN. It\'s one-of-a-kind and is assigned when you add a new product to Amazon\'s list',
		'options' => array(
			'GTIN' => 'GTIN',
			'UPC'  => 'UPC',
			'EAN'  => 'EAN',
			'ISBN' => 'ISBN',
		),
	),


);
