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

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class coupon extends General
{
	public function __construct( $productRegistry )
	{
 
		parent::__construct( $productRegistry );
		
		$this->config_ext = $this->getSetting( 'coupon', $this->store_id );
 
		
	}

	public function getCoupon( $code )
	{

		global $db, $productRegistry, $ProductCart, $globalUserid, $getProducts ;
 
		$status = true;

		$coupon_query = $db->query( 'SELECT * FROM ' . $this->table . '_coupon WHERE code = ' . $db->quote( $code ) . ' AND ((date_start = 0 OR date_start < ' . NV_CURRENTTIME . ') AND (date_end = 0 OR date_end > ' . NV_CURRENTTIME . ')) AND status = 1' )->fetch();

		if( $coupon_query )
		{
			if( $coupon_query['total'] > $ProductCart->getSubTotal() )
			{
				$status = false;
			}

			$coupon_history_query = $db->query( 'SELECT COUNT(*) AS total FROM ' . $this->table . '_coupon_history ch WHERE ch.coupon_id = ' . ( int )$coupon_query['coupon_id'] )->fetchColumn();

			if( $coupon_query['uses_total'] > 0 && ( $coupon_history_query >= $coupon_query['uses_total'] ) )
			{
				$status = false;
			}

			if( $coupon_query['logged'] && ! $globalUserid )
			{
				$status = false;
			}

			if( $globalUserid )
			{
				$coupon_history_query = $db->query( 'SELECT COUNT(*) FROM ' . $this->table . '_coupon_history ch WHERE ch.coupon_id = ' . ( int )$coupon_query['coupon_id'] . ' AND ch.customer_id = ' . ( int )$globalUserid )->fetchColumn();

				if( $coupon_query['uses_customer'] > 0 && ( $coupon_history_query >= $coupon_query['uses_customer'] ) )
				{
					$status = false;
				}
			}

			// Products
			$coupon_product_data = array();

			$result = $db->query( 'SELECT * FROM ' . $this->table . '_coupon_product WHERE coupon_id = ' . ( int )$coupon_query['coupon_id'] );

			while( $product = $result->fetch() )
			{
				$coupon_product_data[] = $product['product_id'];
			}
			$result->closeCursor();
			unset( $result );

			// Categories
			$coupon_category_data = array();

			$result = $db->query( 'SELECT * FROM ' . $this->table . '_coupon_category WHERE coupon_id =' . ( int )$coupon_query['coupon_id'] );

			while( $category = $result->fetch() )
			{
				$coupon_category_data[] = $category['category_id'];
			}
			$result->closeCursor();
			unset( $result );
			$product_data = array();

			if( $coupon_product_data || $coupon_category_data )
			{
				$getProducts = $ProductCart->getProducts();
				
				foreach( $getProducts as $product )
				{
					if( in_array( $product['product_id'], $coupon_product_data ) )
					{
						$product_data[] = $product['product_id'];

						continue;
					}

					foreach( $coupon_category_data as $category_id )
					{
						$coupon_category_query = $db->query( 'SELECT COUNT(*) FROM ' . $this->table . '_product_to_category WHERE product_id = ' . ( int )$product['product_id'] . ' AND category_id = ' . ( int )$category_id )->fetchColumn();

						if( $coupon_category_query )
						{
							$product_data[] = $product['product_id'];

							continue;
						}
					}
				}

				if( ! $product_data )
				{
					$status = false;
				}
			}
		}
		else
		{
			$status = false;
		}

		if( $status )
		{
			return array(
				'coupon_id' => $coupon_query['coupon_id'],
				'code' => $coupon_query['code'],
				'name' => $coupon_query['name'],
				'type' => $coupon_query['type'],
				'discount' => $coupon_query['discount'],
				'shipping' => $coupon_query['shipping'],
				'total' => $coupon_query['total'],
				'product' => $product_data,
				'date_start' => $coupon_query['date_start'],
				'date_end' => $coupon_query['date_end'],
				'uses_total' => $coupon_query['uses_total'],
				'uses_customer' => $coupon_query['uses_customer'],
				'status' => $coupon_query['status'],
				'date_added' => $coupon_query['date_added'] );
		}
	}

	public function getTotal( $total )
	{
		global $ProductTax, $ProductCart, $getProducts;
		
		if( isset( $_SESSION[$this->mod_data . '_coupon'] ) )
		{
			
			$language = $this->getLangSite( 'coupon', 'total' );
			
			$coupon_info = $this->getCoupon( $_SESSION[$this->mod_data . '_coupon'] );
			
			if( $coupon_info )
			{
				
				$getProducts = $ProductCart->getProducts();
				
				$discount_total = 0;

				if( ! $coupon_info['product'] )
				{
					$sub_total = $ProductCart->getSubTotal();
				}
				else
				{
					$sub_total = 0;

					foreach( $getProducts as $product )
					{
						if( in_array( $product['product_id'], $coupon_info['product'] ) )
						{
							$sub_total += $product['total'];
						}
					}
				}

				if( $coupon_info['type'] == 'F' )
				{
					$coupon_info['discount'] = min( $coupon_info['discount'], $sub_total );
				}
				
				
				
				foreach( $getProducts as $product )
				{
					$discount = 0;

					if( ! $coupon_info['product'] )
					{
						$status = true;
					}
					else
					{
						$status = in_array( $product['product_id'], $coupon_info['product'] );
					}

					if( $status )
					{
						if( $coupon_info['type'] == 'F' )
						{
							$discount = $coupon_info['discount'] * ( $product['total'] / $sub_total );
						}
						elseif( $coupon_info['type'] == 'P' )
						{
							$discount = $product['total'] / 100 * $coupon_info['discount'];
						}

						if( $product['tax_class_id'] )
						{
							$tax_rates = $ProductTax->getRates( $product['total'] - ( $product['total'] - $discount ), $product['tax_class_id'] );

							foreach( $tax_rates as $tax_rate )
							{
								if( $tax_rate['type'] == 'P' )
								{
									$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
								}
							}
						}
					}

					$discount_total += $discount;
				}

				if( $coupon_info['shipping'] && isset( $_SESSION[$this->mod_data . '_shipping_method'] ) )
				{
					if( ! empty( $_SESSION[$this->mod_data . '_shipping_method']['tax_class_id'] ) )
					{
						$tax_rates = $ProductTax->getRates( $_SESSION[$this->mod_data . '_shipping_method']['cost'], $_SESSION[$this->mod_data . '_shipping_method']['tax_class_id'] );

						foreach( $tax_rates as $tax_rate )
						{
							if( $tax_rate['type'] == 'P' )
							{
								$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
							}
						}
					}

					$discount_total += $_SESSION[$this->mod_data . '_shipping_method']['cost'];
				}

				// If discount greater than total
				if( $discount_total > $total )
				{
					$discount_total = $total;
				}

				if( $discount_total > 0 )
				{
					$total['xtotals'][] = array(
						'code' => 'coupon',
						'title' => sprintf( $language['text_coupon'], $_SESSION[$this->mod_data . '_coupon'] ),
						'value' => -$discount_total,
						'sort_order' => $this->config_ext['coupon_sort_order'] );

					$total['total'] -= $discount_total;
				}
			}
		}
	}

	public function confirm( $order_info, $order_total )
	{
		global $db;
		$code = '';

		$start = strpos($order_total['title'], '( ') + 1;
		$end = strrpos($order_total['title'], ' )');

		if ($start && $end) {
			$code = substr($order_total['title'], $start, $end - $start);
		}

		if ($code) {
			$coupon_info = $this->getCoupon($code);

			if ($coupon_info) {
				$db->query('INSERT INTO' . $this->table . '_coupon_history SET coupon_id = ' . (int)$coupon_info['coupon_id'] . ', order_id = ' . (int)$order_info['order_id'] . ', customer_id = ' . (int)$order_info['customer_id'] . ', amount = ' . (float)$order_total['value'] . ', date_added = NOW()');
			} else {
				return $this->config['config_fraud_status_id'];
			}
		}
	}

	public function unconfirm($order_id) {
		global $db;
		$db->query('DELETE FROM' . $this->table . '_coupon_history WHERE order_id = ' . (int)$order_id);
	}
}
 