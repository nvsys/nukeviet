<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class shipping extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

		$this->config_ext = $this->getSetting( 'shipping', $this->store_id );
	}
	public function getTotal( $total )
	{
		global $db, $ProductCart, $ProductTax, $globalUserid;
		
		if( $ProductCart->hasShipping() && isset( $_SESSION[$this->mod_data . '_shipping_method'] ) )
		{
			$total['xtotals'][] = array(
				'code' => 'shipping',
				'title' => $_SESSION[$this->mod_data . '_shipping_method']['title'],
				'value' => $_SESSION[$this->mod_data . '_shipping_method']['cost'],
				'sort_order' => $this->config_ext['shipping_sort_order'] );

			if( $_SESSION[$this->mod_data . '_shipping_method']['tax_class_id'] )
			{
				$tax_rates = $ProductTax->getRates( $_SESSION[$this->mod_data . '_shipping_method']['cost'], $_SESSION[$this->mod_data . '_shipping_method']['tax_class_id'] );

				foreach( $tax_rates as $tax_rate )
				{
					if( ! isset( $total['taxes'][$tax_rate['tax_rate_id']] ) )
					{
						$total['taxes'][$tax_rate['tax_rate_id']] = $tax_rate['amount'];
					}
					else
					{
						$total['taxes'][$tax_rate['tax_rate_id']] += $tax_rate['amount'];
					}
				}
			}

			$total['total'] += $_SESSION[$this->mod_data . '_shipping_method']['cost'];
		}
	}
}
