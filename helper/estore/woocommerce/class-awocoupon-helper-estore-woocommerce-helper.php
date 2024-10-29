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

class Awocoupon_Helper_Estore_Woocommerce_Helper {

	var $estore = 'woocommerce';

	public function get_coupon_asset( $coupon ) {

		$coupon_ids = 0;
		$param_list = array();
		if ( is_object( $coupon ) && ! empty( $coupon->id ) ) {
			$coupon_ids = (int) $coupon->id;
			$param_list[ $coupon->id ] = ! empty( $coupon->params->asset ) ? json_decode( json_encode( $coupon->params->asset ), true ) : array();
		} elseif ( is_array( $coupon ) ) {
			$coupon_ids = AC()->helper->scrubids( $coupon );
			$tmp = AC()->db->get_objectlist( 'SELECT id,params FROM #__awocoupon WHERE id IN (' . $coupon_ids . ')' );
			foreach ( $tmp as $row ) {
				$param_list[ $row->id ] = array();
				if ( empty( $row->params ) ) {
					continue;
				}
				$param = json_decode( $row->params, true );
				if ( empty( $param['asset'] ) ) {
					continue;
				}
				$param_list[ $row->id ] = $param['asset'];
			}
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$is_group = is_plugin_active( 'groups/groups.php' ) ? true : false;
		$is_bkinggroup = false;
		if ( $is_group !== true ) {
			$is_bkinggroup = is_plugin_active( 'b2bking-wholesale-for-woocommerce/b2bking.php' ) ? true : false;
		}

		include_once( AWOCOUPON_DIR . '/../woocommerce/includes/class-wc-countries.php' ); // Defines countries and states
		$countryclass = new WC_Countries(); // Countries class

		$sql = 'SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.post_title USING utf8) AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				  JOIN #__posts b ON b.ID=a.asset_id AND b.post_type="product"
				 WHERE a.asset_type="product" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.name USING utf8) AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				  JOIN #__terms b ON b.term_id=a.asset_id
				  JOIN #__term_taxonomy c ON c.term_id=b.term_id AND c.taxonomy="product_cat"
				 WHERE a.asset_type="category" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,"" AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				 WHERE a.asset_type="shipping" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.coupon_code USING utf8) AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				  JOIN #__awocoupon b ON b.id=a.asset_id
				 WHERE a.asset_type="coupon" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.display_name USING utf8) AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				  JOIN #__users b ON b.id=a.asset_id
				 WHERE a.asset_type="user" AND a.coupon_id IN (' . $coupon_ids . ')
			' . ( $is_group ? '
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.name USING utf8) AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				  JOIN #__groups_group b ON b.group_id=a.asset_id
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ')
			' : '' ) . '
			' . ( $is_bkinggroup ? '
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.post_title USING utf8) AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				  JOIN #__posts b ON b.ID=a.asset_id AND b.post_type="b2bking_group"
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ')
			' : '' ) . '
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,"" AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				 WHERE a.asset_type="paymentmethod" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,"" AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				 WHERE a.asset_type="country" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,"" AS asset_name,a.order_by
				  FROM #__awocoupon_asset a
				 WHERE a.asset_type="countrystate" AND a.coupon_id IN (' . $coupon_ids . ')
				 
				 ORDER BY  ' . ( ! empty( $order_by ) ? $order_by : 'order_by,asset_name,asset_id' ) . '
		';
		$items = AC()->db->get_objectlist( $sql );

		$asset = array();
		foreach ( $items as $k => $row ) {
			$params = $param_list[ $row->coupon_id ];
			if ( 'shipping' == $row->asset_type ) {
				if ( ! isset( $shippinglist ) ) {
					$shippinglist = $this->get_shippings();
				}
				if ( isset( $shippinglist[ $row->asset_id ] ) ) {
					$items[ $k ]->asset_name = $shippinglist[ $row->asset_id ]->label;
				}
			} elseif ( 'paymentmethod' == $row->asset_type ) {
				if ( ! isset( $paymentmethodlist ) ) {
					$paymentmethodlist = $this->get_paymentmethods();
				}
				if ( isset( $paymentmethodlist[ $row->asset_id ] ) ) {
					$items[ $k ]->asset_name = $paymentmethodlist[ $row->asset_id ]->name;
				}
			} elseif ( 'country' == $row->asset_type ) {
				if ( ! isset( $countrylist ) ) {
					$countrylist = $countryclass->get_countries();
				}
				if ( isset( $countrylist[ $row->asset_id ] ) ) {
					$items[ $k ]->asset_name = $countrylist[ $row->asset_id ];
				}
			} elseif ( 'countrystate' == $row->asset_type ) {
				if ( ! isset( $countrylist ) ) {
					$countrylist = $countryclass->get_countries();
				}
				if ( ! isset( $countrystatelist ) ) {
					$countrystatelist = $countryclass->get_states();
				}

				list( $ckey, $skey ) = explode( '-', $row->asset_id );
				if ( ! isset( $countrylist[ $ckey ] ) ) {
					unset( $items[ $k ] );
				} else {
					if ( isset( $countrystatelist[ $ckey ] ) && isset( $countrystatelist[ $ckey ][ $skey ] ) ) {
						$items[ $k ]->asset_name = $countrystatelist[ $ckey ][ $skey ];
					}
				}
			}

			$key = (int) $row->asset_key;
			if ( ! isset( $asset[ $row->coupon_id ][ $key ] ) ) {
				$asset[ $row->coupon_id ][ $key ] = new stdClass();
				if ( isset( $params[ $key ]['qty'] ) ) {
					$asset[ $row->coupon_id ][ $key ]->qty = $params[ $key ]['qty'];
				}
				$asset[ $row->coupon_id ][ $key ]->rows = new stdClass();
			}
			if ( ! isset( $asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type} ) ) {
				$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type} = new stdClass();
				$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->type = $row->asset_type;
				if ( isset( $params[ $key ]['rows'][ $row->asset_type ]['mode'] ) ) {
					$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->mode = $params[ $key ]['rows'][ $row->asset_type ]['mode'];
				}
				if ( isset( $params[ $key ]['rows'][ $row->asset_type ]['qty'] ) ) {
					$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->qty = $params[ $key ]['rows'][ $row->asset_type ]['qty'];
				}
			}
			if ( 'countrystate' == $row->asset_type ) {
				if ( ! isset( $asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->country ) ) {
					$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->country = array();
				}
				$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->country[] = substr( $row->asset_id, 0, strpos( $row->asset_id, '-' ) );
			}
			$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->rows[ $row->asset_id ] = $row;
		}

		return is_object( $coupon ) && ! empty( $coupon->id ) ? ( isset( $asset[ $coupon->id ] ) ? $asset[ $coupon->id ] : array() ) : $asset;
	}

	public function get_products( $product_id = null, $search = null, $limit = null, $is_published = true, $limitstart = null, $orderby = null, $orderbydir = null ) {
		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
					p.ID AS id,CONCAT(p.post_title," (",p.ID,")") AS label,p.ID as sku,p.post_title AS product_name
				  FROM #__posts p
				 WHERE p.post_type="product"
				 ' . ( $is_published ? ' AND p.post_status="publish" ' : '' ) . '
				 ' . ( ! empty( $product_id ) ? ' AND p.ID IN (' . AC()->helper->scrubids( $product_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(p.post_title," (",p.ID,")") LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,sku' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_categorys( $category_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		if ( empty( $category_id ) && empty( $search ) && empty( $limit ) ) {
			return self::category_tree();
		}

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS t.term_id AS id, t.name AS label
				  FROM #__terms t
				  JOIN #__term_taxonomy tx ON tx.term_id=t.term_id
				 WHERE tx.taxonomy="product_cat"
				 ' . ( ! empty( $category_id ) ? ' AND t.term_id IN (' . AC()->helper->scrubids( $category_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND t.name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 't.name,t.term_id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	private function category_tree( $selected_categories = array(), $cid = 0, $level = 0, $disabled_fields = array() ) {
		static $category_tree_output = array();

		$cid = (int) $cid;

		$level++;

		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT t.term_id AS category_id,t.name AS category_name, t.term_id AS category_child_id, tx.parent AS category_parent_id
				  FROM #__terms t
				  JOIN #__term_taxonomy tx ON tx.term_id=t.term_id
				 WHERE tx.taxonomy="product_cat" AND tx.parent=' . (int) $cid;
		$records = AC()->db->get_objectlist( $sql );

		$selected = '';
		if ( ! empty( $records ) ) {
			foreach ( $records as $key => $category ) {
				if ( empty( $category->category_child_id ) ) {
					continue;//$category->category_child_id = $category->category_id;
				}

				$child_id = $category->category_child_id;

				if ( $child_id != $cid ) {
					if ( in_array( $child_id, $selected_categories ) ) {
						$selected = 'selected=\"selected\"';
					} else {
						$selected = '';
					}

					$category_tree_output[ $child_id ] = (object) array(
						'category_id' => $child_id,
						'category_name' => $category->category_name,
						'id' => $child_id,
						'label' => str_repeat( '---', ( $level - 1 ) ) . $category->category_name,
					);
				}

				$test = (int) AC()->db->get_value( 'SELECT term_id FROM #__term_taxonomy WHERE taxonomy="product_cat" AND parent=' . (int) $child_id );
				if ( ! empty( $test ) ) {
					self::category_tree( $selected_categories, $child_id, $level, $disabled_fields );
				}
			}
		}

		return $category_tree_output;
	}

	public function get_manufacturers( $manu_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		return array();
	}

	public function get_vendors( $vendor_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		return array();
	}

	public function get_shippings( $shipping_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$shipping_types = AC()->db->get_objectlist( 'SELECT DISTINCT method_id FROM #__woocommerce_shipping_zone_methods WHERE is_enabled=1' );

		$shipping_methods = array();
		foreach ( $shipping_types as $type ) {
			$tmp = AC()->db->get_objectlist( 'SELECT option_name,option_value FROM #__options WHERE option_name LIKE "woocommerce_' . $type->method_id . '_%_settings"' );
			foreach ( $tmp as $shipping ) {
				$unpack = unserialize( $shipping->option_value );
				$shipping->id = $shipping->option_name;
				$shipping->title = $unpack['title'];
				$shipping_methods[] = $shipping;
			}
		}

		if ( ! empty( $shipping_methods ) ) {
			//$select_title = 'CASE s.instance_id';
			$select_title = 'CASE CONCAT("woocommerce_",s.method_id,"_",s.instance_id,"_settings")';
			foreach ( $shipping_methods as $shipping ) {
				$select_title .= ' WHEN "' . $shipping->id . '" THEN "' . AC()->db->escape( $shipping->title ) . '" ';
			}
			$select_title .= ' ELSE "' . AC()->lang->__( 'Unknown' ) . '" END ';
		} else {
			$select_title = ' "' . AC()->lang->__( 'Unknown' ) . '" ';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS s.instance_id AS id, CONCAT(IFNULL(z.zone_name,"' . AC()->db->escape( AC()->lang->__( 'Rest of the World' ) ) . '")," - ", ' . $select_title . ') AS label
				  FROM #__woocommerce_shipping_zone_methods s
				  LEFT JOIN #__woocommerce_shipping_zones z ON z.zone_id=s.zone_id
				 WHERE s.is_enabled=1
				 ' . ( ! empty( $shipping_id ) ? ' AND s.instance_id IN (' . AC()->helper->scrubids( $shipping_id ) . ') ' : '' ) . '
				HAVING 1=1 ' . ( ! empty( $search ) ? ' AND label LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		$shippings = AC()->db->get_objectlist( $sql, 'id' );
		foreach ( $shippings as $k => $shipping ) {
			$shippings[ $k ]->name = $shipping->label;
		}
		return $shippings;
	}

	public function get_groups( $shoppergroup_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		$is_group = is_plugin_active( 'groups/groups.php' ) ? true : false;
		$is_bkinggroup = false;
		if ( $is_group !== true ) {
			$is_bkinggroup = is_plugin_active( 'b2bking-wholesale-for-woocommerce/b2bking.php' ) ? true : false;
		}
		if ( $is_group !== true && $is_bkinggroup !== true ) {
			return null;
		}

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		if ( $is_group ) {
			$sql = 'SELECT SQL_CALC_FOUND_ROWS group_id AS id,name AS label,name 
					  FROM #__groups_group
					 WHERE 1=1
					 ' . ( ! empty( $shoppergroup_id ) ? ' AND group_id IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
					 ' . ( ! empty( $search ) ? ' AND name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
					 ORDER BY ' . ( empty( $orderby ) ? 'name,group_id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
					 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );
		}
		elseif( $is_bkinggroup ) {
			$sql = 'SELECT SQL_CALC_FOUND_ROWS ID AS id,post_title AS label,post_title AS name 
					  FROM #__posts
					 WHERE post_type="b2bking_group" AND post_status="publish"
					 ' . ( ! empty( $shoppergroup_id ) ? ' AND ID IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
					 ' . ( ! empty( $search ) ? ' AND post_title LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
					 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
					 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );
		}
		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_group_ids( $user_id ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$is_group = is_plugin_active( 'groups/groups.php' ) ? true : false;
		$is_bkinggroup = false;
		if ( $is_group !== true ) {
			$is_bkinggroup = is_plugin_active( 'b2bking-wholesale-for-woocommerce/b2bking.php' ) ? true : false;
		}
		if ( $is_group !== true && $is_bkinggroup !== true ) {
			return null;
		}

		if ( $is_group ) {
			return AC()->db->get_column( 'SELECT group_id FROM #__groups_user_group WHERE user_id=' . (int) $user_id );
		}
		elseif( $is_bkinggroup ) {
			return AC()->db->get_column( 'SELECT meta_value FROM #__usermeta WHERE user_id=' . (int) $user_id . ' AND meta_key="b2bking_customergroup"' );
		}
	}

	public function get_users( $user_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
						u.id,CONCAT(u.user_login," - ",u.display_name) as label,
						u.user_login AS username,
						IF(ul.meta_value IS NULL,
								TRIM(SUBSTRING(TRIM(u.display_name),LENGTH(TRIM(u.display_name))-LOCATE(" ",REVERSE(TRIM(u.display_name)))+1)),
								ul.meta_value) as lastname,
						IF(uf.meta_value IS NULL,
								TRIM(REVERSE(SUBSTRING(REVERSE(TRIM(u.display_name)),LOCATE(" ",REVERSE(TRIM(u.display_name)))+1))),
								uf.meta_value) as firstname
				  FROM #__users u
				  LEFT JOIN #__usermeta uf ON uf.user_id=u.id AND uf.meta_key="first_name"
				  LEFT JOIN #__usermeta ul ON ul.user_id=u.id AND ul.meta_key="last_name"
				 WHERE 1=1
				 ' . ( ! empty( $user_id ) ? ' AND u.id IN (' . AC()->helper->scrubids( $user_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(u.user_login," - ",u.display_name) LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . '
				 GROUP BY u.id
				 ORDER BY ' . ( empty( $orderby ) ? 'label,u.id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrys() {

		include_once( AWOCOUPON_DIR . '/../woocommerce/includes/class-wc-countries.php' ); // Defines countries and states
		$class = new WC_Countries(); // Countries class
		$countries = $class->get_countries();

		$name = 'country_name';
		$id = 'country_id';
		$countries_list = array();
		foreach ( $countries as  $key => $value ) {
			$countries_list[ $key ] = new stdClass();
			$countries_list[ $key ]->id = $key;
			$countries_list[ $key ]->{$id} = $key;
			$countries_list[ $key ]->{$name} = $value;
		}

		return $countries_list;
	}

	public function get_countrystates( $country_id = null ) {
		include_once( AWOCOUPON_DIR . '/../woocommerce/includes/class-wc-countries.php' ); // Defines countries and states
		$class = new WC_Countries(); // Countries class
		$states = $class->get_states( $country_id );

		$state_list = array();
		if ( ! empty( $states ) ) {
			if ( ! empty( $country_id ) ) {
				foreach ( $states as $k => $state ) {
					$id = $country_id . '-' . $k;
					$state_list[ $id ] = (object) array(
						'id' => $id,
						'label' => $state,
					);
				}
			} else {
				foreach ( $states as $ckey => $t1 ) {
					if ( ! empty( $t1 ) ) {
						foreach ( $t1 as $skey => $state ) {
							$id = $ckey . '-' . $skey;
							$state_list[ $id ] = (object) array(
								'id' => $id,
								'label' => $state,
							);
						}
					}
				}
			}
		}
		return $state_list;
	}

	public function get_paymentmethods() {

		include_once( AWOCOUPON_DIR . '/../woocommerce/includes/class-wc-payment-gateways.php' ); // Defines countries and states
		$class = new WC_Payment_Gateways(); // Countries class
		$payments = $class->payment_gateways();

		foreach ( $payments as $k => $payment ) {
			$payments[ $k ]->name = $payment->title;
		}
		return $payments;
	}

	public function get_order( $order_id ) {
		if ( version_compare( WC_VERSION, '8', '>=' ) ) {
			return AC()->db->get_object('
				SELECT p.*,p.ID AS order_id,p.ID AS order_number, o.total_amount AS order_total,o.currency AS order_currency
				  FROM #__posts p
				  LEFT JOIN #__wc_orders o ON o.id=p.ID
				 WHERE p.ID=' . (int) $order_id . '
			');
		}
		else {
			return AC()->db->get_object('
				SELECT p.*,p.ID AS order_id,p.ID AS order_number, total.meta_value AS order_total,pcn.meta_value AS order_currency
				  FROM #__posts p
				  LEFT JOIN #__postmeta total ON total.post_id=p.ID AND total.meta_key="_order_total"
				  LEFT JOIN #__postmeta pcn ON pcn.post_id=p.ID AND pcn.meta_key="_order_currency"
				 WHERE p.ID=' . (int) $order_id . '
			');
		}
	}

	public function get_order_link( $order_id ) {
		return AC()->post_url() . '/wp-admin/post.php?post=' . (int) $order_id . '&action=edit';
	}

	public function sql_history_coupon( $where, $having, $orderby ) {

		if ( version_compare( WC_VERSION, '8', '>=' ) ) {
			$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
						 c.min_value,c.discount_type,c.function_type,c.startdate,c.expiration,c.state,
						 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
						 uu.id as use_id,b.first_name,b.last_name,uu.user_id,
						 (uu.coupon_discount+uu.shipping_discount) AS discount,uu.productids,uu.timestamp,uu.user_email,
						 o.id AS order_id,o.id AS order_number,
						 u.user_login as _username, b.first_name AS _fname, b.last_name AS _lname,o.date_created_gmt AS _created_on
					 FROM #__awocoupon_history uu
					 JOIN #__awocoupon c ON c.id=uu.coupon_id
					 LEFT JOIN #__awocoupon c2 ON c2.id=uu.coupon_entered_id
					 LEFT JOIN #__awocoupon_tag t ON t.coupon_id=c.id
					 LEFT JOIN #__wc_orders o ON o.id=uu.order_id
					 LEFT JOIN #__wc_order_addresses b ON b.order_id=o.id AND b.address_type="billing"
					 LEFT JOIN #__users u ON u.ID=uu.user_id
					WHERE uu.estore="' . $this->estore . '" AND uu.session_id IS NULL
					' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
					GROUP BY uu.id
					' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
					' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
					';
		}
		else {
			$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
						 c.min_value,c.discount_type,c.function_type,c.startdate,c.expiration,c.state,
						 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
						 uu.id as use_id,pfn.meta_value AS first_name,pln.meta_value AS last_name,uu.user_id,
						 (uu.coupon_discount+uu.shipping_discount) AS discount,uu.productids,uu.timestamp,uu.user_email,
						 p.ID AS order_id,p.ID AS order_number,
						 u.user_login as _username, pfn.meta_value AS _fname, pln.meta_value AS _lname,p.post_date AS _created_on
					 FROM #__awocoupon_history uu
					 JOIN #__awocoupon c ON c.id=uu.coupon_id
					 LEFT JOIN #__awocoupon c2 ON c2.id=uu.coupon_entered_id
					 LEFT JOIN #__awocoupon_tag t ON t.coupon_id=c.id
					 LEFT JOIN #__posts p ON p.ID=uu.order_id AND p.post_type IN ( "shop_order", "shop_order_placehold" )
					 LEFT JOIN #__postmeta pfn ON pfn.post_id=uu.order_id AND pfn.meta_key="_billing_first_name"
					 LEFT JOIN #__postmeta pln ON pln.post_id=uu.order_id AND pln.meta_key="_billing_last_name"
					 LEFT JOIN #__users u ON u.ID=uu.user_id
					WHERE uu.estore="' . $this->estore . '" AND uu.session_id IS NULL
					' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
					GROUP BY uu.id
					' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
					' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
					';
		}

		return $sql;
	}

	public function get_order_status() {
		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			require AWOCOUPON_DIR . '/../woocommerce/includes/wc-order-functions.php';
		}
		$order_statuses = wc_get_order_statuses();

		$items = array();
		foreach ( $order_statuses as $id => $name ) {
			$tmp = new stdclass();
			$tmp->order_status_id = $id;
			$tmp->order_status_code = $id;
			$tmp->order_status_name = $name;
			$items[] = $tmp;
		}
		return $items;
	}

	public function get_name() {
		return AC()->db->get_value( 'SELECT option_value FROM #__options WHERE option_name LIKE "blogname"' );
	}

	public function get_email() {
		return AC()->db->get_value( 'SELECT option_value FROM #__options WHERE option_name LIKE "woocommerce_email_from_address"' );
	}

	public function getsiteurl() {
		return get_site_url();
	}

	public function get_itemsperpage() {
		return apply_filters( 'woocommerce_api_keys_settings_items_per_page', 10 );
	}

	/**
	 * Get session
	 *
	 * @param string $group group.
	 * @param string $key key.
	 * @param mixed  $default return if nothing else.
	 **/
	public function get_session( $group, $key, $default ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$result = activate_plugin( 'woocommerce/woocommerce.php' );
			if ( is_wp_error( $result ) ) {
				return;
			}
		}
		$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
		if ( is_null( WC()->session ) || ! WC()->session instanceof $session_class ) {
			$session = new $session_class();
			$session->init();
		}
		else {
			$session = WC()->session;
		}
		return $session->get( 'awocoupon_' . $group . '_' . $key, $default );
	}

	/**
	 * Set session
	 *
	 * @param string $group group.
	 * @param string $key key.
	 * @param mixed  $value val.
	 **/
	public function set_session( $group, $key, $value ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$result = activate_plugin( 'woocommerce/woocommerce.php' );
			if ( is_wp_error( $result ) ) {
				return;
			}
		}
		$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
		if ( is_null( WC()->session ) || ! WC()->session instanceof $session_class ) {
			$session = new $session_class();
			$session->init();
		}
		else {
			$session = WC()->session;
		}
		return $session->set( 'awocoupon_' . $group . '_' . $key, $value );
	}

	/**
	 * Reset session
	 *
	 * @param string $group group.
	 * @param string $key key.
	 **/
	public function reset_session( $group, $key ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$result = activate_plugin( 'woocommerce/woocommerce.php' );
			if ( is_wp_error( $result ) ) {
				return;
			}
		}
		$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
		if ( is_null( WC()->session ) || ! WC()->session instanceof $session_class ) {
			$session = new $session_class();
			$session->init();
		}
		else {
			$session = WC()->session;
		}
		unset( $session->{ 'awocoupon_' . $group . '_' . $key } );
	}

}

