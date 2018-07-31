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

final class Tax extends General
{
	private $tax_rates = array();

	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );
	}

	public function unsetRates()
	{
		$this->tax_rates = array();
	}

	public function setShippingAddress( $country_id, $zone_id )
	{
		global $db;

		$result = $db->query( 'SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority FROM ' . $this->table . '_tax_rule tr1 LEFT JOIN ' . $this->table . '_tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id) INNER JOIN ' . $this->table . '_tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id) LEFT JOIN ' . $this->table . '_zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id) LEFT JOIN ' . $this->table . '_geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id) WHERE tr1.based = \'shipping\' AND tr2cg.customer_group_id = ' . intval( $this->config['config_customer_group_id'] ) . ' AND z2gz.country_id = ' . intval( $country_id ) . ' AND (z2gz.zone_id = 0 OR z2gz.zone_id = ' . intval( $zone_id ) . ') ORDER BY tr1.priority ASC' );
 
		while( $data = $result->fetch() )
		{
			$this->tax_rates[$data['tax_class_id']][$data['tax_rate_id']] = array(
				'tax_rate_id' => $data['tax_rate_id'],
				'name' => $data['name'],
				'rate' => $data['rate'],
				'type' => $data['type'],
				'priority' => $data['priority'] );
		}
		$result->closeCursor();
	}

	public function setPaymentAddress( $country_id, $zone_id )
	{
		global $db;
		$result = $db->query( 'SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority FROM ' . $this->table . '_tax_rule tr1 LEFT JOIN ' . $this->table . '_tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id) INNER JOIN ' . $this->table . '_tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id) LEFT JOIN ' . $this->table . '_zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id) LEFT JOIN ' . $this->table . '_geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id) WHERE tr1.based = \'payment\' AND tr2cg.customer_group_id = ' . intval( $this->config['config_customer_group_id'] ) . ' AND z2gz.country_id = ' . intval( $country_id ) . ' AND (z2gz.zone_id =0 OR z2gz.zone_id = ' . intval( $zone_id ) . ') ORDER BY tr1.priority ASC' );
		while( $data = $result->fetch() )
		{
			$this->tax_rates[$data['tax_class_id']][$data['tax_rate_id']] = array(
				'tax_rate_id' => $data['tax_rate_id'],
				'name' => $data['name'],
				'rate' => $data['rate'],
				'type' => $data['type'],
				'priority' => $data['priority'] );
		}
		$result->closeCursor();
	}

	public function setStoreAddress( $country_id, $zone_id )
	{
		global $db;

		$result = $db->query( 'SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority FROM ' . $this->table . '_tax_rule tr1 LEFT JOIN ' . $this->table . '_tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id) INNER JOIN ' . $this->table . '_tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id) LEFT JOIN ' . $this->table . '_zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id) LEFT JOIN ' . $this->table . '_geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id) WHERE tr1.based = \'store\' AND tr2cg.customer_group_id = ' . intval( $this->config['config_customer_group_id'] ) . ' AND z2gz.country_id = ' . intval( $country_id ) . ' AND (z2gz.zone_id = 0 OR z2gz.zone_id = ' . intval( $zone_id ) . ') ORDER BY tr1.priority ASC' );
		while( $data = $result->fetch() )
		{
			$this->tax_rates[$data['tax_class_id']][$data['tax_rate_id']] = array(
				'tax_rate_id' => $data['tax_rate_id'],
				'name' => $data['name'],
				'rate' => $data['rate'],
				'type' => $data['type'],
				'priority' => $data['priority'] );
		}
		$result->closeCursor();
	}

	public function calculate( $value, $tax_class_id, $calculate = true )
	{
		if( $tax_class_id && $calculate )
		{
			$amount = 0;

			$tax_rates = $this->getRates( $value, $tax_class_id );

			foreach( $tax_rates as $tax_rate )
			{
				if( $calculate != 'P' && $calculate != 'F' )
				{
					$amount += $tax_rate['amount'];
				}
				elseif( $tax_rate['type'] == $calculate )
				{
					$amount += $tax_rate['amount'];
				}
			}

			return $value + $amount;
		}
		else
		{
			return $value;
		}
	}

	public function getTax( $value, $tax_class_id )
	{
		$amount = 0;

		$tax_rates = $this->getRates( $value, $tax_class_id );

		foreach( $tax_rates as $tax_rate )
		{
			$amount += $tax_rate['amount'];
		}

		return $amount;
	}

	public function getRateName( $tax_rate_id )
	{
		global $db;
		$result = $db->query( 'SELECT name FROM ' . $this->table . '_tax_rate WHERE tax_rate_id = ' . intval( $tax_rate_id ) );

		if( $result->rowCount() )
		{
			return $result->fetchColumn();
		}
		else
		{
			return false;
		}

	}

	public function getRates( $value, $tax_class_id )
	{
		$tax_rate_data = array();

		if( isset( $this->tax_rates[$tax_class_id] ) )
		{
			foreach( $this->tax_rates[$tax_class_id] as $tax_rate )
			{
				if( isset( $tax_rate_data[$tax_rate['tax_rate_id']] ) )
				{
					$amount = $tax_rate_data[$tax_rate['tax_rate_id']]['amount'];
				}
				else
				{
					$amount = 0;
				}

				if( $tax_rate['type'] == 'F' )
				{
					$amount += $tax_rate['rate'];
				}
				elseif( $tax_rate['type'] == 'P' )
				{
					$amount += ( $value / 100 * $tax_rate['rate'] );
				}

				$tax_rate_data[$tax_rate['tax_rate_id']] = array(
					'tax_rate_id' => $tax_rate['tax_rate_id'],
					'name' => $tax_rate['name'],
					'rate' => $tax_rate['rate'],
					'type' => $tax_rate['type'],
					'amount' => $amount );
			}
		}

		return $tax_rate_data;
	}
}
