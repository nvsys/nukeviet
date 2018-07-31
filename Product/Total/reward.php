<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class reward extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

		$this->config_ext = $this->getSetting( 'reward', $this->store_id );
	}
	public function getTotal( $total )
	{
		global $db, $ProductCart, $ProductTax, $globalUserid, $productRegistry, $getProducts;

		if( isset( $_SESSION[$this->mod_data . '_reward'] ) )
		{

			$language = $this->getLangSite( 'reward', 'total' );

			$points = $db->query( 'SELECT SUM(points) AS total FROM ' . $this->table . '_customer_reward WHERE customer_id = ' . ( int )$order_info['customer_id'] )->fetchColumn();

			if( $_SESSION[$this->mod_data . '_reward'] <= $points )
			{
				$getProducts = $ProductCart->getProducts();

				$discount_total = 0;

				$points_total = 0;

				foreach( $getProducts as $product )
				{
					if( $product['points'] )
					{
						$points_total += $product['points'];
					}
				}

				$points = min( $points, $points_total );

				foreach( $getProducts as $product )
				{
					$discount = 0;

					if( $product['points'] )
					{
						$discount = $product['total'] * ( $_SESSION[$this->mod_data . '_reward'] / $points_total );

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

				$total['xtotals'][] = array(
					'code' => 'reward',
					'title' => sprintf( $language['text_reward'], $_SESSION[$this->mod_data . '_reward'] ),
					'value' => -$discount_total,
					'sort_order' => $this->config_ext['reward_sort_order'] );

				$total['total'] -= $discount_total;
			}
		}
	}

	public function confirm( $order_info, $order_total )
	{
		global $db, $ProductContent, $ProductTax, $globalUserid, $productRegistry;

		$language = $this->getLangSite( 'reward', 'total' );

		$points = 0;

		$start = strpos( $order_total['title'], '(' ) + 1;
		$end = strrpos( $order_total['title'], ')' );

		if( $start && $end )
		{
			$points = substr( $order_total['title'], $start, $end - $start );
		}

		$point_total = $db->query( 'SELECT SUM(points) AS total FROM ' . $this->table . '_customer_reward WHERE customer_id = ' . ( int )$order_info['customer_id'] )->fetchColumn();

		if( $point_total >= $points )
		{
			$db->query( 'INSERT INTO ' . $this->table . '_customer_reward SET customer_id = ' . ( int )$order_info['customer_id'] . ', order_id = ' . ( int )$order_info['order_id'] . ', description = ' . $db->quote( sprintf( $language['text_order_id'], ( int )$order_info['order_id'] ) ) . ', points = ' . ( float ) - $points . ', date_added = ' . NV_CURRENTTIME );
		}
		else
		{
			return $this->config['config_fraud_status_id'];
		}
	}

	public function unconfirm( $order_id )
	{
		global $db;

		$db->query( 'DELETE FROM ' . $this->table . '_customer_reward WHERE order_id = ' . ( int )$order_id . ' AND points < 0' );
	}
}
