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
 
if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );

class shops_store extends shops_global
{ 
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );
	}
	
	public function getStores( ) 
	{ 
		return $this->getdbCache( 'SELECT * FROM ' . $this->table . '_store ORDER BY url', 'store', 'store_id' );
	}
}
