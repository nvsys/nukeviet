<?php

namespace NukeViet\Product\Total;

use NukeViet\Product\General;

class totals extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

		$this->config_ext = $this->getSetting( 'totals', $this->store_id );
	}
	public function getTotal( $total )
	{
		$language = $this->getLangSite( 'totals', 'total' );
		
		$total['xtotals'][] = array(
			'code' => 'totals',
			'title' => $language['text_total'],
			'value' => max( 0, $total['total'] ),
			'sort_order' => $this->config_ext['totals_sort_order'] );
	}
}
