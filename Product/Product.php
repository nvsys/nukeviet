<?php

/**
 * @Project NUKEVIET 4.x
 * @Author DANGDINHTU (dlinhvan@gmail.com)
 * @Copyright (C) 2013 Webdep24.com. All rights reserved
 * @Blog http://dangdinhtu.com
 * @Developers http://developers.dangdinhtu.com/
 * @License GNU/GPL version 2 or any later version
 * @Createdate  Mon, 20 Oct 2014 14:00:59 GMT
 */

namespace NukeViet\Product;

use NukeViet\Product\General;

use NukeViet\Product\Tax;

class Product extends General
{

	public $getProduct = array();
	public $getProductImages = array();
	public $getProductOptions = array();
 
	public function __construct( $productRegistry = array() )
	{
		global $ProductTax;

		parent::__construct( $productRegistry );

		if( $ProductTax )
		{
			$this->tax = $ProductTax;
		}
		else
		{
			$this->tax = new Tax( $productRegistry );
		}

	}

	public function getProduct( $product_id )
	{
		global $db;

		if( intval( $product_id ) == 0 ) return false;

		$discount = '(SELECT price FROM ' . $this->table . '_product_discount pd2 
		WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = ' . ( int )$this->config['config_customer_group_id'] . ' AND pd2.quantity = 1 AND ((pd2.date_start = 0 OR pd2.date_start < ' . NV_CURRENTTIME . ') AND (pd2.date_end = 0 OR pd2.date_end > ' . NV_CURRENTTIME . ') ) 
		ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) discount,';

		$special = '(SELECT price FROM ' . $this->table . '_product_special ps 
		WHERE ps.product_id = p.product_id AND ps.customer_group_id = ' . ( int )$this->config['config_customer_group_id'] . ' AND ((ps.date_start = 0 OR ps.date_start < ' . NV_CURRENTTIME . ') AND (ps.date_end = 0 OR ps.date_end > ' . NV_CURRENTTIME . ')) 
		ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) special,';

		$reward = '(SELECT points FROM ' . $this->table . '_product_reward pr 
		WHERE pr.product_id = p.product_id AND customer_group_id = ' . ( int )$this->config['config_customer_group_id'] . ') reward,';

		$stock_status = '(SELECT ss.name FROM ' . $this->table . '_stock_status ss 
		WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = ' . ( int )$this->current_language_id . ') stock_status,';

		$brand_description = '(SELECT md.name FROM ' . $this->table . '_brand_description md 
		WHERE p.brand_id = md.brand_id AND md.language_id = ' . ( int )$this->current_language_id . ') brand,';

		$weight_class_description = '(SELECT wcd.unit FROM ' . $this->table . '_weight_class_description wcd 
		WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = ' . ( int )$this->current_language_id . ') weight_class,';

		$length_class_description = '(SELECT lcd.unit FROM ' . $this->table . '_length_class_description lcd 
		WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = ' . ( int )$this->current_language_id . ') length_class,';

		// $review_avg = '(SELECT AVG(rating) AS total FROM ' . $this->table . '_review r1
		// WHERE r1.product_id = p.product_id AND r1.status = 1 GROUP BY r1.product_id) AS rating,';

		// $review_count  = '(SELECT COUNT(*) AS total FROM ' . $this->table . '_review r2
		// WHERE r2.product_id = p.product_id AND r2.status = 1 GROUP BY r2.product_id) AS reviews,';

		// $review_count  = '(SELECT AVG(rating) AS total FROM ' . $this->table . '_review r1
		// WHERE r1.product_id = p.product_id AND r1.status = 1 GROUP BY r1.product_id) AS rating,';

		$select = $discount . $special . $reward . $stock_status . $brand_description . $weight_class_description . $length_class_description;

		$sql = 'SELECT DISTINCT *, pd.name name, pd.alias alias, p.image, p.thumb, p.shipping, p.minimum, ' . $select . ' p.status FROM ' . $this->table . '_product p 
		LEFT JOIN ' . $this->table . '_product_description pd ON (p.product_id = pd.product_id) 
		WHERE p.product_id = ' . ( int )$product_id . ' AND pd.language_id = ' . ( int )$this->current_language_id . '  AND p.status = 1 AND p.date_added <= ' . NV_CURRENTTIME;

		$result = $db->query( $sql );

		if( $result->rowCount() )
		{
			$data = $result->fetch();

			return array(
				'product_id' => $data['product_id'],
				'name' => $data['name'],
				'alias' => $data['alias'],
				'description' => $data['description'],
				'meta_title' => $data['meta_title'],
				'meta_description' => $data['meta_description'],
				'category_id' => $data['category_id'],
				'quantity' => $data['quantity'],
				'stock_status' => $data['stock_status'],
				'image' => $data['image'],
				'thumb' => $data['thumb'],
				'brand_id' => $data['brand_id'],
				'brand' => $data['brand'],
				'minimum' => $data['minimum'],
				'price' => ( $data['discount'] ? $data['discount'] : $data['price'] ),
				'special' => $data['special'],
				'reward' => $data['reward'],
				'points' => $data['points'],
				'shipping' => $data['shipping'],
				'tax_class_id' => $data['tax_class_id'],
				'subtract' => $data['subtract'],
				'viewed' => $data['viewed'],
				'status' => $data['status'],
				'date_added' => $data['date_added'],
				'date_modified' => $data['date_modified'] );
		}
		else
		{
			return false;
		}
	}

	public function getProductImages( $product_id )
	{
		global $db;
		$result = $db->query( 'SELECT * FROM ' . $this->table . '_product_image WHERE product_id = ' . ( int )$product_id . ' ORDER BY sort_order ASC' );
		$product_image = array();
		while( $rows = $result->fetch() )
		{
			$product_image[] = $rows;
		}
		$result->closeCursor();

		return $product_image;
	}

	public function getProductOptions( $product_id )
	{
		global $db;
		$product_option_data = array();

		$result1 = $db->query( 'SELECT * FROM ' . $this->table . '_product_option po 
		LEFT JOIN ' . $this->table . '_option o 
			ON (po.option_id = o.option_id) 
		LEFT JOIN ' . $this->table . '_option_description od 
			ON (o.option_id = od.option_id) 
		WHERE po.product_id = ' . ( int )$product_id . ' 
			AND od.language_id = ' . ( int )$this->current_language_id . ' 
		ORDER BY o.sort_order' );

		while( $product_option = $result1->fetch() )
		{
			$product_option_value_data = array();

			$result = $db->query( 'SELECT * FROM ' . $this->table . '_product_option_value pov 
			LEFT JOIN ' . $this->table . '_option_value ov ON (pov.option_value_id = ov.option_value_id) 
			LEFT JOIN ' . $this->table . '_option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) 
			WHERE pov.product_id = ' . ( int )$product_id . ' AND pov.product_option_id = ' . ( int )$product_option['product_option_id'] . ' 
			AND ovd.language_id = ' . ( int )$this->current_language_id . ' 
			ORDER BY ov.sort_order' );

			while( $product_option_value = $result->fetch() )
			{
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id' => $product_option_value['option_value_id'],
					'name' => $product_option_value['name'],
					'image' => $product_option_value['image'],
					'quantity' => $product_option_value['quantity'],
					'subtract' => $product_option_value['subtract'],
					'price' => $product_option_value['price'],
					'price_prefix' => $product_option_value['price_prefix'],
					'weight' => $product_option_value['weight'],
					'weight_prefix' => $product_option_value['weight_prefix'] );
			}
			$result->closeCursor();

			$product_option_data[] = array(
				'product_option_id' => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id' => $product_option['option_id'],
				'name' => $product_option['name'],
				'type' => $product_option['type'],
				'value' => $product_option['value'],
				'required' => $product_option['required'] );
		}
		$result1->closeCursor();

		return $product_option_data;
	}
 
	 

}

if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );
