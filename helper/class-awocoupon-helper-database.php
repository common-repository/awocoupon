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

class AwoCoupon_Helper_Database {

	public function get_objectlist( $sql, $key = null ) {
		global $wpdb;

		$rows = $wpdb->get_results( str_replace( '#__', $wpdb->prefix, $sql ), OBJECT );

		$rtn = array();
		if ( ! empty( $rows ) ) {
			if ( empty( $key ) ) {
				$rtn = $rows;
			} else {
				foreach ( $rows as $row ) {
					if ( isset( $row->{$key} ) ) {
						$rtn[ $row->{$key} ] = $row;
					} else {
						$rtn[] = $row;
					}
				}
			}
		}
		return $rtn;
	}

	public function get_object( $sql ) {
		global $wpdb;

		$row = $wpdb->get_row( str_replace( '#__', $wpdb->prefix, $sql ), OBJECT );
		return $row;
	}

	public function get_arraylist( $sql, $key = null ) {
		global $wpdb;

		$rows = $wpdb->get_results( str_replace( '#__', $wpdb->prefix, $sql ), ARRAY_A );
		$rtn = array();
		if ( ! empty( $rows ) ) {
			if ( empty( $key ) ) {
				$rtn = $rows;
			} else {
				foreach ( $rows as $row ) {
					if ( isset( $row[ $key ] ) ) {
						$rtn[ $row[ $key ] ] = $row;
					} else {
						$rtn[] = $row;
					}
				}
			}
		}
		return $rtn;
	}

	public function get_value( $sql ) {
		global $wpdb;

		$row = $this->get_object( $sql );
		if ( empty( $row ) ) {
			return null;
		}

		foreach ( $row as $firstitem ) {
			return $firstitem;
		}
	}

	public function get_column( $sql ) {
		global $wpdb;

		$rows = $wpdb->get_results( str_replace( '#__', $wpdb->prefix, $sql ), ARRAY_A );
		$rtn = array();
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$row = (array) $row;
				$rtn[] = array_shift( $row );
			}
		}

		return $rtn;
	}

	public function query( $sql ) {
		global $wpdb;
		$affected_rows = $wpdb->query( str_replace( '#__', $wpdb->prefix, $sql ) );
		return $affected_rows;
	}

	public function escape( $s, $extra = false ) {
		$s = esc_sql( $s );
		if ( $extra ) {
			$s = addcslashes( $s, '%_' );
		}

		return $s;
	}

	public function get_table_columns( $table ) {
		$columns = new stdClass();

		$rows = $this->get_objectlist( 'DESCRIBE ' . $table );
		foreach ( $rows as $row ) {
			$val = strtolower( $row->Null ) == 'yes' ? null : $row->Default;
			$columns->{$row->Field} = $val;
		}

		return $columns;
	}

	public function get_insertid() {
		global $wpdb;
		return $wpdb->insert_id;
	}

	public function get_table_instance( $table, $key, $id ) {

		$o = $this->get_object( 'SELECT * FROM ' . $table . ' WHERE ' . $key . '="' . $this->escape( $id ) . '"' );
		if ( ! empty( $o ) ) {
			return (object) $o;
		}

		return $this->get_table_columns( $table );
	}

	public static function bind_table_instance( $prop, $from ) {
		$is_array = is_array( $from );
		$is_object = is_object( $from );
		if ( ! $is_array && ! $is_object ) {
			return false;
		}

		foreach ( $prop as $k => $v ) {
			if ( $is_array ) {
				if ( array_key_exists( $k, $from ) ) {
					// use this because isset returns false on NULL
					$prop->{$k} = $from[ $k ];
				}
			} elseif ( $is_object ) {
				if ( property_exists( $from,$k ) ) {
					// use this because isset returns false on NULL
					$prop->{$k} = $from->{$k};
				}
			}
		}
		return $prop;
	}

	public function save_table_instance( $table, $row, $extra = null ) {

		if ( empty( $row->id ) ) {
			$columns = array();
			$values = array();

			foreach ( $row as $c => $item ) {
				if ( 'id' == $c ) {
					continue;
				}
				$columns[] = $c;
				$values[] = is_null( $item ) ? 'NULL' : '"' . $this->escape( $item ) . '"';
			}
			$sql = 'INSERT INTO ' . $table . ' (' . implode( ',', $columns ) . ') VALUES (' . implode( ',', $values ) . ')';

			$this->query( $sql );
			$row->id = $this->get_insertid();
		} else {
			$cols = array();
			foreach ( $row as $c => $item ) {
				if ( 'id' == $c ) {
					continue;
				}
				$value = is_null( $item ) ? 'NULL' : '"' . $this->escape( $item ) . '"';
				$cols[] = $c . '=' . $value;
			}
			$sql = 'UPDATE ' . $table . ' SET ' . implode( ',', $cols ) . ' WHERE id=' . $row->id;
			$this->query( $sql );
		}
		return $row;
	}

}
