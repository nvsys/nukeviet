<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class klarna_fee extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );
		
		$this->config_ext = $this->getSetting( 'klarna_fee', $this->store_id );
	}

	public function getTotal( $totals )
	{
		global $db, $ProductContent, $ProductTax, $globalUserid, $productRegistry;
		
		$language = $this->getLangSite( 'klarna_fee', 'total' );
		
		extract( $totals );
 
		$status = true;

		$klarna_fee = $this->config_ext['klarna_fee'];

		if( isset( $_SESSION[$this->mod_data . '_payment_address_id'] ) )
		{
			$accountAddress = new NukeViet\Product\Account\Address( $productRegistry );

			$address = $accountAddress->getAddress( $_SESSION[$this->mod_data . '_payment_address_id'] );
		}
		elseif( isset( $_SESSION[$this->mod_data . '_guest']['payment'] ) )
		{
			$address = $_SESSION[$this->mod_data . '_guest']['payment'];
		}

		if( ! isset( $address ) )
		{
			$status = false;
		}
		elseif( ! isset( $_SESSION[$this->mod_data . '_payment_method']['code'] ) || $_SESSION[$this->mod_data . '_payment_method']['code'] != 'klarna_invoice' )
		{
			$status = false;
		}
		elseif( ! isset( $klarna_fee[$address['iso_code_3']] ) )
		{
			$status = false;
		}
		elseif( ! $klarna_fee[$address['iso_code_3']]['status'] )
		{
			$status = false;
		}
		elseif( $ProductTax->getSubTotal() >= $klarna_fee[$address['iso_code_3']]['total'] )
		{
			$status = false;
		}

		if( $status )
		{
			$total['xtotals'][] = array(
				'code' => 'klarna_fee',
				'title' => $language['text_klarna_fee'],
				'value' => $klarna_fee[$address['iso_code_3']]['fee'],
				'sort_order' => $klarna_fee[$address['iso_code_3']]['sort_order'] );

			$tax_rates = $ProductTax->getRates( $klarna_fee[$address['iso_code_3']]['fee'], $klarna_fee[$address['iso_code_3']]['tax_class_id'] );

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

			$total['total'] += $klarna_fee[$address['iso_code_3']]['fee'];
		}
	}
}
