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
class AwoCoupon_Admin_Controller_Config extends AwoCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'AwoCoupon_Admin_Class_Config' );
	}

	/**
	 * Display list
	 **/
	public function show_default() {
		$cron = AC()->helper->new_class( 'AwoCoupon_Helper_Cron' );
		$cron_last_run = $cron->get_last_run();
		AC()->param->set( 'is_case_sensitive', AC()->coupon->is_case_sensitive() );

		$this->render( 'admin.view.config.edit', array(
			'orderstatuses' => AC()->store->get_order_status(),
			'cron_last_run' => ! empty( $cron_last_run ) ? $cron_last_run : '--',
			'language_data' => $this->model->get_languagedata(),
			'default_language' => AC()->lang->get_current(),
			'is_case_sensitive' => AC()->coupon->is_case_sensitive(),
		));
	}

	/**
	 * Save item
	 **/
	public function do_save() {
		$this->model->store( AC()->helper->get_request() );
		AC()->helper->redirect( 'dashboard' );
	}

	/**
	 * Apply save
	 **/
	public function do_apply() {
		$this->model->store( AC()->helper->get_request() );
	}

	/**
	 * Create button for yes/no on config page
	 *
	 * @param string $label the text.
	 * @param string $dbname the config name.
	 **/
	public function display_yes_no( $label, $dbname ) {
		$value = (int) AC()->param->get( $dbname, 0 );
		return '
			<tr><td class="key">' . $label . '</td>
				<td><div class="awcontrols">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" id="params' . $dbname . '_yes" name="params[' . $dbname . ']" value="1" ' . ( 1 == $value ? 'checked="checked"' : '' ) . ' />
							<label for="params' . $dbname . '_yes" >' . AC()->lang->__( 'Yes' ) . '</label>
							<input type="radio" id="params' . $dbname . '_no" name="params[' . $dbname . ']" value="0" ' . ( 1 != $value ? 'checked="checked"' : '' ) . ' />
							<label for="params' . $dbname . '_no" >' . AC()->lang->__( 'No' ) . '</label>
						</span>
					</div>		
				</td>
			</tr>
			<tr>
		';
	}

}
