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

final class Voucher extends  General
{
	private $userid;
	
	private $language;
	
	private $currency;
	
	public function __construct( $productRegistry = array() )
	{
		global $user_info, $admin_info;

		parent::__construct( $productRegistry );

		if( isset( $admin_info['userid'] ) )
		{
			$this->userid = $admin_info['userid'];
		}
		elseif( isset( $user_info['userid'] ) )
		{
			$this->userid = $user_info['userid'];
		}
		else
		{
			$this->userid = 0;
		}

		$this->language = $this->getLangSite( 'voucher', 'mail' );

		$this->currency = new Product\Currency( $productRegistry );

	}
	
	public function addVoucher( $order_id, $data )
	{
		global $db;

		$db->query( 'INSERT INTO ' . $this->table . '_voucher SET order_id = ' . ( int )$order_id . ', code = ' . $db->quote( $data['code'] ) . ', from_name = ' . $db->quote( $data['from_name'] ) . ', from_email = ' . $db->quote( $data['from_email'] ) . ', to_name = ' . $db->quote( $data['to_name'] ) . ', to_email = ' . $db->quote( $data['to_email'] ) . ', voucher_theme_id = ' . ( int )$data['voucher_theme_id'] . ', message = ' . $db->quote( $data['message'] ) . ', amount = ' . ( float )$data['amount'] . ', status = 1, date_added = ' . $this->currenttime );

		return $db->lastInsertId();
	}

	public function disableVoucher( $order_id )
	{
		global $db;

		$db->query( 'UPDATE ' . $this->table . '_voucher SET status = 0 WHERE order_id = ' . ( int )$order_id );
	}

	public function getVoucher( $code )
	{
		global $db;

		$status = true;

		$voucher_query = $db->query( 'SELECT *, vtd.name AS theme FROM ' . $this->table . '_voucher v LEFT JOIN ' . $this->table . '_voucher_theme vt ON (v.voucher_theme_id = vt.voucher_theme_id) LEFT JOIN ' . $this->table . '_voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) WHERE v.code = ' . $db->quote( $code ) . ' AND vtd.language_id = ' . ( int )$this->current_language_id . ' AND v.status = 1' )->fetch();

		if( $voucher_query )
		{

			if( $voucher_query['order_id'] )
			{
				$order_query = $db->query( 'SELECT * FROM ' . $this->table . '_order WHERE order_id = ' . ( int )$voucher_query['order_id'] . ' AND order_status_id = ' . ( int )$this->config->get( 'config_complete_status_id' ) )->fetch();

				if( empty( $order_query ) )
				{
					$status = false;
				}

				$order_voucher_query = $db->query( 'SELECT * FROM ' . $this->table . '_order_voucher WHERE order_id = ' . ( int )$voucher_query['order_id'] . ' AND voucher_id = ' . ( int )$voucher_query['voucher_id'] )->fetch();

				if( empty( $order_voucher_query ) )
				{
					$status = false;
				}
			}

			$voucher_history_query = $db->query( 'SELECT SUM(amount) total FROM ' . $this->table . '_voucher_history vh WHERE vh.voucher_id = ' . ( int )$voucher_query['voucher_id'] . ' GROUP BY vh.voucher_id' )->fetch();

			if( ! empty( $voucher_history_query ) )
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

	public function confirm( $order_id )
	{
		global $db, $global_config;

		$order_info = getOrder( $order_id );

		if( $order_info )
		{

			$voucher_query = $db->query( 'SELECT *, vtd.name theme FROM ' . $this->table . '_voucher v LEFT JOIN ' . $this->table . '_voucher_theme vt ON (v.voucher_theme_id = vt.voucher_theme_id) LEFT JOIN ' . $this->table . '_voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) AND vtd.language_id = ' . ( int )$order_info['language_id'] . ' WHERE v.order_id = ' . ( int )$order_id );

			while( $voucher = $voucher_query->fetch() )
			{

				$data = array();

				$data['title'] = sprintf( $this->language['text_subject'], $voucher['from_name'] );

				$data['text_greeting'] = sprintf( $this->language['text_greeting'], $this->currency->format( $voucher['amount'], $order_info['currency_code'], $order_info['currency_value'] ) );
				$data['text_from'] = sprintf( $this->language['text_from'], $voucher['from_name'] );
				$data['text_message'] = $this->language['text_message'];
				$data['text_redeem'] = sprintf( $this->language['text_redeem'], $voucher['code'] );
				$data['text_footer'] = $this->language['text_footer'];

				if( is_file( $voucher['image'] ) )
				{
					$data['image'] = $this->config['config_url'] . '/' . $voucher['image'];
				}
				else
				{
					$data['image'] = '';
				}

				$data['store_name'] = $order_info['store_name'];
				$data['store_url'] = $order_info['store_url'];
				$data['message'] = nv_nl2br( $voucher['message'] );

				$xtpl = new XTemplate( 'voucher.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file . '/mail' );
				$xtpl->assign( 'NV_BASE_SITEURL', NV_BASE_SITEURL );
				$xtpl->assign( 'TEMPLATE', $global_config['module_theme'] );
				$xtpl->assign( 'DATA', $data );
				if( $message )
				{
					$xtpl->parse( 'main.message' );
				}

				$xtpl->parse( 'main' );
				$message = $xtpl->text( 'main' );

				nv_sendmail( array( $voucher['from_name'], $order_info['store_name'] ), $voucher['to_email'], sprintf( $this->language['text_subject'], $voucher['from_name'] ), $message );

			}
		}
	}
}

if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );