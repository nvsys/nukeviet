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
 
class sub_total extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );
		
		$this->config_ext = $this->getSetting( 'sub_total', $this->store_id );
		
	}
	
	public function getTotal($total)
	{
		global $ProductCart;
		
		$language = $this->getLangSite( 'sub_total', 'total' );
		
		$sub_total = $ProductCart->getSubTotal();

		if( isset( $_SESSION[$this->mod_data . '_vouchers'] ) && $_SESSION[$this->mod_data . '_vouchers'] )
		{

			foreach( $_SESSION[$this->mod_data . '_vouchers'] as $voucher )
			{
				$sub_total += $voucher['amount'];
			}
		}
		$total['xtotals'][] = array(
			'code'       => 'sub_total',
			'title'      => $language['text_sub_total'],
			'value'      => $sub_total,
			'sort_order' => $this->config_ext['sub_total_sort_order']
		);

		$total['total'] += $sub_total;
	}
	 
}
 