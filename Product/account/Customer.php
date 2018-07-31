<?php

namespace NukeViet\Product\Account;

use NukeViet\Product\General;

class Customer extends General
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );

	}
	public function addCustomer( $data )
	{
		if( isset( $data['customer_group_id'] ) && is_array( $this->config->get( 'config_customer_group_display' ) ) && in_array( $data['customer_group_id'], $this->config->get( 'config_customer_group_display' ) ) )
		{
			$customer_group_id = $data['customer_group_id'];
		}
		else
		{
			$customer_group_id = $this->config->get( 'config_customer_group_id' );
		}

		$this->load->model( 'account/customer_group' );

		$customer_group_info = $this->model_account_customer_group->getCustomerGroup( $customer_group_id );

		$db->query( 'INSERT INTO ' . $this->table . '_customer SET customer_group_id = ' . ( int )$customer_group_id . ', store_id = ' . ( int )$this->config->get( 'config_store_id' ) . ', language_id = ' . ( int )$this->config->get( 'config_language_id' ) . ', firstname = ' . $db->quote( $data['firstname'] ) . ', lastname = ' . $db->quote( $data['lastname'] ) . ', email = ' . $db->quote( $data['email'] ) . ', telephone = ' . $db->quote( $data['telephone'] ) . ', fax = ' . $db->quote( $data['fax'] ) . ', custom_field = ' . $db->quote( isset( $data['custom_field']['account'] ) ? json_encode( $data['custom_field']['account'] ) : '' ) . ', salt = ' . $db->quote( $salt = token( 9 ) ) . ', password = ' . $db->quote( sha1( $salt . sha1( $salt . sha1( $data['password'] ) ) ) ) . ', newsletter = ' . ( isset( $data['newsletter'] ) ? ( int )$data['newsletter'] : 0 ) . ', ip = ' . $db->quote( $this->request->server['REMOTE_ADDR'] ) . ', status = '1', approved = ' . ( int )! $customer_group_info['approval'] .
			', date_added = NOW()' );

		$customer_id = $db->getLastId();

		$db->query( 'INSERT INTO ' . $this->table . '_address SET customer_id = ' . ( int )$customer_id . ', firstname = ' . $db->quote( $data['firstname'] ) . ', lastname = ' . $db->quote( $data['lastname'] ) . ', company = ' . $db->quote( $data['company'] ) . ', address_1 = ' . $db->quote( $data['address_1'] ) . ', address_2 = ' . $db->quote( $data['address_2'] ) . ', city = ' . $db->quote( $data['city'] ) . ', postcode = ' . $db->quote( $data['postcode'] ) . ', country_id = ' . ( int )$data['country_id'] . ', zone_id = ' . ( int )$data['zone_id'] . ', custom_field = ' . $db->quote( isset( $data['custom_field']['address'] ) ? json_encode( $data['custom_field']['address'] ) : '' ) . '' );

		$address_id = $db->getLastId();

		$db->query( 'UPDATE ' . $this->table . '_customer SET address_id = ' . ( int )$address_id . ' WHERE customer_id = ' . ( int )$customer_id . '' );

		$this->load->language( 'mail/customer' );

		$subject = sprintf( $this->language->get( 'text_subject' ), html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8' ) );

		$message = sprintf( $this->language->get( 'text_welcome' ), html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8' ) ) . '\n\n';

		if( ! $customer_group_info['approval'] )
		{
			$message .= $this->language->get( 'text_login' ) . '\n';
		}
		else
		{
			$message .= $this->language->get( 'text_approval' ) . '\n';
		}

		$message .= $this->url->link( 'account/login', '', true ) . '\n\n';
		$message .= $this->language->get( 'text_services' ) . '\n\n';
		$message .= $this->language->get( 'text_thanks' ) . '\n';
		$message .= html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8' );

		$mail = new Mail();
		$mail->protocol = $this->config->get( 'config_mail_protocol' );
		$mail->parameter = $this->config->get( 'config_mail_parameter' );
		$mail->smtp_hostname = $this->config->get( 'config_mail_smtp_hostname' );
		$mail->smtp_username = $this->config->get( 'config_mail_smtp_username' );
		$mail->smtp_password = html_entity_decode( $this->config->get( 'config_mail_smtp_password' ), ENT_QUOTES, 'UTF-8' );
		$mail->smtp_port = $this->config->get( 'config_mail_smtp_port' );
		$mail->smtp_timeout = $this->config->get( 'config_mail_smtp_timeout' );

		$mail->setTo( $data['email'] );
		$mail->setFrom( $this->config->get( 'config_email' ) );
		$mail->setSender( html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8' ) );
		$mail->setSubject( $subject );
		$mail->setText( $message );
		$mail->send();

		// Send to main admin email if new account email is enabled
		if( in_array( 'account', ( array )$this->config->get( 'config_mail_alert' ) ) )
		{
			$message = $this->language->get( 'text_signup' ) . '\n\n';
			$message .= $this->language->get( 'text_website' ) . ' ' . html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8' ) . '\n';
			$message .= $this->language->get( 'text_firstname' ) . ' ' . $data['firstname'] . '\n';
			$message .= $this->language->get( 'text_lastname' ) . ' ' . $data['lastname'] . '\n';
			$message .= $this->language->get( 'text_customer_group' ) . ' ' . $customer_group_info['name'] . '\n';
			$message .= $this->language->get( 'text_email' ) . ' ' . $data['email'] . '\n';
			$message .= $this->language->get( 'text_telephone' ) . ' ' . $data['telephone'] . '\n';

			$mail = new Mail();
			$mail->protocol = $this->config->get( 'config_mail_protocol' );
			$mail->parameter = $this->config->get( 'config_mail_parameter' );
			$mail->smtp_hostname = $this->config->get( 'config_mail_smtp_hostname' );
			$mail->smtp_username = $this->config->get( 'config_mail_smtp_username' );
			$mail->smtp_password = html_entity_decode( $this->config->get( 'config_mail_smtp_password' ), ENT_QUOTES, 'UTF-8' );
			$mail->smtp_port = $this->config->get( 'config_mail_smtp_port' );
			$mail->smtp_timeout = $this->config->get( 'config_mail_smtp_timeout' );

			$mail->setTo( $this->config->get( 'config_email' ) );
			$mail->setFrom( $this->config->get( 'config_email' ) );
			$mail->setSender( html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8' ) );
			$mail->setSubject( html_entity_decode( $this->language->get( 'text_new_customer' ), ENT_QUOTES, 'UTF-8' ) );
			$mail->setText( $message );
			$mail->send();

			// Send to additional alert emails if new account email is enabled
			$emails = explode( ',', $this->config->get( 'config_alert_email' ) );

			foreach( $emails as $email )
			{
				if( utf8_strlen( $email ) > 0 && filter_var( $email, FILTER_VALIDATE_EMAIL ) )
				{
					$mail->setTo( $email );
					$mail->send();
				}
			}
		}

		return $customer_id;
	}

	public function editCustomer( $data )
	{
		$customer_id = $this->customer->getId();

		$db->query( 'UPDATE ' . $this->table . '_customer SET firstname = ' . $db->quote( $data['firstname'] ) . ', lastname = ' . $db->quote( $data['lastname'] ) . ', email = ' . $db->quote( $data['email'] ) . ', telephone = ' . $db->quote( $data['telephone'] ) . ', fax = ' . $db->quote( $data['fax'] ) . ', custom_field = ' . $db->quote( isset( $data['custom_field'] ) ? json_encode( $data['custom_field'] ) : '' ) . ' WHERE customer_id = ' . ( int )$customer_id . '' );
	}

	public function editPassword( $email, $password )
	{
		$db->query( 'UPDATE ' . $this->table . '_customer SET salt = ' . $db->quote( $salt = token( 9 ) ) . ', password = ' . $db->quote( sha1( $salt . sha1( $salt . sha1( $password ) ) ) ) . ', code = '' WHERE LOWER(email) = ' . $db->quote( utf8_strtolower( $email ) ) . '' );
	}

	public function editCode( $email, $code )
	{
		$db->query( 'UPDATE `' . $this->table . '_customer` SET code = ' . $db->quote( $code ) . ' WHERE LCASE(email) = ' . $db->quote( utf8_strtolower( $email ) ) . '' );
	}

	public function editNewsletter( $newsletter )
	{
		$db->query( 'UPDATE ' . $this->table . '_customer SET newsletter = ' . ( int )$newsletter . ' WHERE customer_id = ' . ( int )$this->customer->getId() . '' );
	}

	public function getCustomer( $customer_id )
	{
		$query = $db->query( 'SELECT * FROM ' . $this->table . '_customer WHERE customer_id = ' . ( int )$customer_id . '' );

		return $query->row;
	}

	public function getCustomerByEmail( $email )
	{
		$query = $db->query( 'SELECT * FROM ' . $this->table . '_customer WHERE LOWER(email) = ' . $db->quote( utf8_strtolower( $email ) ) . '' );

		return $query->row;
	}

	public function getCustomerByCode( $code )
	{
		$query = $db->query( 'SELECT customer_id, firstname, lastname, email FROM `' . $this->table . '_customer` WHERE code = ' . $db->quote( $code ) . ' AND code != ''' );

		return $query->row;
	}

	public function getCustomerByToken( $token )
	{
		$query = $db->query( 'SELECT * FROM ' . $this->table . '_customer WHERE token = ' . $db->quote( $token ) . ' AND token != ''' );

		$db->query( 'UPDATE ' . $this->table . '_customer SET token = ''' );

		return $query->row;
	}

	public function getTotalCustomersByEmail( $email )
	{
		$query = $db->query( 'SELECT COUNT(*) AS total FROM ' . $this->table . '_customer WHERE LOWER(email) = ' . $db->quote( utf8_strtolower( $email ) ) . '' );

		return $query->row['total'];
	}

	public function getRewardTotal( $customer_id )
	{
		$query = $db->query( 'SELECT SUM(points) AS total FROM ' . $this->table . '_customer_reward WHERE customer_id = ' . ( int )$customer_id . '' );

		return $query->row['total'];
	}

	public function getIps( $customer_id )
	{
		$query = $db->query( 'SELECT * FROM `' . $this->table . '_customer_ip` WHERE customer_id = ' . ( int )$customer_id . '' );

		return $query->rows;
	}

	public function addLoginAttempt( $email )
	{
		$query = $db->query( 'SELECT * FROM ' . $this->table . '_customer_login WHERE email = ' . $db->quote( utf8_strtolower( ( string )$email ) ) . ' AND ip = ' . $db->quote( $this->request->server['REMOTE_ADDR'] ) . '' );

		if( ! $query->num_rows )
		{
			$db->query( 'INSERT INTO ' . $this->table . '_customer_login SET email = ' . $db->quote( utf8_strtolower( ( string )$email ) ) . ', ip = ' . $db->quote( $this->request->server['REMOTE_ADDR'] ) . ', total = 1, date_added = ' . $db->quote( date( 'Y-m-d H:i:s' ) ) . ', date_modified = ' . $db->quote( date( 'Y-m-d H:i:s' ) ) . '' );
		}
		else
		{
			$db->query( 'UPDATE ' . $this->table . '_customer_login SET total = (total + 1), date_modified = ' . $db->quote( date( 'Y-m-d H:i:s' ) ) . ' WHERE customer_login_id = ' . ( int )$query->row['customer_login_id'] . '' );
		}
	}

	public function getLoginAttempts( $email )
	{
		$query = $db->query( 'SELECT * FROM `' . $this->table . '_customer_login` WHERE email = ' . $db->quote( utf8_strtolower( $email ) ) . '' );

		return $query->row;
	}

	public function deleteLoginAttempts( $email )
	{
		$db->query( 'DELETE FROM `' . $this->table . '_customer_login` WHERE email = ' . $db->quote( utf8_strtolower( $email ) ) . '' );
	}
}
