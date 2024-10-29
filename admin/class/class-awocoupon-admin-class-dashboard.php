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
class AwoCoupon_Admin_Class_Dashboard extends AwoCoupon_Library_Class {

	/**
	 * Get statistics
	 */
	public function get_stats() {
		$_products = array();

		/*
		* Get total number of entries
		*/
		$_products['total'] = AC()->db->get_value( 'SELECT count(id)  FROM #__awocoupon WHERE estore="' . AWOCOUPON_ESTORE . '"' );

		/*
		* Get total number of approved entries
		*/
		$current_date = date( 'Y-m-d H:i:s' );
		$sql = 'SELECT count(id) 
				  FROM #__awocoupon 
				 WHERE state="published"
				   AND estore="' . AWOCOUPON_ESTORE . '"
				   AND ( ((startdate IS NULL OR startdate="") 	AND (expiration IS NULL OR expiration="")) OR
						 ((expiration IS NULL OR expiration="") AND startdate<="' . $current_date . '") OR
						 ((startdate IS NULL OR startdate="") 	AND expiration>="' . $current_date . '") OR
						 (startdate<="' . $current_date . '"		AND expiration>="' . $current_date . '")
					   )
				';
		$_products['active'] = AC()->db->get_value( $sql );

		$sql = 'SELECT count(id) 
				  FROM #__awocoupon 
				 WHERE estore="' . AWOCOUPON_ESTORE . '" AND (state="unpublished"  OR startdate>"' . $current_date . '" OR expiration<"' . $current_date . '")';
		$_products['inactive'] = AC()->db->get_value( $sql );

		$sql = 'SELECT count(id) 
				  FROM #__awocoupon
				 WHERE estore="' . AWOCOUPON_ESTORE . '" AND state="template"';
		$_products['templates'] = AC()->db->get_value( $sql );

		return (object) $_products;
	}

}

