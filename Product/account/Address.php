<?php

namespace NukeViet\Product\Account;

use NukeViet\Product\General;

class Address extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

	}
	public function addAddress( $data )
	{
		global $db, $ProductContent, $globalUserid;

		$db->query( 'INSERT INTO ' . $this->table . '_address SET customer_id = ' . ( int )$globalUserid . ', firstname = ' . $db->quote( $data['firstname'] ) . ', lastname = ' . $db->quote( $data['lastname'] ) . ', company = ' . $db->quote( $data['company'] ) . ', address_1 = ' . $db->quote( $data['address_1'] ) . ', address_2 = ' . $db->quote( $data['address_2'] ) . ', postcode = ' . $db->quote( $data['postcode'] ) . ', city = ' . $db->quote( $data['city'] ) . ', zone_id = ' . ( int )$data['zone_id'] . ', country_id = ' . ( int )$data['country_id'] . ', custom_field = ' . $db->quote( isset( $data['custom_field'] ) ? json_encode( $data['custom_field'] ) : '' ) );

		$address_id = $db->lastInsertId();

		if( ! empty( $data['default'] ) )
		{
			$db->query( 'UPDATE ' . $this->table . '_customer SET address_id = ' . ( int )$address_id . ' WHERE customer_id = ' . ( int )$globalUserid . '' );
		}

		return $address_id;
	}

	public function editAddress( $address_id, $data )
	{
		global $db, $ProductContent, $globalUserid;

		$db->query( 'UPDATE ' . $this->table . '_address SET firstname = ' . $db->quote( $data['firstname'] ) . ', lastname = ' . $db->quote( $data['lastname'] ) . ', company = ' . $db->quote( $data['company'] ) . ', address_1 = ' . $db->quote( $data['address_1'] ) . ', address_2 = ' . $db->quote( $data['address_2'] ) . ', postcode = ' . $db->quote( $data['postcode'] ) . ', city = ' . $db->quote( $data['city'] ) . ', zone_id = ' . ( int )$data['zone_id'] . ', country_id = ' . ( int )$data['country_id'] . ', custom_field = ' . $db->quote( isset( $data['custom_field'] ) ? json_encode( $data['custom_field'] ) : '' ) . ' WHERE address_id  = ' . ( int )$address_id . ' AND customer_id = ' . ( int )$globalUserid . '' );

		if( ! empty( $data['default'] ) )
		{
			$db->query( 'UPDATE ' . $this->table . '_customer SET address_id = ' . ( int )$address_id . ' WHERE customer_id = ' . ( int )$globalUserid . '' );
		}
	}

	public function deleteAddress( $address_id )
	{
		global $db, $ProductContent, $globalUserid;

		$db->query( 'DELETE FROM ' . $this->table . '_address WHERE address_id = ' . ( int )$address_id . ' AND customer_id = ' . ( int )$globalUserid . '' );
	}

	public function getAddress( $address_id )
	{
		global $db, $ProductContent, $globalUserid;

		$address_query = $db->query( 'SELECT DISTINCT * FROM ' . $this->table . '_address WHERE address_id = ' . ( int )$address_id . ' AND customer_id = ' . ( int )$globalUserid . '' )->fetch();

		if( $address_query )
		{
			$country_query = $db->query( 'SELECT * FROM ' . $this->table . '_country WHERE country_id = ' . ( int )$address_query['country_id'] . '' );

			if( $country_query )
			{
				$country = $country_query['name'];
				$iso_code_2 = $country_query['iso_code_2'];
				$iso_code_3 = $country_query['iso_code_3'];
				$address_format = $country_query['address_format'];
			}
			else
			{
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}

			$zone_query = $db->query( 'SELECT * FROM ' . $this->table . '_zone WHERE zone_id = ' . ( int )$address_query['zone_id'] . '' )->fetch();

			if( $zone_query )
			{
				$zone = $zone_query['name'];
				$zone_code = $zone_query['code'];
			}
			else
			{
				$zone = '';
				$zone_code = '';
			}

			$address_data = array(
				'address_id' => $address_query['address_id'],
				'firstname' => $address_query['firstname'],
				'lastname' => $address_query['lastname'],
				'company' => $address_query['company'],
				'address_1' => $address_query['address_1'],
				'address_2' => $address_query['address_2'],
				'postcode' => $address_query['postcode'],
				'city' => $address_query['city'],
				'zone_id' => $address_query['zone_id'],
				'zone' => $zone,
				'zone_code' => $zone_code,
				'country_id' => $address_query['country_id'],
				'country' => $country,
				'iso_code_2' => $iso_code_2,
				'iso_code_3' => $iso_code_3,
				'address_format' => $address_format,
				'custom_field' => json_decode( $address_query['custom_field'], true ) );

			return $address_data;
		}
		else
		{
			return false;
		}
	}

	public function getAddresses()
	{
		global $db, $ProductContent, $globalUserid;

		$address_data = array();

		$query = $db->query( 'SELECT * FROM ' . $this->table . '_address WHERE customer_id = ' . ( int )$globalUserid . '' );

		foreach( $querys as $result )
		{
			$country_query = $db->query( 'SELECT * FROM ' . $this->table . '_country WHERE country_id = ' . ( int )$result['country_id'] . '' )->fetch();

			if( $country_query )
			{
				$country = $country_query['name'];
				$iso_code_2 = $country_query['iso_code_2'];
				$iso_code_3 = $country_query['iso_code_3'];
				$address_format = $country_query['address_format'];
			}
			else
			{
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}

			$zone_query = $db->query( 'SELECT * FROM ' . $this->table . '_zone WHERE zone_id = ' . ( int )$result['zone_id'] . '' )->fetch();

			if( $zone_query )
			{
				$zone = $zone_query['name'];
				$zone_code = $zone_query['code'];
			}
			else
			{
				$zone = '';
				$zone_code = '';
			}

			$address_data[$result['address_id']] = array(
				'address_id' => $result['address_id'],
				'firstname' => $result['firstname'],
				'lastname' => $result['lastname'],
				'company' => $result['company'],
				'address_1' => $result['address_1'],
				'address_2' => $result['address_2'],
				'postcode' => $result['postcode'],
				'city' => $result['city'],
				'zone_id' => $result['zone_id'],
				'zone' => $zone,
				'zone_code' => $zone_code,
				'country_id' => $result['country_id'],
				'country' => $country,
				'iso_code_2' => $iso_code_2,
				'iso_code_3' => $iso_code_3,
				'address_format' => $address_format,
				'custom_field' => json_decode( $result['custom_field'], true ) );
		}

		return $address_data;
	}

	public function getTotalAddresses()
	{
		global $db, $ProductContent, $globalUserid;

		return $db->query( 'SELECT COUNT(*) AS total FROM ' . $this->table . '_address WHERE customer_id = ' . ( int )$globalUserid . '' )->fetchClumn();

	}
}
