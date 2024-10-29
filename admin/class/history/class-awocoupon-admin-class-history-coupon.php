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

/**
 * Class
 */
class AwoCoupon_Admin_Class_History_Coupon extends AwoCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'historycoupon';
		$this->_id = $id;
		$this->_orderby = 'coupon_code';
		$this->_primary = 'coupon_code';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'coupon_code' => AC()->lang->__( 'Coupon Code' ),
			'user_email' => AC()->lang->__( 'E-mail' ),
			'user_id' => AC()->lang->__( 'UserID' ),
			'username' => AC()->lang->__( 'Username' ),
			'lastname' => AC()->lang->__( 'Last Name' ),
			'firstname' => AC()->lang->__( 'First Name' ),
			'discount' => AC()->lang->__( 'Discount' ),
			'order_number' => AC()->lang->__( 'Order Number' ),
			'order_date' => AC()->lang->__( 'Order Date' ),
			'id' => AC()->lang->__( 'ID' ),
		);
		return $columns;
	}

	/**
	 * Sortable columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'uu.id',
			'coupon_code' => 'c.coupon_code',
			'user_id' => 'uu.user_id',
			'user_email' => 'uu.user_email',
			'username' => '_username',
			'lastname' => '_lname',
			'firstname' => '_fname',
			'discount' => 'discount',
			'order_number' => 'c.order_id',
			'order_date' => '_created_on',
		);
		return $sortable_columns;
	}

	/**
	 * Action row column
	 *
	 * @param object $row the object.
	 */
	protected function get_row_action( $row ) {
		return array(
			'delete' => '<a href="#/history?task=couponDelete&id=' . $row->use_id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
		);
	}

	/**
	 * Checkbox column
	 *
	 * @param object $row the object.
	 */
	public function column_cb( $row ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%1$s" />', $row->use_id );
	}

	/**
	 * Default column behavior
	 *
	 * @param object $item the object.
	 * @param string $column_name the column.
	 */
	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	/**
	 * Id column
	 *
	 * @param object $row the object.
	 */
	public function column_id( $row ) {
		return $row->use_id;
	}

	/**
	 * Coupon code column
	 *
	 * @param object $row the object.
	 */
	public function column_coupon_code( $row ) {
		$extra = $row->coupon_id != $row->coupon_entered_id ? ' (' . $row->coupon_code . ')' : '';
		return $row->coupon_entered_code . $extra;
	}

	/**
	 * Username column
	 *
	 * @param object $row the object.
	 */
	public function column_username( $row ) {
		return $row->_username;
	}

	/**
	 * Lastname column
	 *
	 * @param object $row the object.
	 */
	public function column_lastname( $row ) {
		return $row->_lname;
	}

	/**
	 * Firstname column
	 *
	 * @param object $row the object.
	 */
	public function column_firstname( $row ) {
		return $row->_fname;
	}

	/**
	 * Discount column
	 *
	 * @param object $row the object.
	 */
	public function column_discount( $row ) {
		return number_format( $row->discount, 2 );
	}

	/**
	 * Order number column
	 *
	 * @param object $row the object.
	 */
	public function column_order_number( $row ) {
		return ! empty( $row->order_id ) ? '<a href="' . AC()->store->get_order_link( $row->order_id ) . '">' . $row->order_number . '</a>' : '';
	}

	/**
	 * Order date column
	 *
	 * @param object $row the object.
	 */
	public function column_order_date( $row ) {
		return ! empty( $row->_created_on ) ? date( 'Y-m-d', strtotime( $row->_created_on ) ) : '';
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();
		$having = $this->buildquery_having();

		$sql = AC()->store->sql_history_coupon( $where, $having, $orderby );

		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {
		$filter_state = AC()->helper->get_userstate_request( $this->name . '.filter_state', 'filter_state', '' );
		$filter_coupon_value_type = AC()->helper->get_userstate_request( $this->name . '.filter_coupon_value_type', 'filter_coupon_value_type', '' );
		$filter_discount_type = AC()->helper->get_userstate_request( $this->name . '.filter_discount_type', 'filter_discount_type', '' );
		$filter_function_type = AC()->helper->get_userstate_request( $this->name . '.filter_function_type', 'filter_function_type', '' );
		$filter_tag = AC()->helper->get_userstate_request( $this->name . '.filter_tag', 'filter_tag', '' );
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );
		$search_type = AC()->helper->get_userstate_request( $this->name . '.search_type', 'search_type', '' );

		$where = array();

		if ( $filter_state ) {
			if ( 'published' == $filter_state ) {
				$current_date = date( 'Y-m-d H:i:s' );
				$where[] = 'c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="") 	AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="") 	AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"		AND c.expiration>="' . $current_date . '")
					   )
				';
			} elseif ( 'unpublished' == $filter_state ) {
				$current_date = date( 'Y-m-d H:i:s' );
				$where[] = '(c.state="unpublished" OR c.startdate>"' . $current_date . '" OR c.expiration<"' . $current_date . '")';
			} else {
				$where[] = 'c.state="' . AC()->db->escape( $filter_state ) . '"';
			}
		}
		if ( $filter_coupon_value_type ) {
			$where[] = 'c.coupon_value_type = \'' . $filter_coupon_value_type . '\'';
		}
		if ( $filter_discount_type ) {
			$where[] = 'c.discount_type = \'' . $filter_discount_type . '\'';
		}
		if ( $filter_function_type ) {
			$where[] = 'c.function_type = \'' . $filter_function_type . '\'';
		}
		if ( $filter_tag ) {
			$where[] = 't.tag = \'' . $filter_tag . '\'';
		}
		if ( $search ) {
			if ( 'coupon' == $search_type ) {
				$where[] = 'LOWER(c.coupon_code) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'email' == $search_type ) {
				$where[] = 'LOWER(uu.user_email) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			}
		}

		return $where;
	}

	/**
	 * Query having clause
	 */
	public function buildquery_having() {
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );
		$search_type = AC()->helper->get_userstate_request( $this->name . '.search_type', 'search_type', '' );

		$having = array();

		if ( $search ) {
			if ( 'user' == $search_type ) {
				$having[] = 'LOWER(_username) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'last' == $search_type ) {
				$having[] = 'LOWER(_lname) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'first' == $search_type ) {
				$having[] = 'LOWER(_fname) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'order' == $search_type ) {
				$having[] = 'order_number LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'date' == $search_type ) {
				$having[] = '_created_on LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			}
		}

		return $having;
	}

	/**
	 * Delete items
	 *
	 * @param array $cids the items to delete.
	 */
	public function delete( $cids ) {

		$cids = AC()->helper->scrubids( $cids );
		if ( empty( $cids ) ) {
			return true;
		}

		AC()->db->query( 'DELETE FROM #__awocoupon_history WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = AC()->db->get_table_columns( '#__awocoupon_history' );
		$this->_entry->username = '';
		$this->_entry->coupon_coupon = '';
		$this->_entry->coupon_discount = '';
		$this->_entry->shipping_discount = '';

		return $this->_entry;
	}

	/**
	 * Save item
	 *
	 * @param array $data the data to save.
	 */
	public function save( $data ) {
		$errors = array();

		// Set null fields.
		$data['coupon_entered_id'] = null;
		$data['productids'] = null;
		if ( empty( $data['coupon_id'] ) ) {
			$data['coupon_id'] = 0;
		}
		if ( empty( $data['coupon_discount'] ) ) {
			$data['coupon_discount'] = 0;
		}
		if ( empty( $data['shipping_discount'] ) ) {
			$data['shipping_discount'] = 0;
		}
		if ( empty( $data['order_id'] ) ) {
			$data['order_id'] = null;
		}

		$data['user_id'] = (int) AC()->db->get_value( 'SELECT id FROM #__users WHERE id=' . (int) $data['user_id'] );

		$row = AC()->db->get_table_instance( '#__awocoupon_history', 'id', (int) $data['id'] );
		$row = AC()->db->bind_table_instance( $row, $data );
		if ( ! $row ) {
			$errors[] = AC()->lang->__( 'Unable to bind item' );
		}

		$row->estore = AWOCOUPON_ESTORE;
		if ( empty( $row->coupon_discount ) ) {
			$row->coupon_discount = 0;
		}
		if ( empty( $row->shipping_discount ) ) {
			$row->shipping_discount = 0;
		}

		// Make sure the data is valid.
		$tmperr = $this->validate( $row, $data );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// Take a break and return if there are any errors.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		// Store the entry to the database.
		$row = AC()->db->save_table_instance( '#__awocoupon_history', $row );

		$this->_entry = $row;

		return;
	}

	/**
	 * Check item before saving
	 *
	 * @param object $row table row.
	 * @param array  $post data turned in.
	 */
	public function validate( $row, $post ) {
		$err = array();

		if ( ! empty( $post['order_id'] ) ) {
			$tmp = AC()->store->get_order( $post['order_id'] );
			if ( empty( $tmp ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__('Order Number') );
			}
		}

		$coupon_row = AC()->db->get_object( 'SELECT * FROM #__awocoupon WHERE id=' . (int) $post['coupon_id'] . ' AND estore="' . AWOCOUPON_ESTORE . '" AND state="published"' );
		if ( empty( $coupon_row->id ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Code' ) );
		}

		if ( empty( $row->coupon_id ) || ! ctype_digit( $row->coupon_id ) ) {
			$err[] = AC()->lang->_e_select( AC()->lang->__( 'Coupon' ) );
		}
		if ( ! empty( $row->user_id ) && ! ctype_digit( $row->user_id ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Username' ) );
		}
		if ( empty( $row->user_email ) || ! AC()->helper->is_email( $row->user_email ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'E-mail' ) );
		}
		if ( ! empty( $row->coupon_discount ) && ( ! is_numeric( $row->coupon_discount ) || $row->coupon_discount < 0 ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Discount (Product)' ) );
		}
		if ( ! empty( $row->shipping_discount ) && ( ! is_numeric( $row->shipping_discount ) || $row->shipping_discount < 0 ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Discount (Shipping)' ) );
		}

		return $err;
	}
}
