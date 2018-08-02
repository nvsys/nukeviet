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

namespace NukeViet\Product\Shipping;

use NukeViet\Product\General;
use NukeViet\Product\Currency;
use NukeViet\Product\cart;
 
class free extends General
{
 
	private $free_config = null;
	private $currency = null;
	private $cart = null;
	
	public function __construct( $productRegistry )
	{
		global $ProductGeneral, $ProductCart, $ProductCurrency, $ProductTax;
		
		parent::__construct( $productRegistry ); 
		
		$this->free_config = $this->getSetting( 'shipping_free', $this->store_id );
 
		if( $ProductCurrency )
		{
			$this->currency = $ProductCurrency;
		}
		else
		{
			$this->currency = new Currency( $productRegistry );
		}
		if( $ProductCart )
		{
			$this->cart = $ProductCart;
		}
		else
		{
			$this->cart = new Cart( $productRegistry );
		}
		
	}

	
	function getQuote( $address )
	{
		global $db;
 
		$query = $db->query( 'SELECT * FROM  '. $this->table .'_zone_to_geo_zone WHERE geo_zone_id = '. intval( $this->free_config['shipping_free_geo_zone_id'] ) .' AND country_id = '. intval( $address['country_id'] ) .' AND (zone_id = '. intval( $address['zone_id'] ) .' OR zone_id = 0)' );

		if( ! $this->free_config['shipping_free_geo_zone_id'] )
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

		if( $this->cart->getSubTotal() < $this->free_config['shipping_free_total'] )
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
				'text' => $this->currency->format( 0.00, $_SESSION[$this->mod_data . '_currency'] ) );

			$method_data = array(
				'code' => 'free',
				'title' => $language['text_title'],
				'quote' => $quote_data,
				'sort_order' => $this->free_config['shipping_free_sort_order'],
				'error' => false );
		}

		return $method_data;
	}	
 	 
}
