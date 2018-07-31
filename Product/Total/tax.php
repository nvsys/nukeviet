<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class tax extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

		$this->config_ext = $this->getSetting( 'tax', $this->store_id );
	}
	public function getTotal( $total )
	{
		global $ProductTax;

		foreach( $total['taxes'] as $key => $value )
		{
			if( $value > 0 )
			{
				$total['xtotals'][] = array(
					'code' => 'tax',
					'title' => $ProductTax->getRateName( $key ),
					'value' => $value,
					'sort_order' => $this->config_ext['tax_sort_order'] );

				$total['total'] += $value;
			}
		}
	}
}
