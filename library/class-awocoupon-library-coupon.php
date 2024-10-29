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
class AwoCoupon_Library_Coupon {

	protected static $_instance = null;

	var $reprocess = false;
	var $error_msgs = array();
	var $enqueue_error_msgs = false;

	protected static function instance( $class = null ) {
		if ( is_null( self::$_instance ) ) {
			if ( class_exists( $class ) ) {
				self::$_instance = new $class;
			} else {
				throw new Exception( 'Cannot instantiate undefined class [' . $class . ']', 1 );
			}
		}
		return self::$_instance;
	}

	/**
	 * Construct
	 */
	public function __construct() {
		$this->params = AC()->param;

		$this->coupon_discount_before_tax = 1 == $this->params->get( 'enable_coupon_discount_before_tax', 0 ) ? 1 : 0;
		$this->allow_zero_value = 1 == $this->params->get( 'enable_zero_value_coupon', 0 ) ? 1 : 0;
	}

	// function to process a coupon_code entered by a user
	protected function process_autocoupon_helper() {

		$db = AC()->db;

		// if cart is the same, do not reproccess coupon
		$autosess = $this->get_coupon_auto();
		if ( ! empty( $autosess ) ) {
			if ( ! empty( $autosess->uniquecartstring ) && $autosess->uniquecartstring == $this->getuniquecartstringauto() ) {
				if ( empty( $awosess ) ) {
					$this->finalize_autocoupon( $autosess->coupons );
				}
				return $autosess->coupons;
			}
		}

		$this->initialize_coupon_auto();

		// check coupons
		$auto_coupon_code = array();
		$multiple_coupon_max_auto = (int) $this->params->get( 'multiple_coupon_max_auto', 100 );
		$current_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );
		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,c.min_value,c.discount_type,
					c.function_type,c.coupon_value_def,c.params,1 as isauto,note,c.state
				  FROM #__awocoupon c
				  JOIN #__awocoupon_auto a ON a.coupon_id=c.id
				 WHERE c.estore="' . $this->estore . '" AND c.state="published" AND a.published=1
				   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
				 ORDER BY a.ordering';
		$coupon_rows = $db->get_objectlist( $sql );
		if ( empty( $coupon_rows ) ) {
			return false;
		}

		// retreive cart items
		$this->define_cart_items();
		if ( empty( $this->cart->items ) ) {
			return false;
		}

		foreach ( $coupon_rows as $coupon_row ) {

			if ( empty( $coupon_row ) ) {
				// no record, so coupon_code entered was not valid
				continue;
			}

			$r_err = $this->couponvalidate_daily_time_limit( $coupon_row );
			if ( ! empty( $r_err ) ) {
				continue;
			}

			// coupon returned
			$this->coupon_row = $coupon_row;

			$return = $this->checkdiscount( $coupon_row, false );
			if ( ! empty( $return ) && $return['redeemed'] ) {
				$auto_coupon_code[] = $coupon_row;
				if ( count( $auto_coupon_code ) >= $multiple_coupon_max_auto ) {
					break;
				}
			}
		}

		$this->set_coupon_auto( $auto_coupon_code );
		if ( ! empty( $auto_coupon_code ) ) {
			$this->finalize_autocoupon( $auto_coupon_code );
			return $auto_coupon_code;
		}

		return;
	}

	protected function process_coupon_helper() {
		$this->error_msgs = array();
		$output = $this->start_processing_coupon();
		if ( $this->enqueue_error_msgs && ! empty( $this->error_msgs ) ) {
			foreach ( $this->error_msgs as $err ) {
				AC()->helper->set_message( $err, 'error' );
			}
		}
		return $output;
	}

	private function start_processing_coupon() {
		if ( ! $this->cart_object_is_initialized() ) {
			return;
		}

		$user = AC()->helper->get_user();
		$db = AC()->db;
		$submitted_coupon_code = trim( $this->get_submittedcoupon() );

		// if cart is the same, do not reproccess coupon
		$awosess = $this->session_get( 'coupon' );
		if ( ! empty( $awosess ) ) {
			if (
				(
					( ! empty( $submitted_coupon_code ) && false !== strpos( ';' . $awosess->coupon_code_internal . ';', ';' . $submitted_coupon_code . ';' ) )
							||
					empty( $submitted_coupon_code )
				)
				&& $awosess->uniquecartstring == $this->getuniquecartstring( $awosess->coupon_code_internal, false )
			) {
				$this->finalize_coupon_store( $awosess );
				return true;
			}
		}

		//------START STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
		if ( empty( $awosess ) ) {
			if ( 1 == $this->params->get( 'enable_store_coupon', 0 ) ) {
				$tmp = $db->get_value( 'SELECT id FROM #__awocoupon WHERE estore="' . $this->estore . '" AND coupon_code="' . $db->escape( $submitted_coupon_code ) . '"' );
				if ( empty( $tmp ) && $this->is_coupon_in_store( $submitted_coupon_code ) ) {
					$this->continue_execution = true;
					return null;
				}
			}
		}
		//------END STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------

		$is_casesensitive = AC()->coupon->is_case_sensitive();

		$coupon_awo_entered_coupon_ids = array();
		$multiple_coupons['auto'] = array();
		$multiple_coupons['coupon'] = array();
		$coupon_session = $this->session_get( 'coupon' );
		if ( ! empty( $coupon_session ) ) {
			if ( ! empty( $coupon_session->processed_coupons ) ) {
				foreach ( $coupon_session->processed_coupons as $k => $r ) {
					if ( $r->isauto ) {
						continue;
					}
					$coupon_awo_entered_coupon_ids[] = $r->coupon_code;
					$multiple_coupons['coupon'][] = $r->coupon_code;
				}
			}
		}
		if ( ! empty( $submitted_coupon_code ) ) {
			$submited_multiple_coupons = explode( ';', $submitted_coupon_code );
			foreach ( $submited_multiple_coupons as $___s_coupon ) {
				$___s_coupon = trim( $___s_coupon );
				$coupon_awo_entered_coupon_ids[] = $db->escape( $___s_coupon );
			}
		}
		$coupon_awo_entered_coupon_ids = $is_casesensitive ? array_unique( $coupon_awo_entered_coupon_ids ) : $this->array_iunique( $coupon_awo_entered_coupon_ids );

		$this->initialize_coupon();

		$auto_codes = $this->get_coupon_auto();
		$auto_codes = isset( $auto_codes->coupons ) ? $auto_codes->coupons : array();
		if ( empty( $coupon_awo_entered_coupon_ids ) && empty( $auto_codes ) ) {
			if ( ! empty( $submitted_coupon_code ) ) {
				return $this->return_false( 'errNoRecord' );
			}
			return;
		}
		if ( empty( $auto_codes ) ) {
			$auto_codes = array();
		}
		if ( ! empty( $auto_codes ) ) {
			$reverse_auto_codes = array_reverse( $auto_codes );
			foreach ( $reverse_auto_codes as $auto_code ) {
				if ( $this->is_coupon_in_array( $is_casesensitive, $auto_code->coupon_code, $multiple_coupons['coupon'] ) ) {
					$key = array_search( $auto_code->coupon_code, $multiple_coupons['coupon'] );
					if ( false !== $key ) {
						unset( $multiple_coupons['coupon'][ $key ] );
					}
				}
				if ( $this->is_coupon_in_array( $is_casesensitive, $db->escape( $auto_code->coupon_code ), $coupon_awo_entered_coupon_ids ) ) {
					$key = array_search( $db->escape( $auto_code->coupon_code ), $coupon_awo_entered_coupon_ids );
					if ( false !== $key ) {
						unset( $coupon_awo_entered_coupon_ids[ $key ] );
					}
				}

				$multiple_coupons['auto'][] = $auto_code->coupon_code;
				$coupon_awo_entered_coupon_ids[] = $db->escape( $auto_code->coupon_code );
			}
		}
		$coupon_awo_entered_coupon_ids = $is_casesensitive ? array_unique( $coupon_awo_entered_coupon_ids ) : $this->array_iunique( $coupon_awo_entered_coupon_ids );

		if ( 0 == $this->params->get( 'enable_multiple_coupon', 0 ) ) {
			// remove all auto codes
			$last_auto_code = '';
			foreach ( $auto_codes as $auto_code ) {
				if ( $this->is_coupon_in_array( $is_casesensitive, $db->escape( $auto_code->coupon_code ), $coupon_awo_entered_coupon_ids ) ) {
					$key = array_search( $db->escape( $auto_code->coupon_code ), $coupon_awo_entered_coupon_ids );
					if ( false !== $key ) {
						unset( $coupon_awo_entered_coupon_ids[ $key ] );
						$last_auto_code = $auto_code->coupon_code;
					}
				}
			}

			// get the last item in the coupon array
			$coupon_awo_entered_coupon_ids = array( array_pop( $coupon_awo_entered_coupon_ids ) );

			// add the last auto code back
			$coupon_awo_entered_coupon_ids[] = $last_auto_code;
		} else {
			// remove coupons is maximums are set

			if ( ! empty( $coupon_awo_entered_coupon_ids ) ) {

				$multiple_coupon_max_auto = (int) $this->params->get( 'multiple_coupon_max_auto', 0 );
				$multiple_coupon_max_coupon = (int) $this->params->get( 'multiple_coupon_max_coupon', 0 );
				if ( $multiple_coupon_max_auto > 0 || $multiple_coupon_max_coupon > 0 ) {
					if ( ! empty( $submitted_coupon_code ) ) {
						$submitted_not_in_coupons = $this->array_intersect_diff( 'diff', $is_casesensitive, $submited_multiple_coupons, $multiple_coupons['coupon'] );
						if ( ! empty( $submitted_not_in_coupons ) ) {
							// now add submitted coupon(s) not on any list to either automatic, or coupon array
							foreach ( $submitted_not_in_coupons as $current_coupon_not_in_coupons ) {
								$check_if_auto = false;
								foreach ( $auto_codes as $auto_code ) {
									if (
										( $is_casesensitive && trim( $auto_code->coupon_code ) == $current_coupon_not_in_coupons )
									|| ( ! $is_casesensitive && strtolower( trim( $auto_code->coupon_code ) ) == strtolower( $current_coupon_not_in_coupons ) )
									) {
										$check_if_auto = true;
										$multiple_coupons['auto'][] = $auto_code->coupon_code;
										break;
									}
								}
								if ( ! $check_if_auto ) {
									$multiple_coupons['coupon'][] = $current_coupon_not_in_coupons;
								}
							}
						}
					}

					if ( $multiple_coupon_max_auto > 0 && count( $multiple_coupons['auto'] ) > 1 ) {
						$multiple_coupons['auto'] = $is_casesensitive ? array_unique( $multiple_coupons['auto'] ) : $this->array_iunique( $multiple_coupons['auto'] );
						if ( count( $multiple_coupons['auto'] ) > $multiple_coupon_max_auto ) {
							$removecoupons = array_slice( $multiple_coupons['auto'], 0, count( $multiple_coupons['auto'] ) - $multiple_coupon_max_auto );
							if ( ! empty( $removecoupons ) ) {
								foreach ( $removecoupons as $r ) {
									$key = array_search( $r, $coupon_awo_entered_coupon_ids );
									if ( false !== $key ) {
										unset( $coupon_awo_entered_coupon_ids[ $key ] );
									}
								}
							}
						}
					}
					if ( $multiple_coupon_max_coupon > 0 && count( $multiple_coupons['coupon'] ) > 1 ) {
						$multiple_coupons['coupon'] = $is_casesensitive ? array_unique( $multiple_coupons['coupon'] ) : $this->array_iunique( $multiple_coupons['coupon'] );
						if ( count( $multiple_coupons['coupon'] ) > $multiple_coupon_max_coupon ) {
							$removecoupons = array_slice( $multiple_coupons['coupon'], 0, count( $multiple_coupons['coupon'] ) - $multiple_coupon_max_coupon );
							if ( ! empty( $removecoupons ) ) {
								foreach ( $removecoupons as $r ) {
									$key = array_search( $r, $coupon_awo_entered_coupon_ids );
									if ( false !== $key ) {
										unset( $coupon_awo_entered_coupon_ids[ $key ] );
									}
								}
							}
						}
					}
				}

				$multiple_coupon_max = (int) $this->params->get( 'multiple_coupon_max', 0 );
				if ( $multiple_coupon_max > 0 && count( $coupon_awo_entered_coupon_ids ) > $multiple_coupon_max ) {
					$coupon_awo_entered_coupon_ids = array_slice( $coupon_awo_entered_coupon_ids, count( $coupon_awo_entered_coupon_ids ) - $multiple_coupon_max );
				}
			}
		}

		// check coupons
		$master_output = array();
		$coupon_rows = array();
		$current_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );
		if ( 1 == $this->params->get( 'is_space_insensitive', 0 ) ) {
			foreach ( $coupon_awo_entered_coupon_ids as $k => $i ) {
				$coupon_awo_entered_coupon_ids[ $k ] = str_replace( ' ', '', $i );
			}
		}
		$coupon_codes = implode( '","', $coupon_awo_entered_coupon_ids );
		if ( ! empty( $coupon_codes ) ) {
			$sql = 'SELECT id,coupon_code,num_of_uses_total,num_of_uses_customer,coupon_value_type,coupon_value,min_value,discount_type,
						function_type,coupon_value_def,params,note,state
					  FROM #__awocoupon 
					 WHERE estore="' . $this->estore . '"
					   AND state="published"
					   AND ( ((startdate IS NULL OR startdate="")   AND (expiration IS NULL OR expiration="")) OR
							 ((expiration IS NULL OR expiration="") AND startdate<="' . $current_date . '") OR
							 ((startdate IS NULL OR startdate="")   AND expiration>="' . $current_date . '") OR
							 (startdate<="' . $current_date . '"    AND expiration>="' . $current_date . '")
						   )
					   AND ' . ( 1 == $this->params->get( 'is_space_insensitive', 0 ) ? 'REPLACE(coupon_code," ","")' : 'coupon_code' ) . ' IN ("' . $coupon_codes . '")
					  ORDER BY FIELD(coupon_code, "' . $coupon_codes . '")';
			$coupon_rows = $db->get_objectlist( $sql, 'id' );
		}

		if ( ! empty( $auto_codes ) ) {
			$valid_auto_codes = array();
			foreach ( $auto_codes as $auto_code ) {
				if ( isset( $coupon_rows[ $auto_code->id ] ) ) {
					$valid_auto_codes[] = $auto_code;
					unset( $coupon_rows[ $auto_code->id ] );
				}
			}
			$valid_auto_codes = array_reverse( $valid_auto_codes );
			foreach ( $valid_auto_codes as $auto_code ) {
				$tmp_array = array(
					$auto_code->id => $auto_code,
				);
				$coupon_rows = $tmp_array + $coupon_rows;  // need to preserve coupon_id as the key
			}
		}

		if ( ! empty( $submitted_coupon_code ) ) {
			$is_found = false;
			foreach ( $submited_multiple_coupons as $_current_submitted_coupon ) {
				foreach ( $coupon_rows as $tmp ) {
					$test_db_code = 1 == $this->params->get( 'is_space_insensitive', 0 ) ? str_replace( ' ', '', $tmp->coupon_code ) : trim( $tmp->coupon_code );
					$test_enter_code = 1 == $this->params->get( 'is_space_insensitive', 0 ) ? str_replace( ' ', '', $_current_submitted_coupon ) : $_current_submitted_coupon;
					if (
						( $is_casesensitive && $test_db_code == $test_enter_code )
						|| ( ! $is_casesensitive && strtolower( $test_db_code ) == strtolower( $test_enter_code ) )
					) {
						$is_found = true;
						break 2;
					}
				}
			}
			if ( ! $is_found ) {
				$this->coupon_row = new stdclass();
				$this->coupon_row->id = -1;
				$this->coupon_row->coupon_code = $submitted_coupon_code;
				$this->coupon_row->function_type = 'coupon';
				$this->coupon_row->isauto = in_array( $submitted_coupon_code, $multiple_coupons['auto'] ) ? true : false;
				$this->return_false( 'errNoRecord' );
			}
		}

		if ( empty( $coupon_rows ) ) {
			return false; //$this->return_false('errNoRecord');
		}

		// get tags
		$tmp = $db->get_objectlist( 'SELECT coupon_id,tag FROM #__awocoupon_tag WHERE coupon_id IN (' . implode( ',', array_keys( $coupon_rows ) ) . ') AND tag LIKE "{_%}"' );
		foreach ( $tmp as $tmp_item ) {
			preg_match( '/{(.*):(.*)}/i', $tmp_item->tag, $match );
			if ( ! empty( $match[1] ) ) {
				$coupon_rows[ $tmp_item->coupon_id ]->tags[ $match[1] ] = $match[2];
			} else {
				$key = trim( $tmp_item->tag, '{}' );
				if ( ! empty( $key ) ) {
					$coupon_rows[ $tmp_item->coupon_id ]->tags[ $key ] = 1;
				}
			}
		}

		// update params
		foreach ( $coupon_rows as $k => $coupon_row ) {
				$coupon_rows[ $k ]->params = ! empty( $coupon_row->params ) ? ( is_string( $coupon_row->params ) ? json_decode( $coupon_row->params ) : $coupon_row->params ) : new stdclass();
		}

		// check for coupon exclusivity
		foreach ( $coupon_rows as $coupon_row ) {
			if ( ! empty( $coupon_row->params->exclusive ) && 1 == $coupon_row->params->exclusive ) {
				// drop all other coupons and only use this one
				$coupon_rows = array();
				$coupon_rows[ $coupon_row->id ] = $coupon_row;
				break;
			}
		}

		// retreive cart items
		$this->define_cart_items();
		if ( empty( $this->cart->items ) ) {
			$this->initialize_coupon();
			$this->return_false( 'errDiscountedExclude' );
			return false;
		}

		foreach ( $coupon_rows as $coupon_row ) {

			if ( empty( $coupon_row ) ) {
				// no record, so coupon_code entered was not valid
				continue;
			}

			$r_err = $this->couponvalidate_daily_time_limit( $coupon_row );
			if ( ! empty( $r_err ) ) {
				$this->return_false( $r_err );
				continue;
			}

			// coupon returned
			$this->coupon_row = $coupon_row;

			$return = $this->checkdiscount( $coupon_row, true );
			if ( ! empty( $return ) && $return['redeemed'] ) {
				$master_output[ $coupon_row->id ] = array( $coupon_row, $return );
			}
		}

		if ( $this->finalize_coupon( $master_output ) ) {
			return true;
		}

		$this->coupon_row = null;
		$this->initialize_coupon();
		return false;
	}

	protected function checkdiscount( $coupon_row, $track_product_price = false ) {
		$user = AC()->helper->get_user();

		if ( empty( $coupon_row ) ) {
			return;
		}
		if ( empty( $this->cart->items ) ) {
			return;
		}
		if ( empty( $this->cart->items_def ) ) {
			return;
		}

		$coupon_row->params = ! empty( $coupon_row->params ) ? ( is_string( $coupon_row->params ) ? json_decode( $coupon_row->params ) : $coupon_row->params ) : new stdclass();
		$coupon_row->asset = AC()->store->get_coupon_asset( $coupon_row );

		$coupon_row->cart_items = $this->cart->items;
		$coupon_row->cart_items_breakdown = $this->cart->items_breakdown;
		$coupon_row->cart_items_def = $this->cart->items_def;
		$coupon_row->cart_shipping = $this->cart->shipping;
		$coupon_row->cart_payment = $this->cart->payment;

		if ( 1 == (int) $this->params->get( 'multiple_coupon_product_discount_limit', 0 ) ) {
			# stop product from being discounted more than once in the cart
			foreach ( $coupon_row->cart_items_breakdown as $k => $a ) {
				if ( ! empty( $a['totaldiscount'] ) ) {
					unset( $coupon_row->cart_items_breakdown[ $k ] );
				}
			}
			if ( empty( $coupon_row->cart_items_breakdown ) ) {
				return;
			}
		}

		$coupon_row->is_discount_before_tax = $this->coupon_discount_before_tax;
		if ( ! empty( $coupon_row->tags['discount_before_tax'] ) && 1 == $coupon_row->tags['discount_before_tax'] ) {
			$coupon_row->is_discount_before_tax = 1;
		} elseif ( ! empty( $coupon_row->tags['discount_after_tax'] ) && 1 == $coupon_row->tags['discount_after_tax'] ) {
			$coupon_row->is_discount_before_tax = 0;
		} elseif ( ! empty( $coupon_row->note ) ) {
			$match = array();
			preg_match( '/{discount_before_tax:\s*(1|0)\s*}/i', $coupon_row->note, $match );
			if ( isset( $match[1] ) ) {
				$coupon_row->is_discount_before_tax = $match[1];
			}
		}

		$coupon_row->customer = new stdClass();
		$coupon_row->customer->user_id = (int) $user->id;

		$coupon_row->specific_min_value = 0;
		$coupon_row->specific_min_value_notax = 0;
		$coupon_row->specific_min_qty = 0;

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------

		// check plugins
		//JPluginHelper::importPlugin('awocoupon');
		//$dispatcher = JDispatcher::getInstance();
		//$plgValues = $dispatcher->trigger('awoValidateCouponCode', array(& $coupon_row, $this));
		//if(!empty($plgValues)){ foreach ($plgValues as $plgValue) { if (!empty($plgValue)) return $this->return_false($plgValue); } }

		if ( in_array( $coupon_row->function_type, array( 'coupon', 'shipping' ) ) ) {

			// verify total is up to the minimum value for the coupon
			if ( ! empty( $coupon_row->min_value ) && round( $this->product_total, 4 ) < $coupon_row->min_value ) {
				return $this->return_false( 'errMinVal' );
			}
			if ( ! empty( $coupon_row->params->min_qty ) && $this->product_qty < $coupon_row->params->min_qty ) {
				return $this->return_false( 'errMinQty' );
			}

			$r_err = $this->couponvalidate_user( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			$r_err = $this->couponvalidate_usergroup( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// country state check
			$r_err = $this->couponvalidate_country( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			$r_err = $this->couponvalidate_countrystate( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// payment method check
			$r_err = $this->couponvalidate_paymentmethod( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// number of use check
			$r_err = $this->couponvalidate_numuses( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// check for specials
			$r_err = $this->couponvalidate_product_special( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// check for discounted products
			$r_err = $this->couponvalidate_product_discounted( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			switch ( $coupon_row->function_type ) {
				case 'coupon':
					return $this->checkdiscount_coupon( $coupon_row, $track_product_price );
				case 'shipping':
					return $this->checkdiscount_shipping( $coupon_row, $track_product_price );
			}
		}

		return $this->return_false( 'invalid function type' );
	}

	private function checkdiscount_coupon( $coupon_row, $track_product_price = false ) {

		$_discount_product = 0;
		$_discount_product_notax = 0;
		$_discount_product_tax = 0;

		$_discount_shipping = 0;
		$_discount_shipping_notax = 0;
		$_discount_shipping_tax = 0;

		$usedproductids = array();

		if ( empty( $coupon_row->function_type ) ) {
			return;
		}
		if ( 'coupon' != $coupon_row->function_type ) {
			return;
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------

		// check specific to function type
		$r_err = $this->couponvalidate_asset_producttype( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}
		$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		// for zero value coupons
		$coupon_row->coupon_value = (double) $coupon_row->coupon_value;
		if ( empty( $coupon_row->coupon_value ) && empty( $coupon_row->coupon_value_def ) ) {
			return $this->get_processed_discount_array( $coupon_row );
		}

		// ----------------------------------------------------
		// Compute Coupon Discount based on coupon parameters
		// ----------------------------------------------------

		if ( ! empty( $coupon_row->coupon_value ) ) {
			// product/category discount
			$total = 0;
			$total_notax = 0;
			$qty = 0;
			$valid_items = array();
			foreach ( $coupon_row->cart_items_breakdown as $product_id => $row ) {
				if ( ! $this->is_product_eligible( $row['product_id'], $coupon_row ) ) {
					continue;
				}
				$usedproductids[] = $product_id;
				$qty++;
				if ( $row['product_price'] <= 0 ) {
					continue;
				}
				$total += $row['product_price'];
				$total_notax += $row['product_price_notax'];

				$valid_items[] = array(
					'key' => $row['key'],
					'product_id' => $row['product_id'],
					'product_price' => $row['product_price'],
					'product_price_notax' => $row['product_price_notax'],
				);
			}

			if ( ! empty( $total ) ) {
				$_discount_product = $coupon_row->coupon_value;
				$_discount_product_notax = $coupon_row->coupon_value;
				if ( 'percent' == $coupon_row->coupon_value_type ) {
					$_discount_product = round( $total * $_discount_product / 100, 4 );
					$_discount_product_notax = round( $total_notax * $_discount_product_notax / 100, 4 );
				} else {
					if ( 'amount_per' == $coupon_row->coupon_value_type ) {
						$_discount_product = 0;
						$_discount_product_notax = 0;
						$postfix = $coupon_row->is_discount_before_tax ? '_notax' : '';
						foreach ( $valid_items as $valid_item ) {
							$current_value = min( $coupon_row->coupon_value, $valid_item[ 'product_price' . $postfix ] );
							if ( $current_value <= 0 ) {
								continue;
							}
							$_discount_product += $current_value;
							$_discount_product_notax += $current_value;
						}
					}
					if ( $coupon_row->is_discount_before_tax ) {
						$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
					} else {
						$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
					}
				}

				$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

				if ( $total < $_discount_product ) {
					$_discount_product = (float) $total;
				}
				if ( $total_notax < $_discount_product_notax ) {
					$_discount_product_notax = (float) $total_notax;
				}

				$this->realtotal_verify( $_discount_product, $_discount_product_notax );

				if ( $coupon_row->is_discount_before_tax ) {
					$_discount_product_tax = $_discount_product - $_discount_product_notax;
				}

				//track product discount
				$this->cartitem_update( array(
					'track_product_price' => $track_product_price,
					'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
					'coupon_row' => $coupon_row,
					'coupon_percent' => $coupon_row->coupon_value,
					'discount_value' => $_discount_product,
					'discount_value_notax' => $_discount_product_notax,
					'qty' => $qty,
					'valid_items' => $valid_items,
					'usedproductids' => $usedproductids,
				) );
			} elseif ( $qty > 0 && 1 == $this->allow_zero_value ) {
				return $this->get_processed_discount_array( $coupon_row, $usedproductids );
			}
		} elseif ( empty( $coupon_row->coupon_value )
			&& ! empty( $coupon_row->coupon_value_def )
			&& preg_match( '/^(\d+\-\d+([.]\d+)?;)+(\[[_a-z]+\=[a-z]+(\&[_a-z]+\=[a-z]+)*\])?$/', $coupon_row->coupon_value_def ) ) {
			// cumulative coupon calculation
			$vdef_table = array();
			$vdef_options = array();
			$each_row = explode( ';', $coupon_row->coupon_value_def );

			//options
			$tmp = end( $each_row );
			if ( '[' == substr( $tmp, 0, 1 ) ) {
				parse_str( trim( $tmp, '[]' ), $vdef_options );
				array_pop( $each_row );
			}
			reset( $each_row );

			foreach ( $each_row as $row ) {
				if ( false !== strpos( $row, '-' ) ) {
					list( $p, $v ) = explode( '-', $row );
					$vdef_table[ $p ] = $v;
				}
			}
			$min_qty = 0;
			$max_qty = 0;
			if ( ! empty( $vdef_table ) ) {
				if ( sizeof( $vdef_table ) > 1 ) {
					ksort( $vdef_table, SORT_NUMERIC );
					$tmp_table = $vdef_table;

					// test for min qty
					reset( $tmp_table );
					$tmp = current( $tmp_table );
					if ( empty( $tmp ) ) {
						$min_qty = key( $tmp_table ) + 1;
					}
					// test for max qty
					$tmp = end( $tmp_table ); // last element in array
					if ( empty( $tmp ) ) {
						$max_qty = key( $tmp_table ) - 1; // last key in array - 1
					}
				}

				$curr_qty = 0;
				$qty = 0;
				$total = 0;
				$total_notax = 0;
				$qty_distinct = array();
				$valid_items = array();

				$cart_items = $coupon_row->cart_items_breakdown;
				// reorder items in cart if needed
				if ( ! empty( $vdef_options['order'] ) ) {
					if ( 'first' == $vdef_options['order'] ) {
					} else {
						$cart_items = array();
						$item_index = array();
						foreach ( $coupon_row->cart_items_breakdown as $key => $row ) {
							$item_index[ $key ] = $row['product_price'];
						}
						if ( 'lowest' == $vdef_options['order'] ) {
							asort( $item_index, SORT_NUMERIC );
						} elseif ( 'highest' == $vdef_options['order'] ) {
							arsort( $item_index, SORT_NUMERIC );
						}

						foreach ( $item_index as $key => $price ) {
							$cart_items[] = $coupon_row->cart_items_breakdown[ $key ];
						}
					}
				}
				if ( empty( $vdef_options['type'] ) || 'progressive' == $vdef_options['type'] ) {
					foreach ( $cart_items as $row ) {
						if ( empty( $row['product_price'] ) ) {
							continue;
						}
						if ( ! $this->is_product_eligible( $row['product_id'], $coupon_row ) ) {
							continue;
						}
						$curr_qty++;
						$qty++;
						if ( ! isset( $qty_distinct[ $row['product_id'] ] ) ) {
							$qty_distinct[ $row['product_id'] ] = 0;
						}
						$qty_distinct[ $row['product_id'] ]++;
						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' == $vdef_options['qty_type'] ) {
							$curr_qty = count( $qty_distinct );
						}
						if ( ! empty( $min_qty ) && $curr_qty < $min_qty ) {
							continue;
						}
						if ( ! empty( $max_qty ) && $curr_qty > $max_qty ) {
							continue;
						}

						$usedproductids[] = $row['product_id'];
						$total += $row['product_price'];
						$total_notax += $row['product_price_notax'];
						$valid_items[] = array(
							'key' => $row['key'],
							'product_id' => $row['product_id'],
							'product_price' => $row['product_price'],
							'product_price_notax' => $row['product_price_notax'],
						);
					}

					if ( ! empty( $qty ) ) {

						if ( ! empty( $max_qty ) ) {
							array_pop( $vdef_table );
						}
						krsort( $vdef_table, SORT_NUMERIC );
						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' == $vdef_options['qty_type'] ) {
							$qty = count( $qty_distinct );
						}

						foreach ( $vdef_table as $pcount => $val ) {
							if ( $pcount <= $qty ) {
								$coupon_value = $val;
								break;
							}
						}
						if ( ! empty( $coupon_value ) ) {

							if ( ! empty( $total ) ) {
								$_discount_product = $coupon_value;
								$_discount_product_notax = $coupon_value;

								if ( 'percent' == $coupon_row->coupon_value_type ) {
									$_discount_product = round( $total * $_discount_product / 100, 4 );
									$_discount_product_notax = round( $total_notax * $_discount_product_notax / 100, 4 );
								} else {
									if ( 'amount_per' == $coupon_row->coupon_value_type ) {
										$_discount_product = 0;
										$_discount_product_notax = 0;
										$postfix = $coupon_row->is_discount_before_tax ? '_notax' : '';
										foreach ( $valid_items as $valid_item ) {
											$current_value = min( $coupon_value, $valid_item[ 'product_price' . $postfix ] );
											if ( $current_value <= 0 ) {
												continue;
											}
											$_discount_product += $current_value;
											$_discount_product_notax += $current_value;
										}
									}
									if ( $coupon_row->is_discount_before_tax ) {
										$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
									} else {
										$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
									}
								}

								$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

								if ( $total < $_discount_product ) {
									$_discount_product = (float) $total;
								}
								if ( $total_notax < $_discount_product_notax ) {
									$_discount_product_notax = (float) $total_notax;
								}
								$this->realtotal_verify( $_discount_product, $_discount_product_notax );

								if ( $coupon_row->is_discount_before_tax ) {
									$_discount_product_tax = $_discount_product - $_discount_product_notax;
								}

								//track product discount
								$this->cartitem_update( array(
									'track_product_price' => $track_product_price,
									'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
									'coupon_row' => $coupon_row,
									'coupon_percent' => $coupon_value,
									'discount_value' => $_discount_product,
									'discount_value_notax' => $_discount_product_notax,
									'qty' => $qty,
									'valid_items' => $valid_items,
									'usedproductids' => $usedproductids,
								) );
							} elseif ( 1 == $this->allow_zero_value ) {
								return $this->get_processed_discount_array( $coupon_row, $usedproductids );
							}
						} else {
							// cumulative coupon, threshold not reached
							return $this->return_false( 'errProgressiveThreshold' );
						}
					}
				} elseif ( 'step' == $vdef_options['type'] ) {
					$_mapstep = array();

					$the_keys = array_keys( $vdef_table );
					foreach ( $vdef_table as $pcount => $val ) {
						if ( empty( $val ) ) {
							continue;
						}
						$_mapstep[ $pcount ] = $val;

						$j = array_search( $pcount, $the_keys );
						if ( ! isset( $the_keys[ $j + 1 ] ) ) {
							continue;
						}
						$forward = $the_keys[ $j + 1 ];
						for ( $k = $pcount + 1; $k < $the_keys[ $j + 1 ]; $k++ ) {
							$_mapstep[ $k ] = 'percent' == $coupon_row->coupon_value_type || 'amount_per' == $coupon_row->coupon_value_type ? $val : 0;
						}
					}
					if ( empty( $min_qty ) ) {
						$min_qty = min( array_keys( $_mapstep ) );
					}
					$value = 0;
					$value_notax = 0;
					foreach ( $cart_items as $row ) {
						if ( empty( $row['product_price'] ) ) {
							continue;
						}
						if ( ! $this->is_product_eligible( $row['product_id'], $coupon_row ) ) {
							continue;
						}
						$curr_qty++;
						$qty++;

						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' == $vdef_options['qty_type'] && isset( $qty_distinct[ $row['product_id'] ] ) ) {
							continue;
						}
						if ( ! isset( $qty_distinct[ $row['product_id'] ] ) ) {
							$qty_distinct[ $row['product_id'] ] = 0;
						}
						$qty_distinct[ $row['product_id'] ]++;
						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' == $vdef_options['qty_type'] ) {
							$curr_qty = count( $qty_distinct );
						}
						if ( ! empty( $min_qty ) && $curr_qty < $min_qty ) {
							continue;
						}
						if ( ! empty( $max_qty ) && $curr_qty > $max_qty ) {
							continue;
						}
						$usedproductids[] = $row['product_id'];
						$total += $row['product_price'];
						$total_notax += $row['product_price_notax'];

						$valtouse = isset( $_mapstep[ $curr_qty ] ) ? $_mapstep[ $curr_qty ] : end( $_mapstep );
						if ( 'percent' == $coupon_row->coupon_value_type ) {
							$value += round( $row['product_price'] * $valtouse / 100, 4 );
							$value_notax += round( $row['product_price_notax'] * $valtouse / 100, 4 );
						} else {
							$value += min( $valtouse, $coupon_row->is_discount_before_tax ? $row['product_price_notax'] : $row['product_price'] );
							$value_notax += min( $valtouse, $coupon_row->is_discount_before_tax ? $row['product_price_notax'] : $row['product_price'] );
						}
						$valid_items[] = array(
							'key' => $row['key'],
							'product_id' => $row['product_id'],
							'product_price' => $row['product_price'],
							'product_price_notax' => $row['product_price_notax'],
						);
					}

					if ( ! empty( $value ) ) {

						$_discount_product = $value;
						$_discount_product_notax = $value_notax;
						if ( 'percent' != $coupon_row->coupon_value_type ) {
							if ( $coupon_row->is_discount_before_tax ) {
								$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
							} else {
								$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
							}
						}

						$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

						if ( $total < $_discount_product ) {
							$_discount_product = (float) $total;
						}
						if ( $total_notax < $_discount_product_notax ) {
							$_discount_product_notax = (float) $total_notax;
						}

						$this->realtotal_verify( $_discount_product, $_discount_product_notax );

						if ( $coupon_row->is_discount_before_tax ) {
							$_discount_product_tax = $_discount_product - $_discount_product_notax;
						}

						//track product discount
						$this->cartitem_update( array(
							'track_product_price' => $track_product_price,
							'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
							'coupon_row' => $coupon_row,
							'coupon_percent' => null,
							'discount_value' => $_discount_product,
							'discount_value_notax' => $_discount_product_notax,
							'qty' => $qty,
							'valid_items' => $valid_items,
							'usedproductids' => $usedproductids,
						) );
					} else {
						// cumulative coupon, threshold not reached
						return $this->return_false( 'errProgressiveThreshold' );
					}
				}
			}
		}

		if ( ! empty( $_discount_product ) || ! empty( $_discount_shipping ) ) {
			return array(
				'redeemed' => true,
				'coupon_id' => $coupon_row->id,
				'coupon_code' => $coupon_row->coupon_code,
				'product_discount' => $_discount_product,
				'product_discount_notax' => $_discount_product_notax,
				'product_discount_tax' => $_discount_product_tax,
				'shipping_discount' => $_discount_shipping,
				'shipping_discount_notax' => $_discount_shipping_notax,
				'shipping_discount_tax' => $_discount_shipping_tax,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'usedproducts' => ! empty( $usedproductids ) ? implode( ',', $usedproductids ) : '',
			);
		}
	}

	private function checkdiscount_shipping( $coupon_row, $track_product_price = false ) {

		$_discount_product = 0;
		$_discount_product_notax = 0;
		$_discount_product_tax = 0;

		$_discount_shipping = 0;
		$_discount_shipping_notax = 0;
		$_discount_shipping_tax = 0;

		$usedproductids = array();

		if ( empty( $coupon_row->function_type ) ) {
			return;
		}
		if ( 'shipping' != $coupon_row->function_type ) {
			return;
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------
		// check specific to function type
		$r_err = $this->couponvalidate_asset_producttype( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		if ( ! $this->get_storeshipping_isdefaultbypass( $coupon_row->id ) ) {

			$shipping_id = $coupon_row->cart_shipping->shipping_id;
			if ( empty( $shipping_id ) ) {
				$ret = $this->get_processed_discount_array( $coupon_row );
				$ret['force_add'] = 1;
				return $ret;
			}

			// verify the shipping is on the list for this coupon
			$r_err = $this->couponvalidate_shipping( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}
		}

		$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		// for zero value coupons
		$coupon_row->coupon_value = (double) $coupon_row->coupon_value;
		if ( empty( $coupon_row->coupon_value ) && empty( $coupon_row->coupon_value_def ) ) {
			return $this->get_processed_discount_array( $coupon_row );
		}

		// ----------------------------------------------------
		// Compute Coupon Discount based on coupon parameters
		// ----------------------------------------------------

		$total = 0;
		$total_notax = 0;
		$qty = 0;
		foreach ( $coupon_row->cart_shipping->shippings as $k => $row ) {
			if ( $row->total <= 0 ) {
				continue;
			}
			if ( ! empty( $coupon_row->asset[0]->rows->shipping->rows ) ) {
				$mode = empty( $coupon_row->asset[0]->rows->shipping->mode ) ? 'include' : $coupon_row->asset[0]->rows->shipping->mode;
				if ( ( 'include' == $mode && ! empty( $coupon_row->asset[0]->rows->shipping->rows[ $row->shipping_id ] ) )
				|| ( 'exclude' == $mode && empty( $coupon_row->asset[0]->rows->shipping->rows[ $row->shipping_id ] ) )
				) {
				} else {
					unset( $coupon_row->cart_shipping->shippings[ $k ] );
					continue;
				}
			}

			$total += (float) $row->total;
			$total_notax += (float) $row->total_notax;
		}

		if ( ! empty( $total ) ) {
			$coupon_value = $coupon_row->coupon_value;
			$_discount_shipping = $coupon_row->coupon_value;
			$_discount_shipping_notax = $coupon_row->coupon_value;
			if ( 'percent' == $coupon_row->coupon_value_type ) {
				$_discount_shipping = round( $total * $_discount_shipping / 100, 4 );
				$_discount_shipping_notax = round( $total_notax * $_discount_shipping_notax / 100, 4 );
			} else {
				if ( $coupon_row->is_discount_before_tax ) {
					$_discount_shipping *= 1 + ( ( $total - $total_notax ) / $total_notax );
				} else {
					$_discount_shipping_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
				}
			}

			$this->get_max_discount_amount( $coupon_row, $_discount_shipping_notax, $_discount_shipping );

			if ( $total < $_discount_shipping ) {
				$_discount_shipping = (float) $total;
			}
			if ( $total_notax < $_discount_shipping_notax ) {
				$_discount_shipping_notax = (float) $total_notax;
			}

			if ( $coupon_row->is_discount_before_tax ) {
				$_discount_shipping_tax = $_discount_shipping - $_discount_shipping_notax;
			}

			//track shipping discount
			$this->cartitem_update( array(
				'track_product_price' => $track_product_price,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'coupon_row' => $coupon_row,
				'coupon_percent' => null,
				'discount_value' => null,
				'discount_value_notax' => null,
				'shipping_discount_value' => $_discount_shipping,
				'shipping_discount_value_notax' => $_discount_shipping_notax,
				'qty' => null,
				'valid_ships' => $coupon_row->cart_shipping->shippings,
				'usedproductids' => null,
			) );
		} elseif ( 1 == $this->allow_zero_value ) {
			return $this->get_processed_discount_array( $coupon_row, null );
		}

		if ( ! empty( $_discount_product ) || ! empty( $_discount_shipping ) ) {
			return array(
				'redeemed' => true,
				'coupon_id' => $coupon_row->id,
				'coupon_code' => $coupon_row->coupon_code,
				'product_discount' => $_discount_product,
				'product_discount_notax' => $_discount_product_notax,
				'product_discount_tax' => $_discount_product_tax,
				'shipping_discount' => $_discount_shipping,
				'shipping_discount_notax' => $_discount_shipping_notax,
				'shipping_discount_tax' => $_discount_shipping_tax,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'usedproducts' => ! empty( $usedproductids ) ? implode( ',', $usedproductids ) : '',
			);
		}
	}

	protected function define_cart_items() {
		if ( empty( $this->cart->items ) ) {
			return false;
		}

		$this->cart->items_breakdown = array();
		$index_breakdown = 0;
		foreach ( $this->cart->items as $k => $r ) {
			$this->cart->items[ $k ]['orig_product_price'] = $this->cart->items[ $k ]['product_price'];
			$this->cart->items[ $k ]['orig_product_price_notax'] = $this->cart->items[ $k ]['product_price_notax'];
			$this->cart->items[ $k ]['orig_product_price_tax'] = $this->cart->items[ $k ]['product_price_tax'];
			$this->cart->items[ $k ]['tax_rate'] = round( $this->cart->items[ $k ]['tax_rate'], 4 );
			$this->cart->items[ $k ]['totaldiscount'] = 0;
			$this->cart->items[ $k ]['totaldiscount_notax'] = 0;
			$this->cart->items[ $k ]['totaldiscount_tax'] = 0;
			$this->cart->items[ $k ]['coupons'] = array();

			$this->cart->items[ $k ]['_marked_total'] = false;
			$this->cart->items[ $k ]['_marked_qty'] = false;

			$r2 = $this->cart->items[ $k ];
			unset( $r2['qty'] );
			$r2['key'] = $k;
			$r['qty'] = (float) $r['qty'];

			for ( $i = 0; $i < $r['qty']; $i++ ) {
				$index_breakdown++;
				$this->cart->items_breakdown[ $index_breakdown ] = $r2;

				if ( ! is_int( $r['qty'] ) && ( $i + 1 ) > $r['qty'] ) {
					$this->cart->items_breakdown[ $index_breakdown ]['product_price'] = ($r['qty'] - floor( $r['qty'] ) ) * $this->cart->items_breakdown[ $index_breakdown ]['product_price'];
					$this->cart->items_breakdown[ $index_breakdown ]['product_price_notax'] = ($r['qty'] - floor( $r['qty'] ) ) * $this->cart->items_breakdown[ $index_breakdown ]['product_price_notax'];
					$this->cart->items_breakdown[ $index_breakdown ]['product_price_tax'] = ($r['qty'] - floor( $r['qty'] ) ) * $this->cart->items_breakdown[ $index_breakdown ]['product_price_tax'];
					break;
				}
			}
		}

		$this->cart->shipping = $this->get_storeshipping();
		$this->cart->shipping->total_tax = $this->cart->shipping->total - $this->cart->shipping->total_notax;
		if ( ! isset( $this->cart->shipping->shippings ) || ! is_array( $this->cart->shipping->shippings ) ) {
			$this->cart->shipping->shippings = array(
				(object) array(
					'shipping_id' => $this->cart->shipping->shipping_id,
					'total_notax' => $this->cart->shipping->total_notax,
					'total_tax' => $this->cart->shipping->total_tax,
					'total' => $this->cart->shipping->total,
					'tax_rate' => empty( $this->cart->shipping->total_notax ) ? 0 : ( $this->cart->shipping->total - $this->cart->shipping->total_notax ) / $this->cart->shipping->total_notax,
					'totaldiscount' => 0,
					'totaldiscount_notax' => 0,
					'totaldiscount_tax' => 0,
					'coupons' => array(),
				),
			);
		} else {
			foreach ( $this->cart->shipping->shippings as $k => $item ) {
				$this->cart->shipping->shippings[ $k ]->total_tax = $item->total - $item->total_notax;
			}
		}
		$this->cart->shipping->orig_total = $this->cart->shipping->total;
		$this->cart->shipping->totaldiscount = 0;
		$this->cart->shipping->totaldiscount_notax = 0;
		$this->cart->shipping->totaldiscount_tax = 0;
		$this->cart->shipping->coupons = array();

		$this->cart->payment = $this->get_storepayment();
		$this->cart->payment->total_tax = $this->cart->payment->total - $this->cart->payment->total_notax;
		$this->cart->payment->orig_total = $this->cart->payment->total;
		$this->cart->payment->totaldiscount = 0;
		$this->cart->payment->totaldiscount_notax = 0;
		$this->cart->payment->totaldiscount_tax = 0;
		$this->cart->payment->coupons = array();
	}

	protected function cartitem_update( $params ) {
		if ( empty( $params['track_product_price'] ) ) {
			return;
		}

		if ( ! empty( $params['discount_value'] ) ) {
			//track product discount
			$tracking_discount = 0;
			$tracking_discount_notax = 0;

			if ( $params['is_discount_before_tax'] ) {
				$recorded_discounts = $this->cartitem_update_each( $params, true );
				foreach ( $recorded_discounts as $k => $value ) {
					// calculate price after tax
					$discount = $value * ( 1 + $this->cart->items_breakdown[ $k ]['tax_rate'] );
					$this->cart->items_breakdown[ $k ]['product_price'] -= $discount;
					$this->cart->items_breakdown[ $k ]['totaldiscount'] += $discount;
					if ( ! isset( $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] ) ) {
						$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] = 0;
					}
					$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] += $discount;

					// calculate tax
					$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'] =
						$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_notax'];
					$this->cart->items_breakdown[ $k ]['totaldiscount_tax'] += $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'];
				}

				$this->cartitem_update_line( $params );
			} else {
				$this->cartitem_update_each( $params, false );
				$this->cartitem_update_each( $params, true );

				$this->cartitem_update_line( $params );
			}
		}

		if ( ! empty( $params['shipping_discount_value'] ) ) {
			//track shipping discount

			$this->cart->shipping->total -= $params['shipping_discount_value'];
			$this->cart->shipping->totaldiscount += $params['shipping_discount_value'];
			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount'] = $params['shipping_discount_value'];

			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;

			$this->cart->shipping->total_notax -= $params['shipping_discount_value_notax'];
			$this->cart->shipping->totaldiscount_notax += $params['shipping_discount_value_notax'];
			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = $params['shipping_discount_value_notax'];

			// calculate tax
			if ( $params['is_discount_before_tax'] ) {
				$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] =
					$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'];
				$this->cart->shipping->totaldiscount_tax += $this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'];
			}

			if ( $params['is_discount_before_tax'] ) {
				$recorded_discounts = $this->shippingitem_update_each( $params, true );
				foreach ( $recorded_discounts as $k => $value ) {
					// calculate price after tax
					$discount = $value * ( 1 + $this->cart->shipping->shippings[ $k ]->tax_rate );
					$this->cart->shipping->shippings[ $k ]->total -= $discount;
					$this->cart->shipping->shippings[ $k ]->totaldiscount += $discount;
					if ( ! isset( $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] ) ) {
						$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] = 0;
					}
					$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] += $discount;

					// calculate tax
					$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] =
						$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'];
					$this->cart->shipping->shippings[ $k ]->totaldiscount_tax += $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'];
				}
				//$this->cartitem_update_line($params);
			} else {
				$this->shippingitem_update_each( $params, false );
				$this->shippingitem_update_each( $params, true );

				//$this->cartitem_update_line($params);
			}
		}

		if ( ! empty( $params['paymentfee_discount_value'] ) ) {
			//track paymentfee discount

			$this->cart->payment->total -= $params['paymentfee_discount_value'];
			$this->cart->payment->totaldiscount += $params['paymentfee_discount_value'];
			$this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount'] = $params['paymentfee_discount_value'];

			$this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
			$this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;

			$this->cart->payment->total_notax -= $params['paymentfee_discount_value_notax'];
			$this->cart->payment->totaldiscount_notax += $params['paymentfee_discount_value_notax'];
			$this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = $params['paymentfee_discount_value_notax'];

			// calculate tax
			if ( $params['is_discount_before_tax'] ) {
				$this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] =
					$this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'];
				$this->cart->payment->totaldiscount_tax += $this->cart->payment->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'];
			}
		}
	}

	private function cartitem_update_each( $params, $is_beforetax ) {
		if ( empty( $params['discount_value'] ) ) {
			return;
		}
		$tracking_discount = 0;
		$fail_safe = 0;
		$tmp_discounts = array();
		$postfix = $is_beforetax ? '_notax' : '';

		$found_items = array();
		$product_total = 0;
		$tmp_items_breakdown = $this->cart->items_breakdown;
		foreach ( $params['valid_items'] as $valid_item ) {
			foreach ( $tmp_items_breakdown as $k => $row ) {
				if ( $valid_item['key'] != $row['key'] ) {
					continue;
				}
				if ( round( $row[ 'product_price' . $postfix ], 4 ) <= 0 ) {
					continue;
				}

				$product_total += $row[ 'product_price' . $postfix ];
				$found_items[ $k ] = $row;

				unset( $tmp_items_breakdown[ $k ] );
				break;
			}
		}

		if ( empty( $product_total ) ) {
			return;
		}

		foreach ( $found_items as $k => $row ) {

			$each_discount = ( $params[ 'discount_value' . $postfix ] ) * ( $row[ 'product_price' . $postfix ] / $product_total );
			$discount = min( $each_discount, $row[ 'product_price' . $postfix ] );
			if ( ! isset( $tmp_discounts[ $k ] ) ) {
				$tmp_discounts[ $k ] = 0;
			}
			$tmp_discounts[ $k ] += $discount;

			$this->cart->items_breakdown[ $k ][ 'product_price' . $postfix ] -= $discount;
			$this->cart->items_breakdown[ $k ][ 'totaldiscount' . $postfix ] += $discount;
			if ( ! isset( $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] ) ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] = 0;
			}
			$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
			$tracking_discount += $discount;

			if ( $params['is_discount_before_tax'] && $is_beforetax ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			} elseif ( ! $params['is_discount_before_tax'] && ! $is_beforetax ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			}
		}

		//penny problem
		if ( $tracking_discount != $params[ 'discount_value' . $postfix ] ) {
			foreach ( $found_items as $k => $row ) {
				$discount = min( ( $params[ 'discount_value' . $postfix ] - $tracking_discount ), $row[ 'product_price' . $postfix ] );
				if ( ! isset( $tmp_discounts[ $k ] ) ) {
					$tmp_discounts[ $k ] = 0;
				}
				$tmp_discounts[ $k ] += $discount;
				$this->cart->items_breakdown[ $k ][ 'product_price' . $postfix ] -= $discount;
				$this->cart->items_breakdown[ $k ][ 'totaldiscount' . $postfix ] += $discount;
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
				$tracking_discount += round( $discount, 4 );
			}
		}

		return $tmp_discounts;
	}

	private function cartitem_update_line( $params ) {

		$new_items = array();
		foreach ( $this->cart->items_breakdown as $k => $row ) {

			$this->cart->items_breakdown[ $k ]['product_price'] = round( $this->cart->items_breakdown[ $k ]['product_price'], 4 );
			$this->cart->items_breakdown[ $k ]['product_price_notax'] = round( $this->cart->items_breakdown[ $k ]['product_price_notax'], 4 );
			if ( 0 == $this->cart->items_breakdown[ $k ]['product_price'] || 0 == $this->cart->items_breakdown[ $k ]['product_price_notax'] ) {
				$this->cart->items_breakdown[ $k ]['product_price'] = 0;
				$this->cart->items_breakdown[ $k ]['product_price_notax'] = 0;
			}
			$this->cart->items_breakdown[ $k ]['totaldiscount'] = round( $this->cart->items_breakdown[ $k ]['totaldiscount'], 4 );
			$this->cart->items_breakdown[ $k ]['totaldiscount_notax'] = round( $this->cart->items_breakdown[ $k ]['totaldiscount_notax'], 4 );

			if ( ! isset( $new_items[ $row['key'] ] ) ) {
				$new_items[ $row['key'] ] = array(
					'product_price' => $this->cart->items[ $row['key'] ]['orig_product_price'] * $this->cart->items[ $row['key'] ]['qty'],
					'product_price_notax' => $this->cart->items[ $row['key'] ]['orig_product_price_notax'] * $this->cart->items[ $row['key'] ]['qty'],
					'totaldiscount' => 0,
					'totaldiscount_notax' => 0,
					'totaldiscount_tax' => 0,
					'coupons' => array(),
				);
			}

			$new_items[ $row['key'] ]['product_price'] -= $row['totaldiscount'];
			$new_items[ $row['key'] ]['totaldiscount'] += $row['totaldiscount'];
			$new_items[ $row['key'] ]['product_price_notax'] -= $row['totaldiscount_notax'];
			$new_items[ $row['key'] ]['totaldiscount_notax'] += $row['totaldiscount_notax'];
			$new_items[ $row['key'] ]['totaldiscount_tax'] += $row['totaldiscount_tax'];

			foreach ( $row['coupons'] as $coupon_id => $c_row ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount'] = round( $this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount'], 4 );
				$this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount_notax'] = round( $this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount_notax'], 4 );
				if ( ! isset( $new_items[ $row['key'] ]['coupons'][ $coupon_id ] ) ) {
					$new_items[ $row['key'] ]['coupons'][ $coupon_id ] = array(
						'totaldiscount' => 0,
						'totaldiscount_notax' => 0,
						'totaldiscount_tax' => 0,
					);
				}
				$new_items[ $row['key'] ]['coupons'][ $coupon_id ]['totaldiscount'] += $c_row['totaldiscount'];
				$new_items[ $row['key'] ]['coupons'][ $coupon_id ]['totaldiscount_notax'] += $c_row['totaldiscount_notax'];
				$new_items[ $row['key'] ]['coupons'][ $coupon_id ]['totaldiscount_tax'] += $c_row['totaldiscount_tax'];
			}
		}

		foreach ( $new_items as $k => $row ) {
			$this->cart->items[ $k ]['totaldiscount'] = $row['totaldiscount'];
			$this->cart->items[ $k ]['totaldiscount_notax'] = $row['totaldiscount_notax'];
			$this->cart->items[ $k ]['totaldiscount_tax'] = $row['totaldiscount_tax'];
			$this->cart->items[ $k ]['coupons'] = $row['coupons'];
		}
	}

	private function shippingitem_update_each( $params, $is_beforetax ) {
		if ( empty( $params['shipping_discount_value'] ) ) {
			return;
		}

		$tracking_discount = 0;
		$fail_safe = 0;
		$tmp_discounts = array();
		$postfix = $is_beforetax ? '_notax' : '';

		$found_items = array();
		$shipping_total = 0;
		if ( empty( $params['valid_ships'] ) ) {
			$params['valid_ships'] = array();
		}
		foreach ( $params['valid_ships'] as $k => $row ) {
			if ( round( $row->{'total' . $postfix}, 4 ) <= 0 ) {
				continue;
			}
			$shipping_total += $row->{'total' . $postfix};
			$found_items[ $k ] = $row;
		}

		if ( empty( $shipping_total ) ) {
			return;
		}

		foreach ( $found_items as $k => $row ) {

			$each_discount = ( $params[ 'shipping_discount_value' . $postfix ] ) * ( $row->{'total' . $postfix} / $shipping_total );
			$discount = min( $each_discount, $row->{'total' . $postfix} );
			if ( ! isset( $tmp_discounts[ $k ] ) ) {
				$tmp_discounts[ $k ] = 0;
			}
			$tmp_discounts[ $k ] += $discount;

			$this->cart->shipping->shippings[ $k ]->{'total' . $postfix} -= $discount;
			$this->cart->shipping->shippings[ $k ]->{'totaldiscount' . $postfix} += $discount;
			if ( ! isset( $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] ) ) {
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] = 0;
			}
			$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
			$tracking_discount += $discount;

			if ( $params['is_discount_before_tax'] && $is_beforetax ) {
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			} elseif ( ! $params['is_discount_before_tax'] && ! $is_beforetax ) {
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			}
		}

		//penny problem
		if ( $tracking_discount != $params[ 'shipping_discount_value' . $postfix ] ) {
			foreach ( $found_items as $k => $row ) {
				$discount = min( ( $params[ 'shipping_discount_value' . $postfix ] - $tracking_discount ), $row->{'total' . $postfix} );
				if ( ! isset( $tmp_discounts[ $k ] ) ) {
					$tmp_discounts[ $k ] = 0;
				}
				$tmp_discounts[ $k ] += $discount;
				$this->cart->shipping->shippings[ $k ]->{'total' . $postfix} -= $discount;
				$this->cart->shipping->shippings[ $k ]->{'totaldiscount' . $postfix} += $discount;
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
				$tracking_discount += round( $discount, 4 );
			}
		}
		return $tmp_discounts;
	}

	protected function save_discount_to_session( $master_output ) {

		$product_discount = 0;
		$product_discount_notax = 0;
		$product_discount_tax = 0;
		$shipping_discount = 0;
		$shipping_discount_notax = 0;
		$shipping_discount_tax = 0;
		$usedproducts = '';
		$coupon_codes = array();
		$coupon_codes_noauto = array();
		$usedcoupons = array();
		$auto_codes = isset( $this->get_coupon_auto()->coupons ) ? $this->get_coupon_auto()->coupons : array();

		foreach ( $master_output as $coupon_id => $r ) {

			if ( empty( $r[1]['force_add'] ) && 1 != $this->allow_zero_value && empty( $r[1]['product_discount'] ) && empty( $r[1]['shipping_discount'] ) ) {
				continue;
			}
			$coupon_codes[] = $r[1]['coupon_code'];

			$isauto = false;
			if ( ! empty( $auto_codes ) ) {
				foreach ( $auto_codes as $auto_code ) {
					if ( $auto_code->id == $r[1]['coupon_id'] ) {
						$isauto = true;
						break;
					}
				}
			}

			$coupon_entered_id = ! empty( $r[1]['coupon_entered_id'] ) ? $r[1]['coupon_entered_id'] : $r[1]['coupon_id'];

			$display_text = '';
			if ( ! empty( $r[0]->tags['customer_display_text'] ) ) {
				$display_text = $r[0]->tags['customer_display_text'];
			} elseif ( ! empty( $r[0]->note ) ) {
				$match = array();
				preg_match( '/{customer_display_text:(.*)?}/i', $r[0]->note, $match );
				if ( ! empty( $match[1] ) ) {
					$display_text = $match[1];
				}
			}

			if ( empty( $display_text ) && $isauto ) {
				$display_text = '(' . AC()->lang->__( 'Discount' ) . ')';
			} else {
				if ( empty( $display_text ) ) {
					$display_text = $r[1]['coupon_code'];
				}
				$coupon_codes_noauto[] = $display_text;
			}

			$entered_coupon_ids[ $r[1]['coupon_id'] ] = 1;
			$product_discount += $r[1]['product_discount'];
			$product_discount_notax += $r[1]['product_discount_notax'];
			$product_discount_tax += $r[1]['product_discount_tax'];
			$shipping_discount += $r[1]['shipping_discount'];
			$shipping_discount_notax += $r[1]['shipping_discount_notax'];
			$shipping_discount_tax += $r[1]['shipping_discount_tax'];
			if ( ! empty( $r[1]['usedproducts'] ) ) {
				$usedproducts .= $r[1]['usedproducts'] . ',';
			}
			if ( ! empty( $r[2] ) ) {
				foreach ( $r[2] as $k => $row ) {
					$r[2][ $k ]['display_text'] = $display_text;
				}
				$usedcoupons = $usedcoupons + $r[2];
			} else {
				$usedcoupons[ $r[1]['coupon_id'] ] = array(
					'coupon_entered_id' => $coupon_entered_id,
					'coupon_code' => $r[1]['coupon_code'],
					'orig_coupon_code' => $r[1]['coupon_code'],
					'product_discount' => $r[1]['product_discount'],
					'product_discount_notax' => $r[1]['product_discount_notax'],
					'product_discount_tax' => $r[1]['product_discount_tax'],
					'shipping_discount' => $r[1]['shipping_discount'],
					'shipping_discount_notax' => $r[1]['shipping_discount_notax'],
					'shipping_discount_tax' => $r[1]['shipping_discount_tax'],

					//'paymentfee_discount' => isset($r[1]['paymentfee_discount']) ? $r[1]['paymentfee_discount'] : 0,
					//'paymentfee_discount_notax' => isset($r[1]['paymentfee_discount_notax']) ? $r[1]['paymentfee_discount_notax'] : 0,
					//'paymentfee_discount_tax' => isset($r[1]['paymentfee_discount_tax']) ? $r[1]['paymentfee_discount_tax'] : 0,

					'is_discount_before_tax' => $r[1]['is_discount_before_tax'],
					'usedproducts' => $r[1]['usedproducts'],
					'display_text' => $display_text,
					'isauto' => $isauto,
					'ischild' => false,
				);
			}
		}
		if ( empty( $usedcoupons ) ) {
			return null;
		}

		if ( ! empty( $auto_codes ) && count( $coupon_codes_noauto ) != count( $coupon_codes ) ) {
			array_unshift( $coupon_codes_noauto, '(' . AC()->lang->__( 'Discount' ) . ')' );
		}
		$user = AC()->helper->get_user();

		$session_array = (object) array(
			'redeemed' => true,
			'user_id' => $user->id,
			'uniquecartstring' => $this->getuniquecartstring( implode( ';', $coupon_codes ), true ),
			'coupon_id' => 1 == count( $coupon_codes ) ? key( $master_output ) : '--multiple--',
			'coupon_code' => implode( ', ', $coupon_codes_noauto ),
			'coupon_code_internal' => implode( ';', $coupon_codes ),
			'product_discount' => $product_discount,
			'product_discount_notax' => $product_discount_notax,
			'product_discount_tax' => $product_discount_tax,
			'shipping_discount' => $shipping_discount,
			'shipping_discount_notax' => $shipping_discount_notax,
			'shipping_discount_tax' => $shipping_discount_tax,
			'productids' => $usedproducts,
			'entered_coupon_ids' => $entered_coupon_ids,
			'processed_coupons' => $usedcoupons,
			'cart_items' => $this->cart->items,
			'cart_items_breakdown' => $this->cart->items_breakdown,
		);
		$this->session_set( 'coupon', $session_array );

		return $session_array;
	}

	protected function save_coupon_history( $order_id, $coupon_session = null ) {

		if ( empty( $coupon_session ) ) {
			$coupon_session = $this->session_get( 'coupon' );
		}
		if ( empty( $coupon_session ) ) {
			return null;
		}

		$this->session_set( 'coupon', null );

		$db = AC()->db;

		$order_id = (int) $order_id;
		$user_email = $this->get_orderemail( $order_id );

		if ( empty( $order_id ) ) {
			$order_id = 'NULL';
		}
		$user_email = empty( $user_email ) ? 'NULL' : '"' . $db->escape( $user_email ) . '"';

		$children_coupons = $coupon_session->processed_coupons;

		$coupon_ids = implode( ',', array_keys( $children_coupons ) );
		$sql = 'SELECT id,num_of_uses_total,num_of_uses_customer,function_type,coupon_value FROM #__awocoupon WHERE estore="' . $this->estore . '" AND state IN ("published") AND id IN (' . $coupon_ids . ')';
		$rows = $db->get_objectlist( $sql );

		$coupon_details = $db->escape( json_encode( $coupon_session ) );

		foreach ( $rows as $coupon_row ) {

			// mark coupon used
			$coupon_entered_id = (int) $children_coupons[ $coupon_row->id ]->coupon_entered_id;

			$usedproducts = ! empty( $children_coupons[ $coupon_row->id ]->usedproducts )
							? $children_coupons[ $coupon_row->id ]->usedproducts
							: 'NULL';

			$postfix = $children_coupons[ $coupon_row->id ]->is_discount_before_tax ? '_notax' : '';
			$shipping_discount = (float) $children_coupons[ $coupon_row->id ]->{'shipping_discount' . $postfix};
			$product_discount = (float) $children_coupons[ $coupon_row->id ]->{'product_discount' . $postfix};

			$sql = 'INSERT INTO #__awocoupon_history (estore,coupon_entered_id,coupon_id,user_id,user_email,coupon_discount,shipping_discount,order_id,productids,details)
				    VALUES ("' . $this->estore . '",' . $coupon_entered_id . ',' . $coupon_row->id . ',' . $coupon_session->user_id . ',' . $user_email . ',' . $product_discount . ',' . $shipping_discount . ',' . $order_id . ',"' . $usedproducts . '","' . $coupon_details . '")';
			$db->query( $sql );

			$is_unpublished = false;
			if ( ! empty( $coupon_row->num_of_uses_total ) ) {
				// limited amount of uses so can be removed
				$sql = 'SELECT COUNT(id) FROM #__awocoupon_history WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_row->id . ' GROUP BY coupon_id';
				$num = $db->get_value( $sql );
				if ( ! empty( $num ) && $num >= $coupon_row->num_of_uses_total ) {
					// already used max number of times
					$is_unpublished = true;
					$db->query( 'UPDATE #__awocoupon SET state="unpublished" WHERE id=' . $coupon_row->id );
				}
			}
		}

		$this->initialize_coupon();

		return true;
	}

	protected function delete_coupon_from_session( $coupon_id = '' ) {
		$coupon_id = (int) $coupon_id;
		if ( empty( $coupon_id ) ) {
			return $this->initialize_coupon(); // empty all coupon codes from cart
		}

		$coupon_session = $this->session_get( 'coupon' );
		if ( empty( $coupon_session ) ) {
			return;
		}

		if ( ! isset( $coupon_session->entered_coupon_ids[ $coupon_id ] ) ) {
			return;
		}

		if ( 1 == count( $coupon_session->entered_coupon_ids ) ) {
			$this->initialize_coupon();
			$this->initialize_coupon_auto();
			return;
		}

		// remove coupon
		$coupon_session->uniquecartstring = mt_rand();
		unset( $coupon_session->entered_coupon_ids[ $coupon_id ] );
		foreach ( $coupon_session->processed_coupons as $k => $row ) {
			if ( $row->coupon_entered_id == $coupon_id ) {
				unset( $coupon_session->processed_coupons[ $k ] );
			}
		}

		// reprocess remaining coupons
		$this->session_set( 'coupon', $coupon_session );

		//auto coupons
		$autosess = $this->get_coupon_auto();
		if ( ! empty( $autosess ) ) {
			foreach ( $autosess->coupons as $k => $coupon ) {
				if ( $coupon->id != $coupon_id ) {
					continue;
				}
				unset( $autosess->coupons[ $k ] );
			}
		}

		if ( empty( $autosess->coupons ) ) {
			$this->initialize_coupon_auto();
			return;
		}
		$autosess->uniquecartstring = mt_rand();
		$this->session_set( 'coupon_auto', $autosess );
	}

	protected function cleanup_ordercancel_helper( $order_id, $order_status ) {

		$order_id = (int) $order_id;
		if ( empty( $order_id ) ) {
			return;
		}

		$_cancelled_statuses = $this->params->get( 'ordercancel_order_status', '' );
		if ( empty( $_cancelled_statuses ) ) {
			return;
		}
		if ( ! is_array( $_cancelled_statuses ) ) {
			$_cancelled_statuses = array( $_cancelled_statuses );
		}
		if ( ! in_array( $order_status, $_cancelled_statuses ) ) {
			return;
		}

		$db = AC()->db;
		$rows = $db->get_objectlist( 'SELECT h.id,h.coupon_id,c.state FROM #__awocoupon_history h LEFT JOIN #__awocoupon c ON c.id=h.coupon_id WHERE h.order_id=' . (int) $order_id );
		foreach ( $rows as $row ) {
			$history_id = (int) $row->id;
			if ( ! empty( $history_id ) ) {
				$db->query( 'DELETE FROM #__awocoupon_history WHERE id=' . $history_id );
			}

			if ( 'unpublished' == $row->state ) {
				$db->query( 'UPDATE #__awocoupon SET state="published" WHERE id=' . $row->coupon_id );
			}
		}
	}

	protected function initialize_coupon() {
		$this->session_set( 'coupon', 0 );
	}

	protected function initialize_coupon_auto() {
		$this->session_set( 'coupon_auto', 0 );
	}

	protected function set_coupon_auto( $coupon_rows ) {
		if ( empty( $coupon_rows ) ) {
			$this->initialize_coupon_auto();
		} else {
			$master_list = new stdClass();
			$master_list->uniquecartstring = $this->getuniquecartstringauto();
			$master_list->coupons = $coupon_rows;
			$this->session_set( 'coupon_auto', $master_list );
		}
	}

	protected function get_coupon_auto() {
		$coupon_row = $this->session_get( 'coupon_auto' );
		if ( ! empty( $coupon_row ) ) {
			if ( ! empty( $coupon_row->coupons ) ) {
				return $coupon_row;
			}
		}
		return '';
	}

	public function get_coupon_session() {
		return $this->session_get( 'coupon' );
	}

	public function is_couponcode_in_session( $coupon_code ) {
		$coupon_session = $this->get_coupon_session();
		if ( empty( $coupon_session->coupon_code_internal ) ) {
			return false;
		}

		$is_casesensitive = AC()->coupon->is_case_sensitive();
		return $this->is_coupon_in_array( $is_casesensitive, $coupon_code, explode( ';', $coupon_session->coupon_code_internal ) );
	}

	protected function return_false( $key, $type = 'key', $force = 'donotforce' ) {
		if ( $this->reprocess ) {
			return;
		}

		if ( empty( $this->coupon_row ) || ( ! empty( $this->coupon_row ) && empty( $this->coupon_row->isauto ) ) ) {

			// display error to screen, if coupon is being set.
			$err = 'custom_error' === $type ? $key : AC()->lang->get_data( $this->params->get( 'idlang_' . $key ) );
			if ( empty( $err ) ) {
				$err = $this->params->get( $key, $this->default_err_msg );
			}
			if ( ! empty( $this->coupon_row ) && 'force' !== $force && 'combination' === $this->coupon_row->function_type ) {
				$err = $this->params->get( $key, $this->default_err_msg );
			}
			if ( ! empty( $err ) ) {
				if ( empty( $this->coupon_row ) ) {
					$this->error_msgs[0] = $err;
				} else {
					$this->error_msgs[ $this->coupon_row->id ] = $this->coupon_row->coupon_code . ': ' . $err;
				}
			}
		}

		return false;
	}

	protected function realtotal_verify( &$_session_product, &$_session_product_notax ) {
		return;
	}

	protected function get_storeshipping_isdefaultbypass( $coupon_id ) {
		return false;
	}

	protected function cart_object_is_initialized() {
		return true;
	}

	protected function get_storepayment() {
		return (object) array(
			'payment_id' => 0,
			'total_notax' => 0,
			'total' => 0,
		);
	}

	private function get_max_discount_amount( $coupon_row, &$total_notax, &$total ) {
		if ( ! isset( $coupon_row->params->max_discount_amt ) ) {
			return;
		}

		$coupon_row->params->max_discount_amt = (float) $coupon_row->params->max_discount_amt;
		if ( empty( $coupon_row->params->max_discount_amt ) ) {
			return;
		}

		if ( $coupon_row->is_discount_before_tax ) {
			if ( $total_notax <= $coupon_row->params->max_discount_amt ) {
				return;
			}

			$total = $coupon_row->params->max_discount_amt * $total / $total_notax;
			$total_notax = $coupon_row->params->max_discount_amt;
		} else {
			if ( $total <= $coupon_row->params->max_discount_amt ) {
				return;
			}

			$total_notax = $coupon_row->params->max_discount_amt * $total_notax / $total;
			$total = $coupon_row->params->max_discount_amt;
		}
	}

	protected function session_get( $name, $default = null ) {
		$value = AC()->helper->get_session( 'site', $name, $default );
		if ( empty( $value ) ) {
			return $value;
		}

		if ( ! is_array( $value ) && ! is_object( $value ) ) {
			$tmp = $value;
			$value = json_decode( $value, true );
			$value = json_last_error() === JSON_ERROR_NONE ? $this->array_to_object( $value ) : $tmp;
		}

		return $value;
	}

	protected function session_set( $name, $value ) {
		if ( is_object( $value ) ) {
			$value = json_encode( $value );
		}
		AC()->helper->set_session( 'site', $name, $value );
	}

	private function array_to_object( $array ) {
		if ( ! is_array( $array ) && ! is_object( $array ) ) {
			return;
		}
		foreach ( $array as $k => $v ) {
			if ( is_array( $v ) ) {
				if ( is_numeric( $k ) ) {
					if ( ! isset( $obj ) ) {
						$obj = array();
					}
					$obj[ $k ] = $this->array_to_object( $v );
				} else {
					if ( ! isset( $obj ) ) {
						$obj = new stdClass();
					}
					$obj->{$k} = $this->array_to_object( $v );
				}
			} else {
				if ( is_numeric( $k ) ) {
					if ( ! isset( $obj ) ) {
						$obj = array();
					}
					$obj[ $k ] = $v;
				} else {
					if ( ! isset( $obj ) ) {
						$obj = new stdClass();
					}
					$obj->{$k} = $v;
				}
			}
		}
		if ( ! isset( $obj ) ) {
			$obj = array();
		}
		return $obj;
	}

	//---------------------------------------------------------
	// utilities
	//---------------------------------------------------------
	private function array_iunique( $array ) {
		return array_intersect_key( $array, array_unique( array_map( 'strtolower', $array ) ) );
	}

	private function in_arrayi( $needle, $haystack ) {
		return in_array( strtolower( $needle ), array_map( 'strtolower', $haystack ) );
	}

	private function is_coupon_in_array( $is_casesensitive, $coupon_code, $array ) {
		return (
			( $is_casesensitive && ( in_array( $coupon_code,$array ) ) )
			|| ( ! $is_casesensitive && ( $this->in_arrayi( $coupon_code, $array ) ) )
			) ? true : false;
	}

	private function array_intersect_diff( $type, $is_casesensitive, $find, $haystack ) {
		return
			'intersect' == $type
				? ( $is_casesensitive ? array_intersect( $haystack, $find ) : array_uintersect( $haystack, $find, 'strcasecmp' ) )
				: ( $is_casesensitive ? array_diff( $find, $haystack ) : array_udiff( $find, $haystack, 'strcasecmp' ) );
	}

	protected function is_customer_num_uses( $coupon_id, $max_num_uses, $customer_num_uses ) {

		$customer_num_uses = (int) $customer_num_uses;
		$max_num_uses = (int) $max_num_uses;

		if ( ! empty( $customer_num_uses ) && $customer_num_uses >= $max_num_uses ) {
			// per user: already used max number of times
			return false;
		}

		return true;
	}

	protected function is_check_payment_method_later() {
		return false;
	}

	protected function is_product_eligible( $product_id, $coupon_row ) {
		if ( isset( $coupon_row->asset[0]->rows ) ) {

			$asset_types = array( 'product', 'category', 'manufacturer', 'vendor' );
			foreach ( $coupon_row->asset[0]->rows as $asset_type => $asset_row ) {
				if ( ! in_array( $asset_type, $asset_types ) ) {
					continue;
				}
				if ( empty( $asset_row->rows ) ) {
					continue;
				}

				if ( 'specific' != $coupon_row->discount_type ) {
					continue;
				}

				if (
					( 'product' == $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_product'] ) )
				|| ( 'category' == $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_category'] ) )
				|| ( 'manufacturer' == $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_manufacturer'] ) )
				|| ( 'vendor' == $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_vendor'] ) )
				) {
				} else {
					return false;
				}
			}
		}
		return true;
	}

	private function get_processed_discount_array( $coupon_row = array(), $usedproductids = '' ) {
		return array(
			'redeemed' => true,
			'coupon_id' => ! empty( $coupon_row ) ? $coupon_row->id : 0,
			'coupon_code' => ! empty( $coupon_row ) ? $coupon_row->coupon_code : '',
			'product_discount' => 0,
			'product_discount_notax' => 0,
			'product_discount_tax' => 0,
			'shipping_discount' => 0,
			'shipping_discount_notax' => 0,
			'shipping_discount_tax' => 0,
			'is_discount_before_tax' => ! empty( $coupon_row ) ? $coupon_row->is_discount_before_tax : 0,
			'usedproducts' => is_array( $usedproductids ) ? implode( ',', $usedproductids ) : $usedproductids,
		);
	}

	//-------------------------------------------------------
	// couponvalidate
	//-------------------------------------------------------
	private function couponvalidate_user( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->user->rows ) ) {
			return;
		}
		$userlist = $coupon_row->asset[0]->rows->user->rows;

		if ( empty( $coupon_row->customer->user_id ) ) {
			// not a logged in user
			return 'errUserLogin';
		}

		// verify the user is on the list for this coupon
		$mode = empty( $coupon_row->asset[0]->rows->user->mode ) ? 'include' : $coupon_row->asset[0]->rows->user->mode;
		if (
			( 'include' == $mode && ! isset( $userlist[ $coupon_row->customer->user_id ] ) )
							||
			( 'exclude' == $mode && isset( $userlist[ $coupon_row->customer->user_id ] ) )
		) {
			// not on user list
			return 'errUserNotOnList';
		}
	}

	private function couponvalidate_usergroup( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->usergroup->rows ) ) {
			return;
		}
		$usergrouplist = $coupon_row->asset[0]->rows->usergroup->rows;

		$customergroups = $this->get_storeshoppergroupids( $coupon_row->customer->user_id );

		$is_in_list = false;
		foreach ( $customergroups as $group_id ) {
			if ( isset( $usergrouplist[ $group_id ] ) ) {
				$is_in_list = true;
				break;
			}
		}
		$mode = empty( $coupon_row->asset[0]->rows->usergroup->mode ) ? 'include' : $coupon_row->asset[0]->rows->usergroup->mode;
		if (
			( 'include' == $mode && ! $is_in_list )
							||
			( 'exclude' == $mode && $is_in_list )
		) {
			// list restriction
			return 'errUserGroupNotOnList';
		}
	}

	private function couponvalidate_country( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->country->rows ) ) {
			return;
		}
		$countrylist = $coupon_row->asset[0]->rows->country->rows;

		if ( empty( $coupon_row->customer->address ) ) {
			$coupon_row->customer->address = $this->get_customeraddress();
		}
		if ( empty( $coupon_row->customer->address->country_id ) ) {
			// not on  list
			return 'errCountryInclude';
		}

		$mode = empty( $coupon_row->asset[0]->rows->country->mode ) ? 'include' : $coupon_row->asset[0]->rows->country->mode;

		if ( 'include' == $mode && ! isset( $countrylist[ $coupon_row->customer->address->country_id ] ) ) {
			return 'errCountryInclude';
		}
		if ( 'exclude' == $mode && isset( $countrylist[ $coupon_row->customer->address->country_id ] ) ) {
			return 'errCountryExclude';
		}
	}

	private function couponvalidate_countrystate( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->countrystate->rows ) ) {
			return;
		}
		$countrystatelist = $coupon_row->asset[0]->rows->countrystate->rows;

		if ( empty( $coupon_row->customer->address ) ) {
			$coupon_row->customer->address = $this->get_customeraddress();
		}
		if ( empty( $coupon_row->customer->address->state_id ) ) {
			// not on  list
			return 'errCountrystateInclude';
		}

		$mode = empty( $coupon_row->asset[0]->rows->countrystate->mode ) ? 'include' : $coupon_row->asset[0]->rows->countrystate->mode;

		if ( 'include' == $mode && ! isset( $countrystatelist[ $coupon_row->customer->address->state_id ] ) ) {
			return 'errCountrystateInclude';
		}
		if ( 'exclude' == $mode && isset( $countrystatelist[ $coupon_row->customer->address->state_id ] ) ) {
			return 'errCountrystateExclude';
		}
	}

	private function couponvalidate_paymentmethod( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->paymentmethod->rows ) ) {
			return;
		}
		$paymentmethodlist = $coupon_row->asset[0]->rows->paymentmethod->rows;

		if ( empty( $this->cart->payment->payment_id ) ) {
			// not on  list
			if ( $this->is_check_payment_method_later() ) {
				return; // payment not selected yet, dont throw error
			}
			return 'errPaymentMethodInclude';
		}

		$mode = empty( $coupon_row->asset[0]->rows->paymentmethod->mode ) ? 'include' : $coupon_row->asset[0]->rows->paymentmethod->mode;

		if ( 'include' == $mode && ! isset( $paymentmethodlist[ $this->cart->payment->payment_id ] ) ) {
			return 'errPaymentMethodInclude';
		}
		if ( 'exclude' == $mode && isset( $paymentmethodlist[ $this->cart->payment->payment_id ] ) ) {
			return 'errPaymentMethodExclude';
		}
	}

	private function couponvalidate_shipping( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->shipping->rows ) ) {
			return;
		}
		$shippinglist = $coupon_row->asset[0]->rows->shipping->rows;

		if ( empty( $coupon_row->cart_shipping->shippings ) ) {
			return 'errShippingInclList';
		}

		$mode = empty( $coupon_row->asset[0]->rows->shipping->mode ) ? 'include' : $coupon_row->asset[0]->rows->shipping->mode;

		if ( 'include' == $mode ) {
			$is_in_list = false;
			foreach ( $coupon_row->cart_shipping->shippings as $row ) {
				if ( isset( $shippinglist[ $row->shipping_id ] ) ) {
					$is_in_list = true;
					break;
				}
			}
			if ( ! $is_in_list ) {
				// (include) not on list
				return $this->return_false( 'errShippingInclList' );
			}
		} elseif ( 'exclude' == $mode ) {
			$is_not_in_list = false;
			foreach ( $coupon_row->cart_shipping->shippings as $row ) {
				if ( ! isset( $shippinglist[ $row->shipping_id ] ) ) {
					$is_not_in_list = true;
					break;
				}
			}
			if ( ! $is_not_in_list ) {
				// (exclude) all on list
				return $this->return_false( 'errShippingExclList' );
			}
		}
	}

	protected function couponvalidate_numuses( &$coupon_row ) {
		// number of use check
		$db = AC()->db;

		if ( ! empty( $coupon_row->num_of_uses_total ) ) {
			// check to make sure it has not been used more than the limit
			$sql = 'SELECT COUNT(id) FROM #__awocoupon_history WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_row->id . ' GROUP BY coupon_id';
			$num = $db->get_value( $sql );
			if ( ! empty( $num ) && $num >= $coupon_row->num_of_uses_total ) {
				// total: already used max number of times
				return 'errTotalMaxUse';
			}
		}

		if ( ! empty( $coupon_row->num_of_uses_customer ) ) {
			// check to make sure user has not used it more than the limit
			$num = 0;
			$user_id = $coupon_row->customer->user_id;
			if ( ! empty( $user_id ) ) {
				$sql = 'SELECT COUNT(id) FROM #__awocoupon_history WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_row->id . ' AND user_id=' . $user_id . ' AND (user_email IS NULL OR user_email="") GROUP BY coupon_id,user_id';
				$num = (int) $db->get_value( $sql );
			}
			if ( ! $this->is_customer_num_uses( $coupon_row->id, $coupon_row->num_of_uses_customer, $num ) ) {
				// per user: already used max number of times
				return 'errUserMaxUse';
			}
		}

		return null;
	}

	private function couponvalidate_product_special( &$coupon_row ) {

		if ( empty( $coupon_row->params->exclude_special ) ) {
			return;
		}

		foreach ( $coupon_row->cart_items_breakdown as $k => $tmp ) {
			if ( ! empty( $tmp['is_special'] ) ) {
				unset( $coupon_row->cart_items_breakdown[ $k ] );// remove specials
			}
		}
		foreach ( $coupon_row->cart_items as $k => $tmp ) {
			if ( ! empty( $tmp['is_special'] ) ) {
				unset( $coupon_row->cart_items[ $k ] );// remove specials
			}
		}
		if ( empty( $coupon_row->cart_items_breakdown ) ) {
			// all products in cart are on special
			return 'errDiscountedExclude';
		}
	}

	private function couponvalidate_product_discounted( &$coupon_row ) {

		if ( empty( $coupon_row->params->exclude_discounted ) ) {
			return;
		}

		foreach ( $coupon_row->cart_items_breakdown as $k => $tmp ) {
			if ( ! empty( $tmp['is_discounted'] ) ) {
				unset( $coupon_row->cart_items_breakdown[ $k ] );// remove specials
			}
		}
		foreach ( $coupon_row->cart_items as $k => $tmp ) {
			if ( ! empty( $tmp['is_discounted'] ) ) {
				unset( $coupon_row->cart_items[ $k ] );// remove specials
			}
		}
		if ( empty( $coupon_row->cart_items_breakdown ) ) {
			// all products in cart are on special
			return 'errDiscountedExclude';
		}
	}

	private function couponvalidate_asset_producttype( &$coupon_row ) {

		$r_err = $this->couponvalidate_include_exclude( $coupon_row, 0, array(
			'is_update_product_total' => true,
			'is_update_product_count' => true,
			'is_update_is_valid_type' => true,
		) );
		if ( ! empty( $r_err ) ) {
			return $r_err;
		}

		if ( 'shipping' == $coupon_row->function_type && 'specific' == $coupon_row->discount_type ) {

			$asset_types = array( 'product', 'category', 'manufacturer', 'vendor' );
			foreach ( $coupon_row->asset[0]->rows as $asset_type => $asset_row ) {
				if ( ! in_array( $asset_type, $asset_types ) ) {
					continue;
				}
				if ( empty( $asset_row->rows ) ) {
					continue;
				}

				$mode = empty( $asset_row->mode ) ? 'include' : $asset_row->mode;
				$assetlist = $asset_row->rows;

				$r_err = '';
				if ( 'include' == $mode ) {
					$is_not_in_list = false;
					foreach ( $coupon_row->cart_items as $row ) {
						if ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ] ) ) {
							$coupon_row->cart_items_def[ $row['product_id'] ] = -1;
						}
						if (
							( 'product' == $asset_type && ! isset( $assetlist[ $row['product_id'] ] ) )
										||
							( 'product' != $asset_type && ! isset( $assetlist[ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) )
						) {
							$is_not_in_list = true;
							break;
						}
					}
					if ( $is_not_in_list ) {
						$r_err = 'err' . ucfirst( strtolower( $asset_type ) ) . 'InclList';
					}
				} elseif ( 'exclude' == $mode ) {
					$is_in_list = false;
					foreach ( $coupon_row->cart_items as $row ) {
						if ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ] ) ) {
							$coupon_row->cart_items_def[ $row['product_id'] ] = -1;
						}
						if (
							( 'product' == $asset_type && isset( $assetlist[ $row['product_id'] ] ) )
										||
							( 'product' != $asset_type && isset( $assetlist[ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) )
						) {
							$is_in_list = true;
							break;
						}
					}
					if ( $is_in_list ) {
						$r_err = 'err' . ucfirst( strtolower( $asset_type ) ) . 'ExclList';
					}
				}
				if ( ! empty( $r_err ) ) {
					return $r_err;
				}
			}
		}
		return;
	}
	/*
		$coupon_row - the coupon properties and items being set
		$_params
			required
			- asset_mode: include/exclude
			- asset_type: product/category/manufacturer/vendor/shipping...etc
			- valid_list: the list of items to test against
			- error_include: the error message for include
			- error_exclude: the error message for exclude
			optional
			- is_update_product_total: update coupon_row with product total if matched, true/false
			- is_update_product_count: update coupon_row with product quantity if matched, true/false
			- is_update_is_valid_type: update coupon_row with type being valid if matched, true/false

		returns array in $coupon_row->temporary
			- products_count: float with product total to update, used by buyxgety
			- products_list: int with product quantity to update, used by buyxgety
	*/
	//private function couponvalidate_include_exclude( &$coupon_row, $_params) {
	private function couponvalidate_include_exclude( &$coupon_row, $index, $_params ) {

		$coupon_row->temporary = array(
			'products_count' => 0,
			'products_list' => array(),
		);

		if ( empty( $coupon_row->asset[ $index ]->rows ) ) {
			return;
		}

		$mode = array();
		$assetlist = array();
		$asset_types = array( 'product', 'category', 'manufacturer', 'vendor' );
		foreach ( $coupon_row->asset[ $index ]->rows as $asset_type => $asset_row ) {
			if ( ! in_array( $asset_type, $asset_types ) ) {
				continue;
			}
			if ( empty( $asset_row->rows ) ) {
				continue;
			}

			$mode[ $asset_type ] = empty( $asset_row->mode ) ? 'include' : $asset_row->mode;
			$assetlist[ $asset_type ] = $asset_row->rows;

			if ( 'product' != $asset_type ) {
				$tmp = call_user_func( array( $this, 'get_store' . $asset_type ) , implode( ',', array_keys( $coupon_row->cart_items_def ) ) );
				foreach ( $tmp as $tmp2 ) {
					if ( isset( $assetlist[ $asset_type ][ $tmp2->asset_id ] ) ) {
						$coupon_row->cart_items_def[ $tmp2->product_id ][ $asset_type ] = $tmp2->asset_id;
					}
				}
			}
		}

		if ( in_array( $index, array( 0, 1, 2 ) ) ) {
			$error_string = '';
			$is_at_least_one = false;
			foreach ( $coupon_row->cart_items as $k => $row ) {

				$in_list = true;
				foreach ( $coupon_row->asset[ $index ]->rows as $asset_type => $asset_row ) {
					if ( empty( $assetlist[ $asset_type ] ) ) {
						continue;
					}
					if ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ] ) ) {
						$coupon_row->cart_items_def[ $row['product_id'] ] = -1;
					}
					if (
						( 'include' == $mode[ $asset_type ] && ! isset( $assetlist[ $asset_type ][ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) )
					|| ( 'exclude' == $mode[ $asset_type ] && isset( $assetlist[ $asset_type ][ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) )
					) {
						$in_list = false;
						if ( empty( $error_string ) ) {
							if ( 'include' == $mode[ $asset_type ] ) {
								$error_string = 'err' . ucfirst( strtolower( $asset_type ) ) . 'InclList';
							} else {
								$error_string = 'err' . ucfirst( strtolower( $asset_type ) ) . 'ExclList';
							}
						}
					} else {
						if ( 0 == $index && isset( $_params['is_update_is_valid_type'] ) && $_params['is_update_is_valid_type'] ) {
							$coupon_row->cart_items_def[ $row['product_id'] ][ 'is_valid_' . $asset_type ] = 1;
						}
					}
				}

				if ( $in_list ) {
					$is_at_least_one = true;
					if ( ! $row['_marked_total'] && isset( $_params['is_update_product_total'] ) && $_params['is_update_product_total'] ) {
						$coupon_row->cart_items[ $k ]['_marked_total'] = true;
						$coupon_row->specific_min_value += $row['qty'] * $row['product_price'];
						$coupon_row->specific_min_value_notax += $row['qty'] * $row['product_price_notax'];
					}
					if ( ! $row['_marked_qty'] && isset( $_params['is_update_product_count'] ) && $_params['is_update_product_count'] ) {
						$coupon_row->cart_items[ $k ]['_marked_qty'] = true;
						$coupon_row->specific_min_qty += $row['qty'];
					}
					$coupon_row->temporary['products_count'] += $row['qty'];
					$coupon_row->temporary['products_list'][ $row['product_id'] ] = $row['product_id'];
				}
			}

			if ( ! $is_at_least_one ) {
				return $error_string;
			}
		}
		return;
	}

	private function couponvalidate_daily_time_limit( $coupon_row ) {

		if ( empty( $coupon_row->note ) ) {
			return;
		}

		$match = array();
		preg_match( '/{daily_time_limit:\s*(\d+)\s*,\s*(\d*)\s*}/i', $coupon_row->note, $match );
		if ( isset( $match[1] ) && 4 == strlen( $match[1] ) && 4 == strlen( $match[2] ) && $match[2] > $match[1] && $match[2] <= 2359 ) {
			$current_time = AC()->helper->get_date( null, 'Hi', 'utc2utc' );
			if ( (int) $current_time < (int) $match[1] || (int) $current_time > (int) $match[2] ) {
				return 'errNoRecord';
			}
		}

		return;
	}

	private function couponvalidate_min_total_qty( $coupon_row ) {
		if ( ! empty( $coupon_row->params->min_value_type ) && ! empty( $coupon_row->min_value ) ) {
			if ( 'specific' == $coupon_row->params->min_value_type ) {
				if ( round( $coupon_row->specific_min_value, 4 ) < $coupon_row->min_value ) {
					return 'errMinVal';
				}
			} elseif ( 'specific_notax' == $coupon_row->params->min_value_type ) {
				if ( round( $coupon_row->specific_min_value_notax, 4 ) < $coupon_row->min_value ) {
					return 'errMinVal';
				}
			}
		}

		if ( ! empty( $coupon_row->params->min_qty_type ) && ! empty( $coupon_row->params->min_qty ) ) {
			if ( 'specific' == $coupon_row->params->min_qty_type ) {
				if ( $coupon_row->specific_min_qty < $coupon_row->params->min_qty ) {
					return 'errMinQty';
				}
			}
		}

		return;
	}

}


/*
asset1_type
asset1_mode
asset1_qty
asset2_type
asset2_mode
asset2_qty
awocoupon_user
get_awocouponasset
*/
