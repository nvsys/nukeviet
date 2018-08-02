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

final class Length extends  General
{
	private $lengths = array();

	public function __construct( $productRegistry = array() )
	{
		global $db;
		parent::__construct( $productRegistry );

		$result = $db->query( 'SELECT * FROM ' . $this->table . '_length_class mc LEFT JOIN ' . $this->table . '_length_class_description mcd ON (mc.length_class_id = mcd.length_class_id) WHERE mcd.language_id = ' . ( int )$this->current_language_id )->fetch();

		while( $data = $result->fetch() )
		{
			$this->lengths[$data['length_class_id']] = array(
				'length_class_id' => $data['length_class_id'],
				'title' => $data['title'],
				'unit' => $data['unit'],
				'value' => $data['value'] );
		}
		$result->closeCursor();
	}

	public function convert( $value, $from, $to )
	{
		if( $from == $to )
		{
			return $value;
		}

		if( isset( $this->lengths[$from] ) )
		{
			$from = $this->lengths[$from]['value'];
		}
		else
		{
			$from = 0;
		}

		if( isset( $this->lengths[$to] ) )
		{
			$to = $this->lengths[$to]['value'];
		}
		else
		{
			$to = 0;
		}

		return $value * ( $to / $from );
	}

	public function format( $value, $length_class_id, $decimal_point = '.', $thousand_point = ',' )
	{
		if( isset( $this->lengths[$length_class_id] ) )
		{
			return number_format( $value, 2, $decimal_point, $thousand_point ) . $this->lengths[$length_class_id]['unit'];
		}
		else
		{
			return number_format( $value, 2, $decimal_point, $thousand_point );
		}
	}

	public function getUnit( $length_class_id )
	{
		if( isset( $this->lengths[$length_class_id] ) )
		{
			return $this->lengths[$length_class_id]['unit'];
		}
		else
		{
			return '';
		}
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
