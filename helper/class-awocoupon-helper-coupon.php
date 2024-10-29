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

class AwoCoupon_Helper_Coupon {

	public function get_templates() {
		return AC()->db->get_objectlist( 'SELECT id,coupon_code,coupon_code AS label FROM #__awocoupon WHERE estore="' . AWOCOUPON_ESTORE . '" AND state="template" ORDER BY coupon_code,id','id' );
	}

	public function generate( $coupon_id, $coupon_code = null, $expiration = null, $override_user = null ) {

		$coupon_id = (int) $coupon_id;
		if ( ! is_null( $override_user ) ) {
			$override_user = trim( $override_user );
		}
		if ( ! is_null( $expiration ) ) {
			$expiration = trim( $expiration );
		}

		$crow = AC()->db->get_object( 'SELECT * FROM #__awocoupon WHERE id=' . $coupon_id );
		if ( empty( $crow ) ) {
			return false;  // template coupon does not exist
		}

		if ( empty( $coupon_code ) || $this->is_code_used( $coupon_code ) ) {
			$coupon_code = $this->generate_coupon_code();
		}

		$db_expiration = ! empty( $crow->expiration ) ? '"' . $crow->expiration . '"' : 'NULL';
		if ( ! empty( $expiration ) && ctype_digit( $expiration ) ) {
			$db_expiration = '"' . date( 'Y-m-d 23:59:59', time() + ( 86400 * (int) $expiration ) ) . '"';
		}

		$passcode = substr( md5( (string) time() . rand( 1, 1000 ) . $coupon_code ), 0, 6 );

		$sql = 'INSERT INTO #__awocoupon (	
					estore,template_id,coupon_code,upc,passcode,coupon_value_type,coupon_value,coupon_value_def,
					function_type,num_of_uses_total,num_of_uses_customer,min_value,discount_type,startdate,
					expiration,note,params,state
				)
				VALUES ("' . AWOCOUPON_ESTORE . '",
						' . $coupon_id . ',
						"' . $coupon_code . '",
						' . ( ! empty( $crow->upc ) ? '"' . $crow->upc . '"' : 'NULL' ) . ',
						"' . $passcode . '",
						' . ( ! empty( $crow->coupon_value_type ) ? '"' . $crow->coupon_value_type . '"' : 'NULL' ) . ',
						' . ( ! empty( $crow->coupon_value ) ? $crow->coupon_value : 'NULL' ) . ',
						' . ( ! empty( $crow->coupon_value_def ) ? '"' . $crow->coupon_value_def . '"' : 'NULL' ) . ',
						"' . $crow->function_type . '",
						' . ( ! empty( $crow->num_of_uses_total ) ? $crow->num_of_uses_total : 'NULL' ) . ',
						' . ( ! empty( $crow->num_of_uses_customer ) ? $crow->num_of_uses_customer : 'NULL' ) . ',
						' . ( ! empty( $crow->min_value ) ? $crow->min_value : 'NULL' ) . ',
						' . ( ! empty( $crow->discount_type ) ? '"' . $crow->discount_type . '"' : 'NULL' ) . ',
						' . ( ! empty( $crow->startdate ) ? '"' . $crow->startdate . '"' : 'NULL' ) . ',
						' . $db_expiration . ',
						' . ( ! empty( $crow->note ) ? '"' . $crow->note . '"' : 'NULL' ) . ',
						' . ( ! empty( $crow->params ) ? '"' . AC()->db->escape( $crow->params ) . '"' : 'NULL' ) . ',
						"published"
					)';
		AC()->db->query( $sql );
		$gen_coupon_id = AC()->db->get_insertid();

		$new_children_coupons = array();

		AC()->db->query('
				INSERT INTO #__awocoupon_asset (coupon_id,asset_key,asset_type,asset_id,qty,order_by) 
				SELECT ' . $gen_coupon_id . ',asset_key,asset_type,asset_id,qty,order_by FROM #__awocoupon_asset WHERE coupon_id=' . $coupon_id . '
		');

		if ( ! empty( $override_user ) && ctype_digit( trim( $override_user ) ) ) {
			AC()->db->query( 'DELETE FROM #__awocoupon_asset WHERE asset_key=0 AND asset_type IN ("user","usergroup") AND coupon_id=' . $gen_coupon_id );
			AC()->db->query( 'INSERT INTO #__awocoupon_asset ( coupon_id,asset_key,asset_type,asset_id ) VALUES ( ' . $gen_coupon_id . ',0,"user",' . $override_user . ' )' );

			$params = json_decode( $crow->params, true );
			$params['asset'][0]['rows']['user']['type'] = 'user';
			$params['asset'][0]['rows']['user']['mode'] = 'include';
			unset( $params['asset'][0]['rows']['usergroup'] );

			AC()->db->query( 'UPDATE #__awocoupon SET params="' . AC()->db->escape( json_encode( $params ) ) . '" WHERE id=' . $gen_coupon_id );
		}

		AC()->db->query( 'INSERT INTO #__awocoupon_tag (coupon_id,tag) SELECT ' . $gen_coupon_id . ',tag FROM #__awocoupon_tag WHERE coupon_id=' . $coupon_id );

		$obj = new stdClass();
		$obj->coupon_id = $gen_coupon_id;
		$obj->coupon_code = $coupon_code;
		return $obj;
	}

	public function generate_coupon_code( $prefix = '', $suffix = '', $min_length = 8, $max_length = 12, $salt_type = array() ) {
		$salt = '';
		if ( ! empty( $salt_type ) ) {
			if ( in_array( 'lower', $salt_type ) ) {
				$salt .= 'abcdefghjkmnpqrstuvwxyz';
			}
			if ( in_array( 'upper', $salt_type ) ) {
				$salt .= 'ABCDEFGHJKLMNPQRSTUVWXYZ';
			}
			if ( in_array( 'number', $salt_type ) ) {
				$salt .= empty( $salt ) ? '1234567890' : '23456789';
			}
		}
		if ( empty( $salt ) ) {
			$salt = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // all
		}

		$min_length = (int) $min_length;
		if ( $min_length < 4 ) {
			$min_length = 4;
		}

		$max_length = (int) $max_length;
		if ( $max_length < 4 ) {
			$max_length = 4;
		}

		do {
			$coupon_code = trim( $prefix ) . $this->random_code( rand( $min_length, $max_length ), $salt ) . trim( $suffix );
		} while ( $this->is_code_used( $coupon_code ) );

		return $coupon_code;
	}

	public function is_code_used( $code ) {
		AC()->db->get_value( 'SELECT id FROM #__awocoupon WHERE estore="' . AWOCOUPON_ESTORE . '" AND coupon_code="' . $code . '"' );

		if ( empty( $id ) ) {
			return false;
		}
		return true;
	}

	private function random_code( $length, $chars ) {
		$rand_id = '';
		$char_length = strlen( $chars );
		if ( $length > 0 ) {
			for ( $i = 1; $i <= $length; $i++ ) {
				$rand_id .= $chars[ mt_rand( 0, $char_length - 1 ) ];
			}
		}
		return $rand_id;
	}

	public function get_value_print( $valuedef, $coupon_value_type ) {
		if ( empty( $valuedef ) ) {
			return '';
		}

		$vdef_table = array();
		$vdef_options = array();
		$each_row = explode( ';', $valuedef );

		//options
		$tmp = end( $each_row );
		if ( substr( $tmp, 0, 1 ) == '[' ) {
			parse_str( trim( $tmp, '[]' ), $vdef_options );
			array_pop( $each_row );
		}
		reset( $each_row );

		foreach ( $each_row as $row ) {
			if ( strpos( $row, '-' ) !== false ) {
				list( $p, $v ) = explode( '-', $row );
				$vdef_table[ $p ] = $v;
			}
		}

		$vdef_table_tmp = $vdef_table;

		$curr = 0;
		$text = '';
		if ( ! empty( $vdef_options['order'] ) && 'first' != $vdef_options['order'] ) {
			$text .= '<div>' . AC()->lang->__( 'Ordering' ) . ': ' . AC()->helper->vars( 'buy_xy_process_type', $vdef_options['order'] ) . '</div>';
		}
		if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' == $vdef_options['qty_type'] ) {
			$text .= '<div>' . AC()->lang->__( 'Apply Distinct Count' ) . '</div>';
		}
		if ( ! empty( $text ) ) {
			$text .= '<hr>';
		}
		foreach ( $vdef_table as $qty => $val ) {
			$curr++;
			$qty_str = str_repeat( '&nbsp;', max( 0, 3 - strlen( $qty ) ) ) . $qty;
			if ( empty( $vdef_options['type'] ) || 'progressive' == $vdef_options['type'] ) {
				if ( empty( $val ) ) {
					$text .= 1 == $curr
						? '<div>' . sprintf( AC()->lang->__( '%1$s- item(s) >> exclude from discount' ), $qty_str ) . '</div>'
						: '<div>' . sprintf( AC()->lang->__( '%1$s+ item(s) >> exclude from discount' ), $qty_str ) . '</div>';
				} else {
					$val = 'percent' == $coupon_value_type ? round( $val, 2 ) . '%' : number_format( $val, 2 ) . ' ' . AC()->helper->vars( 'coupon_value_type', $coupon_value_type );
					$text .= '<div>' . sprintf( AC()->lang->__( '%1$s+ item(s) >> total discount %2$s' ), $qty_str,$val ) . '</div>';
				}
			} elseif ( 'step' == $vdef_options['type'] ) {
				if ( empty( $val ) ) {
					continue;
				}
				$val = 'percent' == $coupon_value_type ? round( $val, 2 ) . '%' : number_format( $val, 2 ) . ' ' . AC()->helper->vars( 'coupon_value_type', $coupon_value_type );

				$qty2 = 0;
				$found = false;
				foreach ( $vdef_table_tmp as $j => $throwaway ) {
					if ( $found ) {
						$qty2 = $j;
						break;
					}
					if ( $qty != $j ) {
						continue;
					}
					$found = true;
				}

				$qty2_str = empty( $qty2 ) ? '---' : str_repeat( '&nbsp;', max( 0, 3 - strlen( $qty2 - 1 ) ) ) . ( $qty2 - 1 );
				$text .= '<div>' . sprintf( AC()->lang->__( '%1$s to %2$s item(s) >> discount %3$s' ), $qty_str, $qty2_str, $val ) . '</div>';
			}
		}

		return '<pre class="valuedef">' . $text . '</pre>';
	}

	public function is_case_sensitive() {
		$rtn = array_change_key_case( (array) AC()->db->get_object( 'SHOW FULL COLUMNS FROM #__awocoupon LIKE "coupon_code"' ) );
		return substr( $rtn['collation'], -4 ) == '_bin' ? true : false;
	}

}
