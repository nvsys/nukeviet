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
 
if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );

class free extends shops_global
{
	private $free_config = null;
	private $currency = null;
	private $product = null;
	
	public function __construct( $productRegistry )
	{
 
		parent::__construct( $productRegistry ); 
		
		$this->free_config = $this->getSetting( 'free', $this->config_store_id );
		
		$this->currency = new shops_currency( $productRegistry );

		$this->product = new shops_product( $productRegistry );

	}

	function getQuote( $address )
	{

		$query = $this->db->query( 'SELECT * FROM ' . $this->table . '_zone_to_geo_zone WHERE geo_zone_id = ' . ( int )$this->free_config['free_geo_zone_id'] . ' AND country_id = ' . ( int )$address['country_id'] . ' AND (zone_id = ' . ( int )$address['zone_id'] . ' OR zone_id = 0)' );

		if( ! $this->free_config['free_geo_zone_id'] )
		{
			$status = true;
		}
		elseif( $query->rowCount() )
		{
			$status = true;
		}
		else
		{
			$status = false;
		}

		if( $this->product->getSubTotal() < $this->free_config['free_total'] )
		{
			$status = false;
		}

		$method_data = array();

		if( $status )
		{
			$language = $this->getLangSite( 'free', 'shipping' );
			
			$quote_data = array();

			$quote_data['free'] = array(
				'code' => 'free.free',
				'title' => $language['text_description'],
				'cost' => 0.00,
				'tax_class_id' => 0,
				'text' => $this->currency->format( 0.00 ) );

			$method_data = array(
				'code' => 'free',
				'title' => $language['text_title'],
				'quote' => $quote_data,
				'sort_order' => $this->free_config['free_sort_order'],
				'error' => false );
		}

		return $method_data;
	}
}
