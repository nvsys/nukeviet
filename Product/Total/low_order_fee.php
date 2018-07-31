<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class low_order_fee extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

		$this->config_ext = $this->getSetting( 'low_order_fee', $this->store_id );
	}

	public function getTotal( $total )
	{

		global $db, $ProductCart, $ProductTax, $globalUserid, $productRegistry;

		if( $ProductCart->getSubTotal() && ( $ProductCart->getSubTotal() < $this->config_ext['low_order_fee_total'] ) )
		{
			$language = $this->getLangSite( 'coupon', 'total' );

			$total['xtotals'][] = array(
				'code' => 'low_order_fee',
				'title' => $language['text_low_order_fee'],
				'value' => $this->config_ext['low_order_fee_fee'],
				'sort_order' => $this->config_ext['low_order_fee_sort_order'] );

			if( $this->config_ext['low_order_fee_tax_class_id'] )
			{
				$tax_rates = $ProductTax->getRates( $this->config_ext['low_order_fee_fee'], $this->config_ext['low_order_fee_tax_class_id'] );

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

			$total['total'] += $this->config_ext['low_order_fee_fee'];
		}
	}
}
