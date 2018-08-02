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

class PaymentOfflineCc extends  General
{

	public function __construct( $productRegistry )
	{

		parent::__construct( $productRegistry );

		$this->config = $this->configs( 'offline_cc' );
	}

	public function getMethod( $address, $total )
	{
		global $db;

		$query = $db->query( 'SELECT * FROM ' . $this->table . '_zone_to_geo_zone WHERE geo_zone_id = ' . ( int )$this->config['offline_cc_geo_zone_id'] . ' AND country_id = ' . ( int )$address['country_id'] . ' AND (zone_id = ' . ( int )$address['zone_id'] . ' OR zone_id = 0)' );

		if( $this->config['offline_cc_total'] > $total )
		{
			$status = false;
		}
		elseif( ! $this->config['offline_cc_geo_zone_id'] )
		{
			$status = true;
		}
		elseif( $query->rowCount() )
		{
			$status = true;
		}
		else
		{
			$status = false;
		}

		$method_data = array();

		if( $status )
		{
			$method_data = array(
				'code' => 'offline_cc',
				'title' => $this->language['offline_cc_title'],
				'sort_order' => $this->config['offline_cc_sort_order'] );
		}

		return $method_data;
	}

	public function cc( $order_id, $card, $name, $payment_type )
	{
		global $db;

		$db->query( 'UPDATE ' . $this->table . '_order SET payment_cc = ' . $db->quote( $card ) . ', payment_card_type = ' . $db->quote( $payment_type ) . ', payment_name = ' . $db->quote( $name ) . ', date_modified = ' . $this->currenttime . ' WHERE order_id = ' . ( int )$order_id );
	}

	public function encrypt( $string )
	{

		$key = $this->config['offline_cc_encryption'];

		$string = ' ' . $string . ' '; // note the spaces

		$encrypted = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $key ), $string, MCRYPT_MODE_CBC, md5( md5( $key ) ) ) );

		return $encrypted;
	}

	public function CCVal( $Num, $Name = 'n/a' )
	{
		/************************************************************************
		*
		* CCVal - Credit Card Validation function.
		*
		* Copyright (c) 1999 Holotech Enterprises. All rights reserved.
		* You may freely modify and use this function for your own purposes. You
		* may freely distribute it, without modification and with this notice
		* and entire header intact.
		*
		* This function accepts a credit card number and, optionally, a code for 
		* a credit card name. If a Name code is specified, the number is checked
		* against card-specific criteria, then validated with the Luhn Mod 10 
		* formula. Otherwise it is only checked against the formula. Valid name
		* codes are:
		*
		*    mcd - Master Card
		*    vis - Visa
		*    amx - American Express
		*    dsc - Discover
		*    dnc - Diners Club
		*    jcb - JCB
		*
		* A description of the criteria used in this function can be found at
		* http://www.beachnet.com/~hstiles/cardtype.html. If you have any 
		* questions or comments, please direct them to ccval@holotech.net
		*
		*                                          Alan Little
		*                                          Holotech Enterprises
		*                                          http://www.holotech.net/
		*                                          September 1999
		*
		************************************************************************/

		//  Innocent until proven guilty
		$GoodCard = true;

		//  Get rid of any non-digits
		$Num = preg_replace( "/[^0-9]+/", "", $Num );

		if( ! strlen( $Num ) >= 16 )
		{
			$GoodCard = false;
		}

		//  Perform card-specific checks, if applicable
		switch( $Name )
		{

			case "mcd":
				$GoodCard = ereg( "^5[1-5].{14}$", $Num );
				break;

			case "vis":
				$GoodCard = ereg( "^4.{15}$|^4.{12}$", $Num );
				break;

			case "amx":
				$GoodCard = ereg( "^3[47].{13}$", $Num );
				break;

			case "dsc":
				$GoodCard = ereg( "^6011.{12}$", $Num );
				break;

			case "dnc":
				$GoodCard = ereg( "^30[0-5].{11}$|^3[68].{12}$", $Num );
				break;

			case "jcb":
				$GoodCard = ereg( "^3.{15}$|^2131|1800.{11}$", $Num );
				break;
		}

		//  The Luhn formula works right to left, so reverse the number.
		$Num = strrev( $Num );

		$Total = 0;

		for( $x = 0; $x < strlen( $Num ); $x++ )
		{
			$digit = substr( $Num, $x, 1 );

			//    If it's an odd digit, double it
			if( $x / 2 != floor( $x / 2 ) )
			{
				$digit *= 2;

				//    If the result is two digits, add them
				if( strlen( $digit ) == 2 ) $digit = substr( $digit, 0, 1 ) + substr( $digit, 1, 1 );
			}

			//    Add the current digit, doubled and added if applicable, to the Total
			$Total += $digit;
		}

		//  If it passed (or bypassed) the card-specific check and the Total is
		//  evenly divisible by 10, it's cool!
		if( $GoodCard && $Total % 10 == 0 )
		{
			return true;
		}
		else
		{
			return false;
		}

	}

}

if( ! defined( 'NV_IS_MOD_PRODUCT' ) ) die( 'Stop!!!' );
