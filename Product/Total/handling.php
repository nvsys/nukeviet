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
namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class handling extends General
{
	public function __construct( $productRegistry )
	{
 
		parent::__construct( $productRegistry );
		
		$this->config_ext = $this->getSetting( 'handling', $this->store_id );
		
	}
	
	public function getTotal( $total )
	{
		global $db, $ProductContent, $ProductTax, $globalUserid;
		
		$language = $this->getLangSite( 'handling', 'total' );
		
		if( ( $ProductTax->getSubTotal() > $this->config->get( 'handling_total' ) ) && ( $ProductTax->getSubTotal() > 0 ) )
		{
 
			$total['xtotals'][] = array(
				'code' => 'handling',
				'title' => $language['text_handling'],
				'value' => $this->config_ext['handling_fee'],
				'sort_order' => $this->config_ext['handling_sort_order'] );

			if( $this->config_ext['handling_tax_class_id'] )
			{
				$tax_rates = $ProductTax->getRates( $this->config_ext['handling_fee'], $this->config_ext['handling_tax_class_id'] );

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

			$total['total'] += $this->config_ext['handling_fee'];
		}
	}
}
