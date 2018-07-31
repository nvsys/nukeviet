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

final class Encyption
{

	private $key;

	public function __construct( $key )
	{
		$this->key = hash( 'sha256', $key, true );
	}

	public function encrypt( $value )
	{
		return strtr( base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, hash( 'sha256', $this->key, true ), $value, MCRYPT_MODE_ECB ) ), '+/=', '-_,' );
	}

	public function decrypt( $value )
	{
		return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, hash( 'sha256', $this->key, true ), base64_decode( strtr( $value, '-_,', '+/=' ) ), MCRYPT_MODE_ECB ) );
	}
}

if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );