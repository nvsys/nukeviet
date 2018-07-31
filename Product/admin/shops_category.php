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

class shops_category extends shops_global
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );
	}

	public function addCategory( $data )
	{

		$this->db->query( 'INSERT INTO ' . $this->table . '_category SET parent_id = ' . ( int )$data['parent_id'] . ', top = ' . ( isset( $data['top'] ) ? ( int )$data['top'] : 0 ) . ', column = ' . ( int )$data['column'] . ', sort_order = ' . ( int )$data['sort_order'] . ', status = ' . ( int )$data['status'] . ', date_modified = NOW(), date_added = NOW()' );

		$category_id = $this->db->lastInsertId();
 
		foreach( $data['category_description'] as $language_id => $value )
		{
			$this->db->query( 'INSERT INTO ' . $this->table . '_category_description SET category_id = ' . ( int )$category_id . ', language_id = ' . ( int )$language_id . ', name = ' . $this->db->quote( $value['name'] ) . ', description = ' . $this->db->quote( $value['description'] ) . ', meta_title = ' . $this->db->quote( $value['meta_title'] ) . ', meta_description = ' . $this->db->quote( $value['meta_description'] ) . ', meta_keyword = ' . $this->db->quote( $value['meta_keyword'] ));
		}
 
		if( isset( $data['category_filter'] ) )
		{
			foreach( $data['category_filter'] as $filter_id )
			{
				$this->db->query( 'INSERT INTO ' . $this->table . '_category_filter SET category_id = ' . ( int )$category_id . ', filter_id = ' . ( int )$filter_id);
			}
		}

		if( isset( $data['category_store'] ) )
		{
			foreach( $data['category_store'] as $store_id )
			{
				$this->db->query( 'INSERT INTO ' . $this->table . '_category_to_store SET category_id = ' . ( int )$category_id . ', store_id = ' . ( int )$store_id);
			}
		}
 
		return $category_id;
	}

	public function editCategory( $category_id, $data )
	{
 
		$this->db->query( 'UPDATE ' . $this->table . '_category SET parent_id = ' . ( int )$data['parent_id'] . ', top = ' . ( isset( $data['top'] ) ? ( int )$data['top'] : 0 ) . ', column = ' . ( int )$data['column'] . ', sort_order = ' . ( int )$data['sort_order'] . ', status = ' . ( int )$data['status'] . ', date_modified = NOW() WHERE category_id = ' . ( int )$category_id);
 
		$this->db->query( 'DELETE FROM ' . $this->table . '_category_description WHERE category_id = ' . ( int )$category_id);

		foreach( $data['category_description'] as $language_id => $value )
		{
			$this->db->query( 'INSERT INTO ' . $this->table . '_category_description SET category_id = ' . ( int )$category_id . ', language_id = ' . ( int )$language_id . ', name = ' . $this->db->quote( $value['name'] ) . ', description = ' . $this->db->quote( $value['description'] ) . ', meta_title = ' . $this->db->quote( $value['meta_title'] ) . ', meta_description = ' . $this->db->quote( $value['meta_description'] ) . ', meta_keyword = ' . $this->db->quote( $value['meta_keyword'] ));
		}
 
		$this->db->query( 'DELETE FROM ' . $this->table . '_category_filter WHERE category_id = ' . ( int )$category_id);

		if( isset( $data['category_filter'] ) )
		{
			foreach( $data['category_filter'] as $filter_id )
			{
				$this->db->query( 'INSERT INTO ' . $this->table . '_category_filter SET category_id = ' . ( int )$category_id . ', filter_id = ' . ( int )$filter_id);
			}
		}

		$this->db->query( 'DELETE FROM ' . $this->table . '_category_to_store WHERE category_id = ' . ( int )$category_id);

		if( isset( $data['category_store'] ) && !empty( $data['category_store'] ) )
		{
			foreach( $data['category_store'] as $store_id )
			{
				$this->db->query( 'INSERT INTO ' . $this->table . '_category_to_store SET category_id = ' . ( int )$category_id . ', store_id = ' . ( int )$store_id);
			}
		}
 
 	}

	public function deleteCategory( $category_id )
	{
		$this->db->query( 'DELETE FROM ' . $this->table . '_category WHERE category_id = ' . ( int )$category_id);
		$this->db->query( 'DELETE FROM ' . $this->table . '_category_description WHERE category_id = ' . ( int )$category_id);
		$this->db->query( 'DELETE FROM ' . $this->table . '_category_filter WHERE category_id = ' . ( int )$category_id);
		$this->db->query( 'DELETE FROM ' . $this->table . '_category_to_store WHERE category_id = ' . ( int )$category_id);
 	}

	public function repairCategories( $parent_id = 0 )
	{
		$query = $this->db->query( 'SELECT * FROM ' . $this->table . '_category WHERE parent_id = ' . ( int )$parent_id )->fetchAll();

		foreach( $query as $category )
		{
			 
		}
	}
 
	public function getCategoryDescriptions( $category_id )
	{
		$category_description_data = array();

		$query = $this->db->query( 'SELECT * FROM ' . $this->table . '_category_description WHERE category_id = ' . ( int )$category_id )->fetchAll();

		foreach( $query as $result )
		{
			$category_description_data[$result['language_id']] = array(
				'name' => $result['name'],
				'meta_title' => $result['meta_title'],
				'meta_description' => $result['meta_description'],
				'meta_keyword' => $result['meta_keyword'],
				'description' => $result['description'] );
		}

		return $category_description_data;
	}

	public function getCategoryFilters( $category_id )
	{
		$category_filter_data = array();

		$query = $this->db->query( 'SELECT * FROM ' . $this->table . '_category_filter WHERE category_id = ' . ( int )$category_id )->fetchAll();

		foreach( $query as $result )
		{
			$category_filter_data[] = $result['filter_id'];
		}

		return $category_filter_data;
	}

	public function getCategoryStores( $category_id )
	{
		$category_store_data = array();

		$query = $this->db->query( 'SELECT * FROM ' . $this->table . '_category_to_store WHERE category_id = ' . ( int )$category_id )->fetchAll();

		foreach( $query as $result )
		{
			$category_store_data[] = $result['store_id'];
		}

		return $category_store_data;
	}
 
	public function getTotalCategories()
	{
		return $this->db->query( 'SELECT COUNT(*) total FROM ' . $this->table . '_category' )->fetchColmn();
   
	}
 
}
