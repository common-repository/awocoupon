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

class AwoCoupon_Helper_Helper {

	public function vars( $type, $item = null, $excludes = null ) {
		$vars = array(
			'estore' => array(
				'woocommerce' => 'WooCommerce',
			),
			'function_type' => array(
				'coupon' => AC()->lang->__( 'Coupon' ),
				'shipping' => AC()->lang->__( 'Shipping' ),
			),
			'asset_mode' => array(
				'include' => AC()->lang->__( 'Include' ),
				'exclude' => AC()->lang->__( 'Exclude' ),
			),
			'asset_type' => array(
				'product' => AC()->lang->__( 'Product' ),
				'category' => AC()->lang->__( 'Category' ),
				//'manufacturer' => AC()->lang->__( 'Manufacturer' ),
				//'vendor' => AC()->lang->__( 'Vendor' ),
				'shipping' => AC()->lang->__( 'Shipping' ),
				'coupon' => AC()->lang->__( 'Coupon' ),
				'country' => AC()->lang->__( 'Country' ),
				'countrystate' => AC()->lang->__( 'State/Province' ),
				'paymentmethod' => AC()->lang->__( 'Payment Method' ),
				'user' => AC()->lang->__( 'Customer' ),
				'usergroup' => AC()->lang->__( 'User Group' ),
			),
			'buy_xy_process_type' => array(
				'first' => AC()->lang->__( 'First found match' ),
				'lowest' => AC()->lang->__( 'Lowest value' ),
				'highest' => AC()->lang->__( 'Highest value' ),
			),
			'published' => array(
				'1' => AC()->lang->__( 'Published' ),
				'-1' => AC()->lang->__( 'Unpublished' ),
			),
			'state' => array(
				'published' => AC()->lang->__( 'Published' ),
				'unpublished' => AC()->lang->__( 'Unpublished' ),
				'template' => AC()->lang->__( 'Template' ),
			),
			'coupon_value_type' => array(
				'percent' => AC()->lang->__( 'Percent' ),
				'amount' => AC()->lang->__( 'Amount' ),
				'amount_per' => AC()->lang->__( 'Amount per item' ),
			),
			'discount_type' => array(
				'overall' => AC()->lang->__( 'Overall' ),
				'specific' => AC()->lang->__( 'Specific' ),
			),
			'min_value_type' => array(
				'overall' => AC()->lang->__( 'Overall' ),
				'specific' => AC()->lang->__( 'Specific' ),
				'specific_notax' => AC()->lang->__( 'Specific no tax' ),
			),
			'min_qty_type' => array(
				'overall' => AC()->lang->__( 'Overall' ),
				'specific' => AC()->lang->__( 'Specific' ),
			),
			'num_of_uses_type' => array(
				'total' => AC()->lang->__( 'Total' ),
				'per_user' => AC()->lang->__( 'Per customer' ),
			),
			'expiration_type' => array(
				'day' => AC()->lang->__( 'Days' ),
				'month' => AC()->lang->__( 'Months' ),
				'year' => AC()->lang->__( 'Years' ),
			),
			'status' => array(
				'active' => AC()->lang->__( 'Active' ),
				'inactive' => AC()->lang->__( 'Inactive' ),
				'used' => AC()->lang->__( 'Used' ),
			),
		);

		if ( '__all__' == $type ) {
			return $vars;
		}

		if ( isset( $vars[ $type ] ) ) {
			if ( isset( $item ) ) {
				if ( isset( $vars[ $type ][ $item ] ) ) {
					return $vars[ $type ][ $item ];
				} else {
					return '';
				}
			} else {
				$return_obj = $vars[ $type ];
				if ( ! is_array( $excludes ) ) {
					$excludes = array( $excludes );
				}
				if ( ! empty( $excludes ) && is_array( $excludes ) ) {
					foreach ( $excludes as $exclude ) {
						if ( isset( $return_obj[ $exclude ] ) ) {
							unset( $return_obj[ $exclude ] );
						}
					}
				}
				return $return_obj;
			}
		}
	}

	public function get_userstate_request( $key, $request, $default = null ) {

		$cur_state = $this->get_session( 'userConfigSettings', $key, $default );
		$new_state = $this->get_request( $request, null );

		if ( null === $new_state ) {
			return $cur_state;
		}

		// Save the new value only if it was set in this request.
		$this->set_session( 'userConfigSettings', $key, $new_state );

		return $new_state;
	}

	public function get_request( $key = null, $default = '', $type = null ) {
		if ( is_null( $key ) ) {
			if ( empty( $type ) ) {
				$type = 'post';
			}
			if ( 'request' == $type ) {
				return $_REQUEST;
			} elseif ( 'post' == $type ) {
				return $_POST;
			} elseif ( 'get' == $type ) {
				return $_GET;
			} elseif ( 'file' == $type ) {
				return $_FILES;
			}
		} elseif ( strpos( $key, '.' ) === false ) {
			if ( empty( $type ) ) {
				$type = 'request';
			}

			if ( 'request' == $type ) {
				return isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : $default;
			} elseif ( 'post' == $type ) {
				return isset( $_POST[ $key ] ) ? $_POST[ $key ] : $default;
			} elseif ( 'get' == $type ) {
				return isset( $_GET[ $key ] ) ? $_GET[ $key ] : $default;
			} elseif ( 'file' == $type ) {
				return isset( $_FILES[ $key ] ) ? $_FILES[ $key ] : $default;
			}
		} else {
			if ( empty( $type ) ) {
				$type = 'request';
			}

			$pos = strrpos( $key, '.' );
			$part1 = substr( $key, 0, $pos );
			$part2 = substr( $key, $pos + 1 );
			if ( 'request' == $type ) {
				return isset( $_REQUEST[ $part1 ][ $part2 ] ) ? $_REQUEST[ $part1 ][ $part2 ] : $default;
			} elseif ( 'post' == $type ) {
				return isset( $_POST[ $part1 ][ $part2 ] ) ? $_POST[ $part1 ][ $part2 ] : $default;
			} elseif ( 'get' == $type ) {
				return isset( $_GET[ $part1 ][ $part2 ] ) ? $_GET[ $part1 ][ $part2 ] : $default;
			} elseif ( 'file' == $type ) {
				return isset( $_FILES[ $part1 ][ $part2 ] ) ? $_FILES[ $part1 ][ $part2 ] : $default;
			}
		}
		return $default;
	}

	public function scrubids( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = explode( ',', $ids );
		}
		$ids = array_map( 'intval', $ids );
		if ( empty( $ids ) ) {
			$ids = array( 0 );
		}
		return implode( ',', $ids );
	}

	public function redirect( $path ) {
		$separator = strpos( $path, '?' ) !== false ? '&' : '?';
		echo '
			<script>
			jQuery( document ).ready(function() {
				window.location.hash = "#/' . $path . $separator . 'cache=' . mt_rand() . '";
			});
			</script>
		';
		exit;
	}

	public function redirect_front( $url ) {
		wp_redirect( $url );
		exit;
	}

	public function route( $parts ) {

		if ( empty( $parts['type'] ) ) {
			return;
		}
		switch ( $parts['type'] ) {
			case 'admin':
				if ( empty( $parts['view'] ) ) {
					return;
				}

				$class = 'AwoCoupon_Admin_Controller_' . $parts['view'];
				$class = AC()->helper->new_class( $class );

				if ( ! empty( $parts['task'] ) ) {
					$function = 'do_' . $parts['task'];
					if ( method_exists( $class, $function ) ) {
						$class->$function();
					}
				}

				$layout = ! empty( $parts['layout'] ) ? $parts['layout'] : 'default';
				$function = 'show_' . $layout;
				$class->$function();

				return;

			case 'public':
				if ( empty( $parts['view'] ) ) {
					return;
				}

				$class = 'AwoCoupon_Public_Controller_' . $parts['view'];
				$class = AC()->helper->new_class( $class );

				if ( ! empty( $parts['task'] ) ) {
					$function = 'do_' . $parts['task'];
					if ( method_exists( $class, $function ) ) {
						$class->$function();
					}
				}

				$layout = ! empty( $parts['layout'] ) ? $parts['layout'] : 'default';
				$function = 'show_' . $layout;
				$class->$function();

				return;
		}
	}

	public function render_layout( $layout_file, $data = null ) {

		// Check possible overrides, and build the full path to layout file
		$path = AWOCOUPON_DIR . '/layout';
		$tmp = explode( '.', $layout_file );
		foreach ( $tmp as $tmp2 ) {
			$path .= '/' . $tmp2;
		}
		$path .= '.php';

		// Nothing to show
		if ( ! file_exists( $path ) ) {
			return '';
		}

		ob_start();
		include $path;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public function add_class( $class ) {
		if ( class_exists( $class ) ) {
			return $class;
		}
		if ( strtolower( substr( $class, 0, 9 ) ) != 'awocoupon' ) {
			return false;
		}

		if ( strpos( $class, '_' ) !== false ) {
			$pieces = explode( '_' , $class );
			array_shift( $pieces );
		} else {
			$pieces = preg_split( '/(?=[A-Z])/', trim( substr( $class, 9 ) ), -1, PREG_SPLIT_NO_EMPTY );
		}
		if ( empty( $pieces ) ) {
			return false;
		}
		$classname = 'Awocoupon_' . implode( '_', $pieces );
		if ( class_exists( $classname ) ) {
			return $classname;
		}

		$pieces = array_map( 'strtolower', $pieces ); //lowercase items in array
		$filename = array_pop( $pieces );
		$filename = 'class-awocoupon-' . implode( '-', $pieces ) . '-' . $filename . '.php';

		$path = AWOCOUPON_DIR . '/' . implode( '/', $pieces ) . '/' . $filename;
		if ( ! file_exists( $path ) ) {
			return false;
		}

		require $path;
		return $classname;
	}

	public function new_class( $class ) {
		if ( class_exists( $class ) ) {
			return new $class();
		}

		$class_name = $this->add_class( $class );
		return false !== $class_name ? new $class_name() : null;
	}

	public function set_message( $msg, $type = 'notice' ) {
		$messages = $this->get_session( 'admin', 'messages', array() );
		if ( ! isset( $messages[ $type ] ) ) {
			$messages[ $type ] = array();
		}
		$messages[ $type ][] = $msg;
		$this->set_session( 'admin', 'messages', $messages );
	}

	public function get_messages_and_flush() {
		$messages = $this->get_session( 'admin', 'messages', array() );
		if ( empty( $messages ) ) {
			return '';
		}
		$this->reset_session( 'admin', 'messages' );
		return $messages;
	}

	public function is_email( $email ) {
		return is_email( $email ) === false ? false : true;
	}

	public function get_user( $id = null ) {
		$userclass = is_null( $id ) ? wp_get_current_user() : get_user_by( 'id', $id );
		if ( ! isset( $userclass->data ) ) {
			return;
		}
		$user = $userclass->data;
		if ( is_null( $id ) && empty( $user->ID ) ) {
			$user = new stdClass();
			$user->id = 0;
			$user->username = '';
			$user->email = '';
			$user->name = '';
			return $user;
		}

		$user->id = $user->ID;
		$user->username = $user->user_login;
		$user->email = $user->user_email;
		$user->name = $user->display_name;

		return $user;
	}

	/*
	$type = utc2loc (utc to localtime)
	$type = utc2utc (utc to utc)
	$type = loc2utc (localtime to utc)
	*/
	public function get_date( $date = null, $format = null, $type = 'utc2loc' ) {

		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}
		elseif ( $format == 'date' ) {
			$format = get_option( 'date_format' );
		}
		elseif ( $format == 'datetime' ) {
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		}

		if ( in_array( $type, array( 'utc2loc', 'utc2utc' ) ) ) {
			if ( 'utc2loc' == $type ) {
				$gmt = false;
			} elseif ( 'utc2utc' == $type ) {
				$gmt = true;
			}

			if ( empty( $date ) || 'now' == $date ) {
				$date = false;
			} elseif ( ! is_numeric( $date ) ) {
				$date = strtotime( $date . ' UTC' );
			}

			return date_i18n( $format, $date, $gmt );
		} elseif ( 'loc2utc' == $type ) {
			if ( empty( $date ) || 'now' == $date ) {
				$date = false;
			} elseif ( ! is_numeric( $date ) ) {
				$date = strtotime( $date . ' UTC' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			}

			return date_i18n( $format, $date, true );
		}
	}

	public function fixpaths_relative_to_absolute( $str ) {

		$site_url = AC()->store->getsiteurl();
		$site_url = rtrim( $site_url, '/' ) . '/';

		// image path
		$str = preg_replace( '/src=\"(?!cid)(?!http).*/Uis', 'src="' . $site_url, $str );
		$str = str_replace( 'url(components', 'url(' . $site_url . 'components', $str );

		// links
		$str = str_replace( 'href="..', 'href="', $str );
		$str = preg_replace( '/href=\"(?!cid)(?!http).*/Uis', 'href="' . $site_url, $str );

		return $str;
	}

	public function get_link() {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}

	public function get_home_link() {
		return home_url();
	}

	public function cms_redirect( $url ) {
		wp_redirect( $url );
		exit;
	}

	/**
	 * Convert line for csv
	 *
	 * @param array   $fields fieldlist.
	 * @param string  $delimiter comma or semi-colon.
	 * @param string  $enclosure generally quote.
	 * @param boolean $mysql_null allow nulls.
	 **/
	public function fputcsv2( array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false ) {
		$delimiter_esc = preg_quote( $delimiter, '/' );
		$enclosure_esc = preg_quote( $enclosure, '/' );

		$output = array();
		foreach ( $fields as $field ) {
			if ( null === $field && $mysql_null ) {
				$output[] = 'NULL';
				continue;
			}

			$output[] = preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ? (
				$enclosure . str_replace( $enclosure, $enclosure . $enclosure, $field ) . $enclosure
			) : $field;
		}

		return join( $delimiter, $output ) . "\n";
	}

	public function get_editor( $content, $id, $settings = array() ) {
		ob_start();
		wp_editor( $content, $id, $settings );
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}

	public function get_session( $group, $key, $default ) {
		return AC()->store->get_session( $group, $key, $default );
	}

	public function set_session( $group, $key, $value ) {
		return AC()->store->set_session( $group, $key, $value );
	}

	public function reset_session( $group, $key ) {
		return AC()->store->reset_session( $group, $key );
	}

}

if ( ! function_exists( 'AC' ) ) {
	function AC() {
		return AwoCoupon::instance();
	}
}

if ( ! function_exists( 'printr' ) ) {
	function printr( $a ) {
		echo '<pre>' . print_r( $a, 1 ) . '</pre>';
	}
}

if ( ! function_exists( 'printrx' ) ) {
	function printrx( $a ) {
		echo '<pre>' . print_r( $a, 1 ) . '</pre>';
		exit;
	}
}

if ( ! function_exists( 'awotrace' ) ) {
	function awotrace() {
		ob_start();
		debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$rtn = ob_get_contents();
		ob_end_clean();
		return $rtn;

		//$data = debug_backtrace();
		//$rtn = array();
		//foreach($data as $r) $rtn[] = @$r['file'].':'.@$r['line'].' function '.@$r['function'];
		//return array_reverse($rtn);
	}
}

if ( ! function_exists( 'curl_setopt_array' ) ) {
	function curl_setopt_array( &$ch, $curl_options ) {
		foreach ( $curl_options as $option => $value ) {
			if ( ! curl_setopt( $ch, $option, $value ) ) {
				return false;
			}
		}
		return true;
	}
}

