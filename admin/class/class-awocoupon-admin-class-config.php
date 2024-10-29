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
class AwoCoupon_Admin_Class_Config extends AwoCoupon_Library_Class {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name = 'config';
		parent::__construct();

		$this->idlangs = array(
			'errNoRecord',
			'errMinVal',
			'errMinQty',
			'errUserLogin',
			'errUserNotOnList',
			'errUserGroupNotOnList',
			'errUserMaxUse',
			'errTotalMaxUse',
			'errProductInclList',
			'errProductExclList',
			'errCategoryInclList',
			'errCategoryExclList',
			'errManufacturerInclList',
			'errManufacturerExclList',
			'errVendorInclList',
			'errVendorExclList',
			'errShippingSelect',
			'errShippingValid',
			'errShippingInclList',
			'errShippingExclList',
			'errProgressiveThreshold',
			'errDiscountedExclude',
			'errCountryInclude',
			'errCountryExclude',
			'errCountrystateInclude',
			'errCountrystateExclude',
			'errPaymentMethodInclude',
			'errPaymentMethodExclude',
		);
	}

	/**
	 * Get language information
	 */
	public function get_languagedata() {
		$rtn = array();
		foreach ( $this->idlangs as $key ) {
			$elem_id = (int) AC()->param->get( 'idlang_' . $key );
			if ( ! empty( $elem_id ) ) {
				$rows = AC()->db->get_objectlist( 'SELECT lang,text FROM #__awocoupon_lang_text WHERE elem_id=' . $elem_id );
				foreach ( $rows as $row ) {
					if ( ! isset( $rtn[ $row->lang ] ) ) {
						$rtn[ $row->lang ] = new stdclass();
					}
					$rtn[ $row->lang ]->{$key} = $row->text;
				}
			}
		}

		return $rtn;
	}

	/**
	 * Store configuration
	 *
	 * @param array $data info to stor.
	 */
	public function store( $data ) {

		if ( isset( $data['params']['is_case_sensitive'] ) ) {
			$data['is_case_sensitive'] = $data['params']['is_case_sensitive'];
			unset( $data['params']['is_case_sensitive'] );
		}
		if ( ! empty( $data['params'] ) ) {
			$params = AC()->param;

			if ( ! isset( $data['params'][ AWOCOUPON_ESTORE . '_orderupdate_coupon_process' ] )
			|| ( is_array( $data['params'][ AWOCOUPON_ESTORE . '_orderupdate_coupon_process' ] ) && current( $data['params'][ AWOCOUPON_ESTORE . '_orderupdate_coupon_process' ] ) == '' )
			) {
				$data['params'][ AWOCOUPON_ESTORE . '_orderupdate_coupon_process' ] = '';
			}
			if ( ! isset( $data['params']['ordercancel_order_status'] ) ) {
				$data['params']['ordercancel_order_status'] = '';
			}

			// Store normal data.
			foreach ( $data['params'] as $name => $value ) {
				$params->set( $name, $value );
			}

			// Store language data.
			foreach ( $data['idlang'] as $iso => $langarray ) {
				foreach ( $langarray as $field => $value ) {
					if ( ! in_array( $field, $this->idlangs ) ) {
						continue;
					}

					$name = 'idlang_' . $field;
					$elem_id = AC()->lang->save_data( $params->get( $name ), $value, $iso );
					if ( ! empty( $elem_id ) ) {
						$params->set( $name, $elem_id );
					}
				}
			}
		}

		if ( isset( $data['is_case_sensitive'], $data['casesensitiveold'] )
		&& $data['is_case_sensitive'] != $data['casesensitiveold']
		&& ( 1 == $data['is_case_sensitive'] || 0 == $data['is_case_sensitive'] ) ) {
			$sql = 0 == $data['is_case_sensitive']
					? 'ALTER TABLE `#__awocoupon` MODIFY `coupon_code` VARCHAR(255) NOT NULL DEFAULT ""'
					: 'ALTER TABLE `#__awocoupon` MODIFY `coupon_code` VARCHAR(255) BINARY NOT NULL DEFAULT ""'
					;
			AC()->db->query( $sql );
		}

		return true;
	}

	/**
	 * Reset all awocoupon tables
	 */
	public function reset_tables() {
		$installer = AC()->helper->new_class( 'AwoCoupon_Helper_Install' );

		// Delete all.
		$rtn = $installer->run_sql_file( AWOCOUPON_DIR . '/helper/install/mysql.uninstall.sql' );

		// Install all.
		if ( $rtn ) {
			$rtn = $installer->run_sql_file( AWOCOUPON_DIR . '/helper/install/mysql.install.sql' );

		}

		return $rtn;
	}

}
