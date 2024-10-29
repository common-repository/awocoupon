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

class Awocoupon_Helper_Param {

	var $params = null;

	public function __construct() {
		static $config_data;
		if ( empty( $config_data ) ) {
			$config_data = AC()->db->get_objectlist( 'SELECT id,name,is_json,value FROM #__awocoupon_config', 'name' );
		}
		$this->params = $config_data;
	}

	public function get( $param, $default = '' ) {
		$value = isset( $this->params[ $param ]->value ) ? $this->params[ $param ]->value : '';
		if ( ! empty( $value ) && ! empty( $this->params[ $param ]->is_json ) ) {
			$value = json_decode( $value );
		}
		return ( empty( $value ) && ( 0 !== $value ) && ( '0' !== $value ) ) ? $default : $value;
	}

	public function set( $key, $value = '' ) {
		if ( ! empty( $key ) ) {
			//$value = empty($value) ? 'NULL' : '"'.mysql_real_escape_string($value).'"';

			$is_json = 'NULL';
			if ( is_array( $value ) ) {
				$value = json_encode( $value );
				$is_json = 1;
			}

			if ( ! isset( $this->params[ $key ] ) ) {
				$this->params[ $key ] = new stdClass();
			}
			$this->params[ $key ]->value = $value;

			$value = ( empty( $value ) && ( 0 !== $value ) && ( '0' !== $value ) ) ? 'NULL' : '"' . AC()->db->escape( $value ) . '"';
			$tmp = AC()->db->get_value( 'SELECT name FROM #__awocoupon_config WHERE name="' . $key . '"' );
			$sql = empty( $tmp )
						? 'INSERT INTO #__awocoupon_config (name,value,is_json) VALUES ("' . $key . '",' . $value . ',' . $is_json . ')'
						: 'UPDATE #__awocoupon_config SET value=' . $value . ',is_json=' . $is_json . ' WHERE name="' . $key . '"';
			AC()->db->query( $sql );
		}
	}


}
