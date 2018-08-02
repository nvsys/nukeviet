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

class credit extends General
{
	
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );
		
		$this->config_ext = $this->getSetting( 'credit', $this->store_id );
	}
	
	public function getTotal( $total )
	{
		global $db, $ProductContent, $ProductTax, $globalUserid;
		
		$language = $this->getLangSite( 'credit', 'total' );
		
		$balance = $query = $db->query( 'SELECT SUM(amount) total FROM ' . $this->table . '_customer_transaction WHERE userid = ' . ( int )$globalUserid )->fetchColumn();
 
		if( ( float )$balance )
		{
			$credit = min( $balance, $total );

			if( $credit > 0 )
			{
				$total['xtotals'][] = array(
					'code' => 'credit',
					'title' => $language['text_credit'],
					'value' => -$credit,
					'sort_order' => $this->config_ext['credit_sort_order'] );

				$total['total'] -= $credit;
			}
		}
	}

	public function confirm( $order_info, $order_total )
	{
		global $db, $ProductContent, $ProductTax, $globalUserid;
		
		$language = $this->getLangSite( 'credit', 'total' );

		if( $order_info['customer_id'] )
		{
			$db->query( 'INSERT INTO ' . $this->table . '_customer_transaction SET customer_id = ' . ( int )$order_info['customer_id'] . ', order_id = ' . ( int )$order_info['order_id'] . ', description = ' . $db->quote( sprintf( $language['text_order_id'], ( int )$order_info['order_id'] ) ) . ', amount = ' . ( float )$order_total['value'] . ', date_added = ' . NV_CURRENTTIME );
		}
	}

	public function unconfirm( $order_id )
	{
		global $db, $globalUserid;
		
		$db->query( 'DELETE FROM ' . $this->table . '_customer_transaction WHERE order_id = ' . ( int )$order_id );
	}
}
 