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

class shops_filter extends shops_global
{
	public function __construct( $productRegistry )
	{
		parent::__construct( $productRegistry );
	}

	public function addFilter( $data )
	{

		$this->db->query( 'INSERT INTO ' . $this->table . '_filter_group SET sort_order = ' . ( int )$data['sort_order'] );

		$filter_group_id = $this->db->lastInsertId();

		foreach( $data['filter_group_description'] as $language_id => $value )
		{
			$this->db->query( 'INSERT INTO ' . $this->table . '_filter_group_description SET filter_group_id = ' . ( int )$filter_group_id . ', language_id = ' . ( int )$language_id . ', name = ' . $this->db->quote( $value['name'] ) );
		}

		if( isset( $data['filter'] ) )
		{
			foreach( $data['filter'] as $filter )
			{
				$this->db->query( 'INSERT INTO ' . $this->table . '_filter SET filter_group_id = ' . ( int )$filter_group_id . ', sort_order = ' . ( int )$filter['sort_order'] );

				$filter_id = $this->db->lastInsertId();

				foreach( $filter['filter_description'] as $language_id => $filter_description )
				{
					$this->db->query( 'INSERT INTO ' . $this->table . '_filter_description SET filter_id = ' . ( int )$filter_id . ', language_id = ' . ( int )$language_id . ', filter_group_id = ' . ( int )$filter_group_id . ', name = ' . $this->db->quote( $filter_description['name'] ) );
				}
			}
		}

		return $filter_group_id;
	}

	public function editFilter( $filter_group_id, $data )
	{
		$this->db->query( 'UPDATE ' . $this->table . '_filter_group SET sort_order = ' . ( int )$data['sort_order'] . ' WHERE filter_group_id = ' . ( int )$filter_group_id );

		$this->db->query( 'DELETE FROM ' . $this->table . '_filter_group_description WHERE filter_group_id = ' . ( int )$filter_group_id );

		foreach( $data['filter_group_description'] as $language_id => $value )
		{
			$this->db->query( 'INSERT INTO ' . $this->table . '_filter_group_description SET filter_group_id = ' . ( int )$filter_group_id . ', language_id = ' . ( int )$language_id . ', name = ' . $this->db->quote( $value['name'] ) );
		}

		$this->db->query( 'DELETE FROM ' . $this->table . '_filter WHERE filter_group_id = ' . ( int )$filter_group_id );
		$this->db->query( 'DELETE FROM ' . $this->table . '_filter_description WHERE filter_group_id = ' . ( int )$filter_group_id );

		if( isset( $data['filter'] ) )
		{
			foreach( $data['filter'] as $filter )
			{
				if( $filter['filter_id'] )
				{
					$this->db->query( 'INSERT INTO ' . $this->table . '_filter SET filter_id = ' . ( int )$filter['filter_id'] . ', filter_group_id = ' . ( int )$filter_group_id . ', sort_order = ' . ( int )$filter['sort_order'] );
				}
				else
				{
					$this->db->query( 'INSERT INTO ' . $this->table . '_filter SET filter_group_id = ' . ( int )$filter_group_id . ', sort_order = ' . ( int )$filter['sort_order'] );
				}

				$filter_id = $this->db->lastInsertId();

				foreach( $filter['filter_description'] as $language_id => $filter_description )
				{
					$this->db->query( 'INSERT INTO ' . $this->table . '_filter_description SET filter_id = ' . ( int )$filter_id . ', language_id = ' . ( int )$language_id . ', filter_group_id = ' . ( int )$filter_group_id . ', name = ' . $this->db->quote( $filter_description['name'] ) );
				}
			}
		}

	}

	public function deleteFilter( $filter_group_id )
	{
		$this->db->query( 'DELETE FROM ' . $this->table . '_filter_group WHERE filter_group_id = ' . ( int )$filter_group_id );
		$this->db->query( 'DELETE FROM ' . $this->table . '_filter_group_description WHERE filter_group_id = ' . ( int )$filter_group_id );
		$this->db->query( 'DELETE FROM ' . $this->table . '_filter WHERE filter_group_id = ' . ( int )$filter_group_id );
		$this->db->query( 'DELETE FROM ' . $this->table . '_filter_description WHERE filter_group_id = ' . ( int )$filter_group_id );
	}

	public function getFilterGroup( $filter_group_id )
	{
		$query = $this->db->query( 'SELECT * FROM ' . $this->table . '_filter_group fg LEFT JOIN ' . $this->table . '_filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE fg.filter_group_id = ' . ( int )$filter_group_id . ' AND fgd.language_id = ' . ( int )$this->current_language_id );

		return $query->fetch();
	}

	public function getFilterGroups( $data = array() )
	{
		$sql = 'SELECT * FROM ' . $this->table . '_filter_group fg LEFT JOIN ' . $this->table . '_filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE fgd.language_id = ' . ( int )$this->current_language_id;

		$sort_data = array( 'fgd.name', 'fg.sort_order' );

		if( isset( $data['sort'] ) && in_array( $data['sort'], $sort_data ) )
		{
			$sql .= ' ORDER BY ' . $data['sort'];
		}
		else
		{
			$sql .= ' ORDER BY fgd.name';
		}

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

		$query = $this->db->query( $sql )->fetchAll();

		return $query;
	}

	public function getFilterGroupDescriptions( $filter_group_id )
	{
		$filter_group_data = array();

		$query = $this->db->query( 'SELECT * FROM ' . $this->table . '_filter_group_description WHERE filter_group_id = ' . ( int )$filter_group_id )->fetchAll();

		foreach( $query as $result )
		{
			$filter_group_data[$result['language_id']] = array( 'name' => $result['name'] );
		}

		return $filter_group_data;
	}

	public function getFilter( $filter_id )
	{
		return $this->db->query( 'SELECT *, (SELECT name FROM ' . $this->table . '_filter_group_description fgd WHERE f.filter_group_id = fgd.filter_group_id AND fgd.language_id = ' . ( int )$this->current_language_id . ') groupname FROM ' . $this->table . '_filter f LEFT JOIN ' . $this->table . '_filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id = ' . ( int )$filter_id . ' AND fd.language_id = ' . ( int )$this->current_language_id )->fetch();
	}

	public function getFilters( $data )
	{
		$sql = 'SELECT *, (SELECT name FROM ' . $this->table . '_filter_group_description fgd WHERE f.filter_group_id = fgd.filter_group_id AND fgd.language_id = ' . ( int )$this->current_language_id . ') groupname FROM ' . $this->table . '_filter f LEFT JOIN ' . $this->table . '_filter_description fd ON (f.filter_id = fd.filter_id) WHERE fd.language_id = ' . ( int )$this->current_language_id;

		if( ! empty( $data['filter_name'] ) )
		{
			$sql .= ' AND fd.name LIKE ' . $this->db->quote( $data['filter_name'] ) . '%';
		}

		$sql .= ' ORDER BY f.sort_order ASC';

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

		return $this->db->query( $sql )->fetchAll();

	}

	public function getFilterDescriptions( $filter_group_id )
	{
		$filter_data = array();

		$filter_query = $this->db->query( 'SELECT * FROM ' . $this->table . '_filter WHERE filter_group_id = ' . ( int )$filter_group_id )->fetchAll();

		foreach( $filter_query as $filter )
		{
			$filter_description_data = array();

			$filter_description_query = $this->db->query( 'SELECT * FROM ' . $this->table . '_filter_description WHERE filter_id = ' . ( int )$filter['filter_id'] )->fetchAll();

			foreach( $filter_description_query as $filter_description )
			{
				$filter_description_data[$filter_description['language_id']] = array( 'name' => $filter_description['name'] );
			}

			$filter_data[] = array(
				'filter_id' => $filter['filter_id'],
				'filter_description' => $filter_description_data,
				'sort_order' => $filter['sort_order'] );
		}

		return $filter_data;
	}

	public function getTotalFilterGroups()
	{
		return $this->db->query( 'SELECT COUNT(*) total FROM ' . $this->table . '_filter_group' )->fetchColumn();

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
