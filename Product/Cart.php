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
use NukeViet\Product\Weight;

class Cart extends General
{
	private $data = array();
	
	public function __construct( $productRegistry = array() )
	{
		global $db, $ProductTax, $ProductWeight, $globalUserid;

		parent::__construct( $productRegistry );
		
		$this->tax = ( $ProductTax ) ? $ProductTax : new Tax( $productRegistry );
		$this->weight = ( $ProductWeight ) ? $ProductWeight : new Weight( $productRegistry );

		
		// Remove all the expired carts with no customer ID
		$db->query( 'DELETE FROM ' . $this->table . '_cart WHERE (api_id > 0 OR customer_id = 0) AND date_added < ' . ( NV_CURRENTTIME - 3600 ) );

		if( $globalUserid )
		{
			// We want to change the session ID on all the old items in the customers cart
			$db->query( 'UPDATE ' . $this->table . '_cart SET session_id = ' . $db->quote( session_id() ) . ' WHERE api_id = 0 AND customer_id = ' . ( int )$globalUserid );

			// Once the customer is logged in we want to update the customers cart
			$result = $db->query( 'SELECT * FROM ' . $this->table . '_cart WHERE api_id = 0 AND customer_id = 0 AND session_id = ' . $db->quote( session_id() ) );

			while( $cart = $result->fetch() )
			{
				$db->query( 'DELETE FROM ' . $this->table . '_cart WHERE cart_id = ' . ( int )$cart['cart_id'] );

				// The advantage of using $this->add is that it will check if the products already exist and increaser the quantity if necessary.
				$this->add( $cart['product_id'], $cart['quantity'], json_decode( $cart['option'] ), $cart['recurring_id'] );
			}
			$result->closeCursor();
			
		}
		
		
	}

	public function getProducts()
	{
		global $db, $ProductTax, $ProductContent, $globalUserid;

		$product_data = array();

		$cart_query = $db->query( 'SELECT * FROM ' . $this->table . '_cart WHERE api_id = ' . ( isset( $_SESSION[$this->mod_data . '_api_id'] ) ? ( int )$_SESSION[$this->mod_data . '_api_id'] : 0 ) . ' AND customer_id = ' . ( int )$globalUserid . ' AND session_id = ' . $db->quote( session_id() ) )->fetchAll();
 
		foreach( $cart_query as $cart )
		{
			$stock = true;

			$product_query = $db->query( 'SELECT * FROM ' . $this->table . '_product_to_store p2s LEFT JOIN ' . $this->table . '_product p ON (p2s.product_id = p.product_id) LEFT JOIN ' . $this->table . '_product_description pd ON (p.product_id = pd.product_id) WHERE p2s.store_id = ' . ( int )$this->store_id . ' AND p2s.product_id = ' . ( int )$cart['product_id'] . ' AND pd.language_id = ' . ( int )$this->current_language_id . ' AND p.date_added <= ' . NV_CURRENTTIME . ' AND p.status = 1' )->fetch();

			if( $product_query && ( $cart['quantity'] > 0 ) )
			{
				$option_price = 0;
				$option_points = 0;
				$option_weight = 0;

				$option_data = array();
				
				$cart['option'] = json_decode( $cart['option'] );
			
				foreach( $cart['option'] as $product_option_id => $value )
				{
					
					$option_query = $db->query( 'SELECT po.product_option_id, po.option_id, od.name, o.type FROM ' . $this->table . '_product_option po LEFT JOIN ' . $this->table . '_option o ON (po.option_id = o.option_id) LEFT JOIN ' . $this->table . '_option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = ' . ( int )$product_option_id . ' AND po.product_id = ' . ( int )$cart['product_id'] . ' AND od.language_id = ' . ( int )$this->current_language_id )->fetch();

					if( $option_query )
					{
						if( $option_query['type'] == 'select' || $option_query['type'] == 'radio' )
						{
							$option_value_query = $db->query( 'SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM ' . $this->table . '_product_option_value pov LEFT JOIN ' . $this->table . '_option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN ' . $this->table . '_option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = ' . ( int )$value . ' AND pov.product_option_id = ' . ( int )$product_option_id . ' AND ovd.language_id = ' . ( int )$this->current_language_id )->fetch();

							if( $option_value_query )
							{
								if( $option_value_query['price_prefix'] == '+' )
								{
									$option_price += $option_value_query['price'];
								}
								elseif( $option_value_query['price_prefix'] == '-' )
								{
									$option_price -= $option_value_query['price'];
								}

								if( $option_value_query['points_prefix'] == '+' )
								{
									$option_points += $option_value_query['points'];
								}
								elseif( $option_value_query['points_prefix'] == '-' )
								{
									$option_points -= $option_value_query['points'];
								}

								if( $option_value_query['weight_prefix'] == '+' )
								{
									$option_weight += $option_value_query['weight'];
								}
								elseif( $option_value_query['weight_prefix'] == '-' )
								{
									$option_weight -= $option_value_query['weight'];
								}

								if( $option_value_query['subtract'] && ( ! $option_value_query['quantity'] || ( $option_value_query['quantity'] < $cart['quantity'] ) ) )
								{
									$stock = false;
								}

								$option_data[] = array(
									'product_option_id' => $product_option_id,
									'product_option_value_id' => $value,
									'option_id' => $option_query['option_id'],
									'option_value_id' => $option_value_query['option_value_id'],
									'name' => $option_query['name'],
									'value' => $option_value_query['name'],
									'type' => $option_query['type'],
									'quantity' => $option_value_query['quantity'],
									'subtract' => $option_value_query['subtract'],
									'price' => $option_value_query['price'],
									'price_prefix' => $option_value_query['price_prefix'],
									'points' => $option_value_query['points'],
									'points_prefix' => $option_value_query['points_prefix'],
									'weight' => $option_value_query['weight'],
									'weight_prefix' => $option_value_query['weight_prefix'] );
							}
						}
						elseif( $option_query['type'] == 'checkbox' && is_array( $value ) )
						{
							foreach( $value as $product_option_value_id )
							{
								$option_value_query = $db->query( 'SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM ' . $this->table . '_product_option_value pov LEFT JOIN ' . $this->table . '_option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = ' . ( int )$product_option_value_id . ' AND pov.product_option_id = ' . ( int )$product_option_id . ' AND ovd.language_id = ' . ( int )$this->current_language_id )->fetch();

								if( $option_value_query )
								{
									if( $option_value_query['price_prefix'] == '+' )
									{
										$option_price += $option_value_query['price'];
									}
									elseif( $option_value_query['price_prefix'] == '-' )
									{
										$option_price -= $option_value_query['price'];
									}

									if( $option_value_query['points_prefix'] == '+' )
									{
										$option_points += $option_value_query['points'];
									}
									elseif( $option_value_query['points_prefix'] == '-' )
									{
										$option_points -= $option_value_query['points'];
									}

									if( $option_value_query['weight_prefix'] == '+' )
									{
										$option_weight += $option_value_query['weight'];
									}
									elseif( $option_value_query['weight_prefix'] == '-' )
									{
										$option_weight -= $option_value_query['weight'];
									}

									if( $option_value_query['subtract'] && ( ! $option_value_query['quantity'] || ( $option_value_query['quantity'] < $cart['quantity'] ) ) )
									{
										$stock = false;
									}

									$option_data[] = array(
										'product_option_id' => $product_option_id,
										'product_option_value_id' => $product_option_value_id,
										'option_id' => $option_query['option_id'],
										'option_value_id' => $option_value_query['option_value_id'],
										'name' => $option_query['name'],
										'value' => $option_value_query['name'],
										'type' => $option_query['type'],
										'quantity' => $option_value_query['quantity'],
										'subtract' => $option_value_query['subtract'],
										'price' => $option_value_query['price'],
										'price_prefix' => $option_value_query['price_prefix'],
										'points' => $option_value_query['points'],
										'points_prefix' => $option_value_query['points_prefix'],
										'weight' => $option_value_query['weight'],
										'weight_prefix' => $option_value_query['weight_prefix'] );
								}
							}
						}
						elseif( $option_query['type'] == 'text' || $option_query['type'] == 'textarea' || $option_query['type'] == 'file' || $option_query['type'] == 'date' || $option_query['type'] == 'datetime' || $option_query['type'] == 'time' )
						{
							$option_data[] = array(
								'product_option_id' => $product_option_id,
								'product_option_value_id' => '',
								'option_id' => $option_query['option_id'],
								'option_value_id' => '',
								'name' => $option_query['name'],
								'value' => $value,
								'type' => $option_query['type'],
								'quantity' => '',
								'subtract' => '',
								'price' => '',
								'price_prefix' => '',
								'points' => '',
								'points_prefix' => '',
								'weight' => '',
								'weight_prefix' => '' );
						}
					}
				}
				
				$price = $product_query['price'];

				// Product Discounts
				$discount_quantity = 0;

				foreach( $cart_query as $cart_2 )
				{
					if( $cart_2['product_id'] == $cart['product_id'] )
					{
						$discount_quantity += $cart_2['quantity'];
					}
				}

				$product_discount_query = $db->query( 'SELECT price FROM ' . $this->table . '_product_discount WHERE product_id = ' . ( int )$cart['product_id'] . ' AND customer_group_id = ' . ( int )$this->config['config_customer_group_id'] . ' AND quantity <= ' . ( int )$discount_quantity . ' AND ( ( date_start = 0 OR date_start < ' . NV_CURRENTTIME . ' ) AND ( date_end = 0 OR date_end > ' . NV_CURRENTTIME . ' ) ) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1' )->fetch();

				if( $product_discount_query )
				{
					$price = $product_discount_query['price'];
				}

				// Product Specials
				$product_special_query = $db->query( 'SELECT price FROM ' . $this->table . '_product_special WHERE product_id = ' . ( int )$cart['product_id'] . ' AND customer_group_id = ' . ( int )$this->config['config_customer_group_id'] . ' AND ( ( date_start = 0 OR date_start < ' . NV_CURRENTTIME . ') AND (date_end = 0 OR date_end > ' . NV_CURRENTTIME . ' ) ) ORDER BY priority ASC, price ASC LIMIT 1' )->fetch();

				if( $product_special_query )
				{
					$price = $product_special_query['price'];
				}

				// Reward Points
				$product_reward_query = $db->query( 'SELECT points FROM ' . $this->table . '_product_reward WHERE product_id = ' . ( int )$cart['product_id'] . ' AND customer_group_id = ' . ( int )$this->config['config_customer_group_id'] )->fetch();

				if( $product_reward_query )
				{
					$reward = $product_reward_query['points'];
				}
				else
				{
					$reward = 0;
				}

				// Downloads
				$download_data = array();

				$download_query = $db->query( 'SELECT * FROM ' . $this->table . '_product_to_download p2d LEFT JOIN ' . $this->table . '_download d ON (p2d.download_id = d.download_id) LEFT JOIN ' . $this->table . '_download_description dd ON (d.download_id = dd.download_id) WHERE p2d.product_id = ' . ( int )$cart['product_id'] . ' AND dd.language_id = ' . ( int )$this->current_language_id );

				while( $download = $download_query->fetch() )
				{
					$download_data[] = array(
						'download_id' => $download['download_id'],
						'name' => $download['name'],
						'filename' => $download['filename'],
						'mask' => $download['mask'] );
				}
				$download_query->closeCursor();
				
				// Stock
				if( ! $product_query['quantity'] || ( $product_query['quantity'] < $cart['quantity'] ) )
				{
					$stock = false;
				}

				$recurring_query = $db->query( 'SELECT * FROM ' . $this->table . '_recurring r LEFT JOIN ' . $this->table . '_product_recurring pr ON (r.recurring_id = pr.recurring_id) LEFT JOIN ' . $this->table . '_recurring_description rd ON (r.recurring_id = rd.recurring_id) WHERE r.recurring_id = ' . ( int )$cart['recurring_id'] . ' AND pr.product_id = ' . ( int )$cart['product_id'] . ' AND rd.language_id = ' . ( int )$this->current_language_id . ' AND r.status = 1 AND pr.customer_group_id = ' . ( int )$this->config['config_customer_group_id'] )->fetch();

				if( $recurring_query )
				{
					$recurring = array(
						'recurring_id' => $cart['recurring_id'],
						'name' => $recurring_query['name'],
						'frequency' => $recurring_query['frequency'],
						'price' => $recurring_query['price'],
						'cycle' => $recurring_query['cycle'],
						'duration' => $recurring_query['duration'],
						'trial' => $recurring_query['trial_status'],
						'trial_frequency' => $recurring_query['trial_frequency'],
						'trial_price' => $recurring_query['trial_price'],
						'trial_cycle' => $recurring_query['trial_cycle'],
						'trial_duration' => $recurring_query['trial_duration'] );
				}
				else
				{
					$recurring = false;
				}

				$product_data[] = array(
					'cart_id' => $cart['cart_id'],
					'category_id' => $product_query['category_id'],
					'product_id' => $product_query['product_id'],
					'name' => $product_query['name'],
					'alias' => $product_query['alias'],
					'model' => $product_query['model'],
					'shipping' => $product_query['shipping'],
					'image' => $product_query['image'],
					'thumb' => $product_query['thumb'],
					'option' => $option_data,
					'download' => $download_data,
					'quantity' => $cart['quantity'],
					'minimum' => $product_query['minimum'],
					'subtract' => $product_query['subtract'],
					'stock' => $stock,
					'price' => ( $price + $option_price ),
					'total' => ( $price + $option_price ) * $cart['quantity'],
					'reward' => $reward * $cart['quantity'],
					'points' => ( $product_query['points'] ? ( $product_query['points'] + $option_points ) * $cart['quantity'] : 0 ),
					'tax_class_id' => $product_query['tax_class_id'],
					'weight' => ( $product_query['weight'] + $option_weight ) * $cart['quantity'],
					'weight_class_id' => $product_query['weight_class_id'],
					'length' => $product_query['length'],
					'width' => $product_query['width'],
					'height' => $product_query['height'],
					'length_class_id' => $product_query['length_class_id'],
					'recurring' => $recurring );
				
				
					
			}
			else
			{
				$this->remove( $cart['cart_id'] );
			}
			
		}
		
		unset( $cart_query, $product_query, $product_discount_query, $product_special_query, $recurring_query, $product_reward_query, $download_query );
		
		return $product_data;
	}
		 
	public function add( $product_id, $quantity = 1, $option = array(), $recurring_id = 0 )
	{
		global $db, $globalUserid;

		$_total = $db->query( 'SELECT COUNT(*) FROM ' . $this->table . '_cart WHERE api_id = ' . ( isset( $_SESSION[$this->mod_data . '_api_id'] ) ? ( int )$_SESSION[$this->mod_data . '_api_id'] : 0 ) . ' AND customer_id = ' . ( int )$globalUserid . ' AND session_id = ' . $db->quote( session_id() ) . ' AND product_id = ' . ( int )$product_id . ' AND recurring_id = ' . ( int )$recurring_id . ' AND option = ' . $db->quote( json_encode( $option ) ) )->fetchColumn();

		if( ! $_total )
		{
			$db->query( 'INSERT ' . $this->table . '_cart SET api_id = ' . ( isset( $_SESSION[$this->mod_data . '_api_id'] ) ? ( int )$_SESSION[$this->mod_data . '_api_id'] : 0 ) . ', customer_id = ' . ( int )$globalUserid . ', session_id = ' . $db->quote( session_id() ) . ', product_id = ' . ( int )$product_id . ', recurring_id = ' . ( int )$recurring_id . ', option = ' . $db->quote( json_encode( $option ) ) . ', quantity = ' . ( int )$quantity . ', date_added = ' . NV_CURRENTTIME );
		}
		else
		{
			$db->query( 'UPDATE ' . $this->table . '_cart SET quantity = (quantity + ' . ( int )$quantity . ') WHERE api_id = ' . ( isset( $_SESSION[$this->mod_data . '_api_id'] ) ? ( int )$_SESSION[$this->mod_data . '_api_id'] : 0 ) . ' AND customer_id = ' . ( int )$globalUserid . ' AND session_id = ' . $db->quote( session_id() ) . ' AND product_id = ' . ( int )$product_id . ' AND recurring_id = ' . ( int )$recurring_id . ' AND option = ' . $db->quote( json_encode( $option ) ) );
		}
	}

	public function update( $cart_id, $quantity )
	{
		global $db, $globalUserid;

		$db->query( 'UPDATE ' . $this->table . '_cart SET quantity = ' . ( int )$quantity . ' WHERE cart_id = ' . ( int )$cart_id . ' AND api_id = ' . ( isset( $_SESSION[$this->mod_data . '_api_id'] ) ? ( int )$_SESSION[$this->mod_data . '_api_id'] : 0 ) . ' AND customer_id = ' . ( int )$globalUserid . ' AND session_id = ' . $db->quote( session_id() ) );
	}

	public function remove( $cart_id )
	{
		global $db, $globalUserid;

		$db->query( 'DELETE FROM ' . $this->table . '_cart WHERE cart_id = ' . ( int )$cart_id . ' AND api_id = ' . ( isset( $_SESSION[$this->mod_data . '_api_id'] ) ? ( int )$_SESSION[$this->mod_data . '_api_id'] : 0 ) . ' AND customer_id = ' . ( int )$globalUserid . ' AND session_id = ' . $db->quote( session_id() ) );
	}

	public function clear()
	{
		global $db, $globalUserid;

		$db->query( 'DELETE FROM ' . $this->table . '_cart WHERE api_id = ' . ( isset( $_SESSION[$this->mod_data . '_api_id'] ) ? ( int )$_SESSION[$this->mod_data . '_api_id'] : 0 ) . ' AND customer_id = ' . ( int )$globalUserid . ' AND session_id = ' . $db->quote( session_id() ) );
	}

	public function getRecurringProducts()
	{
		$product_data = array();

		foreach( $this->getProducts() as $value )
		{
			if( $value['recurring'] )
			{
				$product_data[] = $value;
			}
		}

		return $product_data;
	}

	public function getWeight()
	{
		$weight = 0;

		foreach( $this->getProducts() as $product )
		{
			if( $product['shipping'] )
			{
				$weight += $this->weight->convert( $product['weight'], $product['weight_class_id'], $this->config['config_weight_class_id'] );
			}
		}

		return $weight;
	}

	public function getSubTotal()
	{
		$total = 0;

		foreach( $this->getProducts() as $product )
		{
			$total += $product['total'];
		}

		return $total;
	}

	public function getTaxes()
	{
		$tax_data = array();

		foreach( $this->getProducts() as $product )
		{
			if( $product['tax_class_id'] )
			{
				$tax_rates = $this->tax->getRates( $product['price'], $product['tax_class_id'] );

				foreach( $tax_rates as $tax_rate )
				{
					if( ! isset( $tax_data[$tax_rate['tax_rate_id']] ) )
					{
						$tax_data[$tax_rate['tax_rate_id']] = ( $tax_rate['amount'] * $product['quantity'] );
					}
					else
					{
						$tax_data[$tax_rate['tax_rate_id']] += ( $tax_rate['amount'] * $product['quantity'] );
					}
				}
			}
		}

		return $tax_data;
	}

	public function getTotal()
	{
		$total = 0;

		foreach( $this->getProducts() as $product )
		{
			$total += $this->tax->calculate( $product['price'], $product['tax_class_id'], $this->config['config_tax' ] ) * $product['quantity'];
		}

		return $total;
	}

	public function countProducts()
	{
		$product_total = 0;

		$products = $this->getProducts();

		foreach( $products as $product )
		{
			$product_total += $product['quantity'];
		}

		return $product_total;
	}

	public function hasProducts()
	{
		return count( $this->getProducts() );
	}

	public function hasRecurringProducts()
	{
		return count( $this->getRecurringProducts() );
	}

	public function hasStock()
	{
		foreach( $this->getProducts() as $product )
		{
			if( ! $product['stock'] )
			{
				return false;
			}
		}

		return true;
	}

	public function hasShipping()
	{
		foreach( $this->getProducts() as $product )
		{
			if( $product['shipping'] )
			{
				return true;
			}
		}

		return false;
	}

	public function hasDownload()
	{
		foreach( $this->getProducts() as $product )
		{
			if( $product['download'] )
			{
				return true;
			}
		}

		return false;
	}

	
}
