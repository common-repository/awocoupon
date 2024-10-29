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

class AwoCoupon_Public_Class_Coupon extends AwoCoupon_Library_Class {

	public function __construct( $id = 0 ) {
		$this->name = 'coupon';
		$this->_id = $id;
		$this->_orderby = 'coupon_code';
		parent::__construct();
	}

	public function get_columns() {
		$columns = array(
			'coupon_code' => AC()->lang->__( 'Coupon Code' ),
			'coupon_value_type' => AC()->lang->__( 'Value Type' ),
			'coupon_value' => AC()->lang->__( 'Value' ),
			'startdate' => AC()->lang->__( 'Start Date' ),
			'expiration' => AC()->lang->__( 'Expiration' ),
		);
		return $columns;
	}

	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'c.id',
			'coupon_code' => 'coupon_code',
			'coupon_value_type' => 'coupon_value_type',
			'coupon_value' => 'coupon_value',
			'startdate' => 'startdate',
			'expiration' => 'expiration',
		);
		return $sortable_columns;
	}

	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	public function column_coupon_value_type( $row ) {
		return AC()->helper->vars( 'function_type', $row->function_type );
	}

	public function column_coupon_value( $row ) {
		$price = '';
		if ( ! empty( $row->coupon_value ) ) {
			$price = 'amount' == $row->coupon_value_type
				? AC()->storecurrency->format( $row->coupon_value )
				: round( $row->coupon_value ) . '%';
		}
		return $price;
	}

	public function column_startdate( $row ) {
		return ! empty( $row->startdate ) ? AC()->helper->get_date( $row->startdate ) : '';
	}

	public function column_expiration( $row ) {
		return ! empty( $row->expiration ) ? AC()->helper->get_date( $row->expiration ) : '';
	}

	public function get_data() {
		// Lets load the files if it doesn't already exist
		if ( empty( $this->_data ) ) {
			$this->_data = $this->get_list( $this->buildquery(), 'id', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );

			$user = AC()->helper->get_user();

			$coupon_ids = array();
			foreach ( $this->_data as $row ) {
				$coupon_ids[] = $row->id;
			}
		}

		return $this->_data;
	}

	public function buildquery() {

		$sql_master = 'SELECT id FROM #__awocoupon WHERE 1!=1';

		$user = AC()->helper->get_user();
		if ( empty( $user->id ) ) {
			return $sql_master;
		}

		$cc_codes = array();
		$current_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );

		// find all coupons assigned to specific customer or customer group
		$sql = 'SELECT u.coupon_id,c.num_of_uses_total,c.num_of_uses_customer
				  FROM #__awocoupon c
				  JOIN #__awocoupon_asset u ON u.coupon_id=c.id AND u.asset_key=0 AND u.asset_type="user"
				 WHERE c.estore="' . AWOCOUPON_ESTORE . '" AND u.asset_id=' . $user->id . ' AND c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
		';
		$shoppergroups = AC()->store->get_group_ids( $user->id );
		if ( ! empty( $shoppergroups ) ) {
			$sql .= '    
											UNION
						 
					SELECT u.coupon_id,c.num_of_uses_total,c.num_of_uses_customer
					  FROM #__awocoupon c
					  JOIN #__awocoupon_asset u ON u.coupon_id=c.id AND u.asset_key=0 AND u.asset_type="usergroup"
					 WHERE c.estore="' . AWOCOUPON_ESTORE . '" AND u.asset_id IN (' . implode( ',', $shoppergroups ) . ') AND c.state="published"
					   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
							 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
							 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
							 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
						   )
			';
		}
		$cc_codes = AC()->db->get_objectlist( $sql, 'coupon_id' );

		$all_found_codes = $cc_codes;

		// remove all coupons where the number of uses have been used up
		foreach ( $all_found_codes as $row ) {
			if ( ! empty( $row->num_of_uses_customer ) ) {
				$userlist = array();
				$cnt = AC()->db->get_value( 'SELECT COUNT(id) AS cnt FROM #__awocoupon_history WHERE coupon_id=' . $row->coupon_id . ' AND user_id=' . $user->id . ' GROUP BY coupon_id,user_id' );
				if ( ! empty( $cnt ) && $cnt >= $row->num_of_uses_customer ) {
					unset( $all_found_codes[ $row->coupon_id ] );
					continue;
				}
			}
			if ( ! empty( $row->num_of_uses_total ) ) {
				$num = AC()->db->get_value( 'SELECT COUNT(id) FROM #__awocoupon_history WHERE coupon_id=' . $row->coupon_id . ' GROUP BY coupon_id' );
				if ( ! empty( $num ) && $num >= $row->num_of_uses_total ) {
					unset( $all_found_codes[ $row->coupon_id ] );
					continue;
				}
			}
		}

		if ( ! empty( $all_found_codes ) ) {
			$orderby = $this->buildquery_orderby();
			if ( ! empty( $orderby ) ) {
				$orderby = ' ORDER BY ' . $orderby . ' ';
			}
			$sql_master = '
				SELECT c.id,c.function_type,c.coupon_code,c.coupon_value_type,c.coupon_value,c.startdate,c.expiration
				  FROM #__awocoupon c
				 WHERE c.id IN (' . implode( ',', array_keys( $all_found_codes ) ) . ')
				 GROUP BY c.id
				 ' . $orderby . '
			';
		}

		return $sql_master;
	}

}
