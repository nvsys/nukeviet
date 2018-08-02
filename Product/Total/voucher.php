<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class voucher extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

		$this->config_ext = $this->getSetting( 'voucher', $this->store_id );
	}
	
	public function addVoucher( $order_id, $data )
	{
		global $db, $ProductTax, $ProductContent;
		
		$db->query( 'INSERT INTO ' . $this->table . '_voucher SET order_id = ' . ( int )$order_id . ', code = ' . $db->quote( $data['code'] ) . ', from_name = ' . $db->quote( $data['from_name'] ) . ', from_email = ' . $db->quote( $data['from_email'] ) . ', to_name = ' . $db->quote( $data['to_name'] ) . ', to_email = ' . $db->quote( $data['to_email'] ) . ', voucher_theme_id = ' . ( int )$data['voucher_theme_id'] . ', message = ' . $db->quote( $data['message'] ) . ', amount = ' . ( float )$data['amount'] . ', status = 1, date_added = ' . NV_CURRENTTIME );

		return $db->lastInsertId();
	}

	public function disableVoucher( $order_id )
	{
		global $db;
		
		$db->query( 'UPDATE ' . $this->table . '_voucher SET status = 0 WHERE order_id = ' . ( int )$order_id  );
	}

	public function getVoucher( $code )
	{
		global $db, $ProductTax, $ProductContent;
		
		$language = $this->getLangSite( 'voucher', 'total' );
		
		$status = true;

		$voucher_query = $db->query( 'SELECT *, vtd.name AS theme FROM ' . $this->table . '_voucher v LEFT JOIN ' . $this->table . '_voucher_theme vt ON (v.voucher_theme_id = vt.voucher_theme_id) LEFT JOIN ' . $this->table . '_voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) WHERE v.code = ' . $db->quote( $code ) . ' AND vtd.language_id = ' . ( int )$this->current_language_id . ' AND v.status = 1' )->fetch();

		if( $voucher_query )
		{
			if( $voucher_query['order_id'] )
			{
				$implode = array();

				foreach( $this->config['config_complete_status'] as $order_status_id )
				{
					$implode[] = '' . ( int )$order_status_id . '';
				}

				$order_query = $db->query( 'SELECT order_id FROM ' . $this->table . '_order WHERE order_id = ' . ( int )$voucher_query['order_id'] . ' AND order_status_id IN(' . implode( ',', $implode ) . ')' );

				if( ! $order_query )
				{
					$status = false;
				}

				$order_voucher_query = $db->query( 'SELECT order_voucher_id FROM ' . $this->table . '_order_voucher WHERE order_id = ' . ( int )$voucher_query['order_id'] . ' AND voucher_id = ' . ( int )$voucher_query['voucher_id'] )->fetchColumn();

				if( ! $order_voucher_query )
				{
					$status = false;
				}
			}

			$voucher_history_query = $db->query( 'SELECT SUM(amount) AS total FROM ' . $this->table . '_voucher_history vh WHERE vh.voucher_id = ' . ( int )$voucher_query['voucher_id'] . ' GROUP BY vh.voucher_id' )->fetch();

			if( $voucher_history_query )
			{
				$amount = $voucher_query['amount'] + $voucher_history_query['total'];
			}
			else
			{
				$amount = $voucher_query['amount'];
			}

			if( $amount <= 0 )
			{
				$status = false;
			}
		}
		else
		{
			$status = false;
		}

		if( $status )
		{
			return array(
				'voucher_id' => $voucher_query['voucher_id'],
				'code' => $voucher_query['code'],
				'from_name' => $voucher_query['from_name'],
				'from_email' => $voucher_query['from_email'],
				'to_name' => $voucher_query['to_name'],
				'to_email' => $voucher_query['to_email'],
				'voucher_theme_id' => $voucher_query['voucher_theme_id'],
				'theme' => $voucher_query['theme'],
				'message' => $voucher_query['message'],
				'image' => $voucher_query['image'],
				'amount' => $amount,
				'status' => $voucher_query['status'],
				'date_added' => $voucher_query['date_added'] );
		}
	}

	public function getTotal( $total )
	{
		global $db, $ProductTax, $ProductContent;
		
		if( isset( $_SESSION[$this->mod_data . '_voucher'] ) )
		{
			$language = $this->getLangSite( 'voucher', 'total' );

			$voucher_info = $this->getVoucher( $_SESSION[$this->mod_data . '_voucher'] );

			if( $voucher_info )
			{
				$amount = min( $voucher_info['amount'], $total['total'] );

				if( $amount > 0 )
				{
					$total['xtotals'][] = array(
						'code' => 'voucher',
						'title' => sprintf( $language['text_voucher'], $_SESSION[$this->mod_data . '_voucher'] ),
						'value' => -$amount,
						'sort_order' => $this->config_ext['voucher_sort_order'] );

					$total['total'] -= $amount;
				}
				else
				{
					unset( $_SESSION[$this->mod_data . '_voucher'] );
				}
			}
			else
			{
				unset( $_SESSION[$this->mod_data . '_voucher'] );
			}
		}
	}

	public function confirm( $order_info, $order_total )
	{
		global $db, $ProductTax, $ProductContent;
		
		$code = '';

		$start = strpos( $order_total['title'], '(' ) + 1;
		$end = strrpos( $order_total['title'], ')' );

		if( $start && $end )
		{
			$code = substr( $order_total['title'], $start, $end - $start );
		}

		if( $code )
		{
			$voucher_info = $this->getVoucher( $code );

			if( $voucher_info )
			{
				$db->query( 'INSERT INTO ' . $this->table . '_voucher_history SET voucher_id = ' . ( int )$voucher_info['voucher_id'] . ', order_id = ' . ( int )$order_info['order_id'] . ', amount = ' . ( float )$order_total['value'] . ', date_added = ' . NV_CURRENTTIME );
			}
			else
			{
				return $this->config['config_fraud_status_id'];
			}
		}
	}

	public function unconfirm( $order_id )
	{
		global $db, $ProductTax, $ProductContent;
		
		$db->query( 'DELETE FROM ' . $this->table . '_voucher_history WHERE order_id = ' . ( int )$order_id  );
	}
}
