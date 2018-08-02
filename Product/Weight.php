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

final class Weight extends General
{
	private $weights = array();

	public function __construct( $productRegistry = array() )
	{
		global $db;

		parent::__construct( $productRegistry );

		$sql='SELECT * FROM ' . $this->table . '_weight_class wc LEFT JOIN ' . $this->table . '_weight_class_description wcd ON (wc.weight_class_id = wcd.weight_class_id) WHERE wcd.language_id = ' . ( int )$this->current_language_id;
		$this->weights = $this->getdbCache( $sql, 'weight', $key = 'weight_class_id' );
 
	}

	public function convert( $value, $from, $to )
	{
		if( $from == $to )
		{
			return $value;
		}

		if( isset( $this->weights[$from] ) )
		{
			$from = $this->weights[$from]['value'];
		}
		else
		{
			$from = 1;
		}

		if( isset( $this->weights[$to] ) )
		{
			$to = $this->weights[$to]['value'];
		}
		else
		{
			$to = 1;
		}

		return $value * ( $to / $from );
	}

	public function format( $value, $weight_class_id, $decimal_point = '.', $thousand_point = ',' )
	{
		if( isset( $this->weights[$weight_class_id] ) )
		{
			return number_format( $value, 2, $decimal_point, $thousand_point ) . $this->weights[$weight_class_id]['unit'];
		}
		else
		{
			return number_format( $value, 2, $decimal_point, $thousand_point );
		}
	}

	public function getUnit( $weight_class_id )
	{
		if( isset( $this->weights[$weight_class_id] ) )
		{
			return $this->weights[$weight_class_id]['unit'];
		}
		else
		{
			return '';
		}
	}
}

if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );
