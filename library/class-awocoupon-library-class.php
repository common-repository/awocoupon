<?php
/**
 * AwoCoupon
 *
 * @package WordPress AwoCoupon
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 **/

if ( ! defined( '_AWO_' ) ) {
	exit;
}

class AwoCoupon_Library_Class {

	var $_entry = null;
	var $_id = null;
	var $_type = null;
	var $_pagination = null;

	public $name;
	protected $state;

	public function __construct() {
		$this->state = new stdclass();

		$this->_layout = AC()->helper->get_request( 'layout', 'default' );

		$this->limit = AC()->helper->get_userstate_request( 'global.limit', 'limit', AC()->store->get_itemsperpage() );
		$current_page = AC()->helper->get_userstate_request( $this->name . '.page', 'paged', 1 );
		$current_page = max( 1, (int) $current_page );
		$this->limitstart = 1 < $current_page ? $this->limit * ( $current_page - 1 ) : 0;

		$this->current_url = AC()->helper->get_request( 'urlx' );

		$this->set_state( 'limit', $this->limit );
		$this->set_state( 'limitstart', $this->limitstart );

		$cid = AC()->helper->get_request( 'id', 0 );
		$this->set_id( (int) $cid );
	}

	public function get_state( $property = null, $default = null ) {
		return null === $property ? $this->state : ( isset( $this->state->{$property} ) ? $this->state->{$property} : null );
	}

	public function set_state( $property, $value = null ) {
		$this->state->{$property} = $value;
	}

	public function buildquery_orderby() {

		$prefix = '.site';
		if ( AC()->is_request( 'admin' ) ) {
			$prefix = '.admin';
		}
		$filter_order = AC()->helper->get_userstate_request( $this->name . $prefix . '.orderby', 'orderby', $this->_orderby );
		$filter_order_dir = AC()->helper->get_userstate_request( $this->name . $prefix . '.order', 'order', '' );

		$sortable = $this->get_sortable_columns();
		$orderby = isset( $sortable[ $filter_order ] ) ? $sortable[ $filter_order ] . ' ' . $filter_order_dir : '';

		return $orderby;
	}

	public function display_list() {

		$this->get_data();
		$columns = $this->get_columns();

		$this->current_url = AC()->helper->get_request( 'urlx' );
		$current_url = $this->current_url;
		$current_url = $this->remove_query_arg( 'orderby', $current_url );
		$current_url = $this->remove_query_arg( 'order', $current_url );
		$query_separator = $this->query_and_or_questionmark( $current_url );
		$sortable = $this->get_sortable_columns();

		$html_header = '';
		foreach ( $columns as $key => $title ) {
			$class_sortable = '';
			$class_ordering = '';
			$class_td = '';
			if ( isset( $sortable[ $key ] ) ) {
				$filter_order = AC()->helper->get_userstate_request( $this->name . '.orderby', 'orderby', $this->_orderby );
				$filter_order_dir = AC()->helper->get_userstate_request( $this->name . '.order', 'order', '' );
				if ( $key == $filter_order ) {
					$class_sortable = 'sorted';
					$class_ordering = empty( $filter_order_dir ) || 'asc' == $filter_order_dir ? 'asc' : 'desc';
					$direction = empty( $filter_order_dir ) || 'asc' == $filter_order_dir ? 'desc' : 'asc';
				} else {
					$class_sortable = 'sortable';
					$class_ordering = empty( $filter_order_dir ) || 'asc' == $filter_order_dir ? 'desc' : 'asc';
					$direction = empty( $filter_order_dir ) || 'asc' == $filter_order_dir ? 'asc' : 'desc';
				}
				$title = '<a href="' . $current_url . $query_separator . 'orderby=' . $key . '&order=' . $direction . '"><span>' . $title . '</span><span class="sorting-indicator"></span></a>';
			}
			if ( 'cb' == $key ) {
				$class_td .= ' check-column ';
			}
			$html_header .= '<th class="manage-column column-primary ' . $class_sortable . ' ' . $class_ordering . ' ' . $class_td . '">' . $title . '</th>';
		}

		$html_rows = '';
		if ( isset( $this->_data ) && is_array( $this->_data ) ) {
			foreach ( $this->_data as $row ) {
				$html_rows .= '<tr>';
				foreach ( $columns as $key => $title ) {
					$class_td = '';
					if ( 'cb' == $key ) {
						$class_td .= ' checkcolumn ';
					}
					$func = 'column_' . $key;
					$html_rows .= '<td class="' . $class_td . '">';
					$html_rows .= method_exists( $this, $func ) ? $this->$func( $row ) : $this->column_default( $row, $key );
					if ( AC()->is_request( 'admin' ) ) {
						if ( $key == $this->_primary ) {
							$html_rows .= $this->row_actions( $this->get_row_action( $row ) );
						}
					}
					$html_rows .= '</td>';
				}
				$html_rows .= '</tr>';
			}
		}

		return '
			<div>
				<table class="wp-list-table tableinne widefat striped posts" cellspacing="1">
				<thead>' . $html_header . '</thead>
				<tbody>' . $html_rows . '</tbody>
				</table>
			</div>
			<div>' . $this->get_pagination()->get_list_footer() . '</div>
		';
		//return '
		//	<table class="wp-list-table widefat fixed striped posts" cellspacing="1">
		//	<thead>'.$html_header.'</thead>
		//	<tbody>'.$html_rows.'</tbody>
		//	<tfoot><tr><td colspan="'.count($columns).'">'.$this->get_pagination()->get_list_footer().'</td></tr></tfoot>
		//	</table>
		//';
	}

	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= '<span class="' . $action . '">' . $link . $sep . '</span>';
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . AC()->lang->__( 'Show more details' ) . '</span></button>';

		return $out;
	}

	public function get_language_ids( $table, $id = 0 ) {
		$columns = AC()->db->get_column( 'DESC ' . $table );

		$idlang_fields = array();
		foreach ( $columns as $column ) {
			if ( substr( $column, 0, 7 ) == 'idlang_' ) {
				$idlang_fields[ $column ] = 0;
			}
		}

		$rows = AC()->db->get_objectlist( 'SELECT ' . implode( ',', array_keys( $idlang_fields ) ) . ' FROM ' . $table . ' WHERE id=' . (int) $id );
		foreach ( $rows as $row ) {
			foreach ( $idlang_fields as $k => $v ) {
				$idlang_fields[ $k ] = array(
					'name' => substr( $k, 7 ),
					'elem_id' => $row->{$k},
				);
			}
		}

		return $idlang_fields;
	}

	public function query_and_or_questionmark( $url ) {
		$query_separator = '?';
		if ( ! empty( $url ) ) {
			list( $part1, $part2 ) = explode( '#', $url );
			if ( ! empty( $part2 ) ) {
				if ( strpos( $part2, '?' ) !== false ) {
					$query_separator = '&';
				}
			} elseif ( strpos( $part1, '?' ) !== false ) {
				$query_separator = '&';
			}
		}
		return $query_separator;
	}

	public function remove_query_arg( $key, $query ) {
		if ( empty( $query ) ) {
			return $query;
		}

		list( $part1, $part2 ) = explode( '#', $query );
		$part_to_use = empty( $part2 ) ? $part1 : $part2;
		if ( strpos( $part_to_use, '?' ) === false ) {
			return $query;
		}
		list( $link, $query_string ) = explode( '?', $part_to_use );
		$tmp = array();
		parse_str( $query_string, $tmp );
		unset( $tmp[ $key ] );

		if ( empty( $tmp ) ) {
			return empty( $part2 )
				? $link
				: $part1 . '#' . $link;
		} else {
			return empty( $part2 )
				? $link . '?' . http_build_query( $tmp )
				: $part1 . '#' . $link . '?' . http_build_query( $tmp );
		}
	}

	public function prepare_url( $queries ) {
		if ( ! is_array( $queries ) ) {
			$queries = array( $queries );
		}

		$current_url = $this->current_url;
		foreach ( $queries as $query ) {
			$current_url = $this->remove_query_arg( $query, $current_url );
		}
		$query_separator = $this->query_and_or_questionmark( $current_url );

		return $current_url . $query_separator;
	}

	protected function get_sortable_columns() {
		return array();
	}

	public function get( $property, $default = null ) {
		if ( $this->_loadEntry() ) {
			if ( isset( $this->_entry->{$property} ) ) {
				return $this->_entry->{$property};
			}
		}
		return $default;
	}

	public function set_id( $id ) {
		// Set entry id and wipe data
		$this->_id = $id;
		$this->_entry = null;
	}

	public function get_list( $query, $key = null, $limitstart = 0, $limit = 0 ) {
		$query = trim( $query );
		$_iscount = false;
		if ( strtolower( substr( $query, 0, 7 ) ) == 'select ' ) {
			$_iscount = true;
			$query = 'SELECT SQL_CALC_FOUND_ROWS ' . substr( $query, 7 );
		}

		if ( ! empty( $limit ) ) {
			$query .= ' LIMIT ' . (int) $limit;
		}
		if ( ! empty( $limitstart ) ) {
			$query .= ' OFFSET ' . (int) $limitstart;
		}
		$results = AC()->db->get_objectlist( $query, $key );

		if ( $_iscount ) {
			$this->_total = AC()->db->get_value( 'SELECT FOUND_ROWS() as totalRows' );
		}

		return $results;
	}

	public function get_data() {
		if ( empty( $this->_data ) ) {
			$query = $this->buildquery();
			$this->_data = $this->get_list( $query, null, $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );
		}
		return $this->_data;
	}

	public function get_total() {
		if ( empty( $this->_total ) ) {
			$query = $this->buildquery();
			$this->_total = $this->get_list_count( $query );
		}
		return $this->_total;
	}

	protected function get_list_count( $query ) {
		$rows = AC()->db->get_arraylist( $query );
		return (int) count( $rows );
	}

	public function get_pagination() {
		if ( empty( $this->_pagination ) ) {
			$this->_pagination = AC()->helper->new_class( 'Awocoupon_Library_Pagination_Base' );
			$this->_pagination->init( $this->get_total(), $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );
		}
		return $this->_pagination;
	}

	protected function get_row_action( $row ) {
		return array();
	}

	public function get_entry() {

		$row = JTable::getInstance( $this->_type, 'AwoCouponTable' );
		$row->load( $this->_id );
		$this->_entry = $row;

		return $this->_entry;
	}

}
