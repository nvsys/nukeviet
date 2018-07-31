<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class voucher_theme extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

		$this->config_ext = $this->getSetting( 'voucher', $this->store_id );
	}
	public function getVoucherTheme( $voucher_theme_id )
	{
		global $db, $ProductTax, $ProductContent;

		return $db->query( 'SELECT * FROM ' . $this->table . '_voucher_theme vt LEFT JOIN ' . $this->table . '_voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) WHERE vt.voucher_theme_id = ' . ( int )$voucher_theme_id . ' AND vtd.language_id = ' . ( int )$this->current_language_id )->fetch();

	}

	public function getVoucherThemes( $data = array() )
	{
		global $db, $ProductTax, $ProductContent;

		if( $data )
		{
			$sql = 'SELECT * FROM ' . $this->table . '_voucher_theme vt LEFT JOIN ' . $this->table . '_voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) WHERE vtd.language_id = ' . ( int )$this->current_language_id . ' ORDER BY vtd.name';

			if( isset( $data['order'] ) && ( $data['order'] == 'DESC' ) )
			{
				$sql .= ' DESC';
			}
			else
			{
				$sql .= ' ASC';
			}

			if( isset( $data['start'] ) || isset( $data['limit'] ) )
			{
				if( $data['start'] < 0 )
				{
					$data['start'] = 0;
				}

				if( $data['limit'] < 1 )
				{
					$data['limit'] = 20;
				}

				$sql .= ' LIMIT ' . ( int )$data['start'] . ',' . ( int )$data['limit'];
			}

			$query = $db->query( $sql )->fetch();

			return $query;
		}
		else
		{

			$sql = 'SELECT * FROM ' . $this->table . '_voucher_theme vt LEFT JOIN ' . $this->table . '_voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) WHERE vtd.language_id = ' . ( int )$this->current_language_id . ' ORDER BY vtd.name';

			return $this->getdbCache( $sql, 'voucher_theme.' . ( int )$this->current_language_id );

		}
	}
}
