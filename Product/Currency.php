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

class Currency extends General
{
	private $currencies = array();

	public function __construct( $productRegistry  )
	{
		global $nv_Request;

		parent::__construct( $productRegistry );

		$currency = $this->getdbCache( 'SELECT * FROM ' . $this->table . '_currency', 'currency', 'code' );

		foreach( $currency as $result )
		{
			$this->currencies[$result['code']] = array(
				'currency_id' => $result['currency_id'],
				'title' => $result['title'],
				'symbol_left' => $result['symbol_left'],
				'symbol_right' => $result['symbol_right'],
				'decimal_place' => $result['decimal_place'],
				'value' => $result['value'] );
		}
	}

	public function format( $number, $currency='', $value = '', $format = true )
	{
		
		$symbol_left = $this->currencies[$currency]['symbol_left'];
		$symbol_right = $this->currencies[$currency]['symbol_right'];
		$decimal_place = $this->currencies[$currency]['decimal_place'];

		if( ! $value )
		{
			$value = $this->currencies[$currency]['value'];
		}

		$amount = $value ? ( float )$number * $value : ( float )$number;

		$amount = round( $amount, ( int )$decimal_place );

		if( ! $format )
		{
			return $amount;
		}

		$string = '';

		if( $symbol_left )
		{
			$string .= $symbol_left;
		}

		$string .= number_format( $amount, ( int )$decimal_place, $this->mod_lang['currency_decimal_point'], $this->mod_lang['currency_thousand_point'] );
 
		if( $symbol_right )
		{
			$string .= $symbol_right;
		}

		return $string;
	}

	public function convert( $value, $from, $to )
	{
		if( isset( $this->currencies[$from] ) )
		{
			$from = $this->currencies[$from]['value'];
		}
		else
		{
			$from = 1;
		}

		if( isset( $this->currencies[$to] ) )
		{
			$to = $this->currencies[$to]['value'];
		}
		else
		{
			$to = 1;
		}

		return $value * ( $to / $from );
	}

	public function getId( $currency )
	{
		if( isset( $this->currencies[$currency] ) )
		{
			return $this->currencies[$currency]['currency_id'];
		}
		else
		{
			return 0;
		}
	}

	public function getSymbolLeft( $currency )
	{
		if( isset( $this->currencies[$currency] ) )
		{
			return $this->currencies[$currency]['symbol_left'];
		}
		else
		{
			return '';
		}
	}

	public function getSymbolRight( $currency )
	{
		if( isset( $this->currencies[$currency] ) )
		{
			return $this->currencies[$currency]['symbol_right'];
		}
		else
		{
			return '';
		}
	}

	public function getDecimalPlace( $currency )
	{
		if( isset( $this->currencies[$currency] ) )
		{
			return $this->currencies[$currency]['decimal_place'];
		}
		else
		{
			return 0;
		}
	}

	public function getValue( $currency )
	{
		if( isset( $this->currencies[$currency] ) )
		{
			return $this->currencies[$currency]['value'];
		}
		else
		{
			return 0;
		}
	}

	public function has( $currency )
	{
		return isset( $this->currencies[$currency] );
	}
	
	public function __destruct()
	{

		foreach( $this as $key => $value )
		{
			unset( $this->$key );
		}
	}

	public function clear()
	{
		$this->__destruct();
		parent::__destruct();

	}
}

if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );
