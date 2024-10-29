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
 **/
class AwoCoupon_Admin_Controller_History extends AwoCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->cmodel = AC()->helper->new_class( 'AwoCoupon_Admin_Class_History_Coupon' );
	}

	/**
	 * Show default
	 **/
	public function show_default() {
		$this->render( 'admin.view.history.coupon', array(
			'table_html' => $this->cmodel->display_list(),
			'filter_function_type' => AC()->helper->get_userstate_request( 'com_awocoupon.coupons.filter_function_type', 'filter_function_type', '', 'cmd' ),
			'filter_coupon_value_type' => AC()->helper->get_userstate_request( $this->cmodel->name . '.coupon_value_type', 'filter_coupon_value_type', '' ),
			'filter_state' => AC()->helper->get_userstate_request( $this->cmodel->name . '.filter_state', 'filter_state', '' ),
			'filter_discount_type' => AC()->helper->get_userstate_request( $this->cmodel->name . '.discount_type', 'filter_discount_type', '' ),
			'filter_template' => AC()->helper->get_userstate_request( $this->cmodel->name . '.template', 'filter_template', '' ),
			'filter_tag' => AC()->helper->get_userstate_request( $this->cmodel->name . '.tag', 'filter_tag', '' ),
			'search' => AC()->helper->get_userstate_request( $this->cmodel->name . '.search', 'search', '' ),
			'search_type' => AC()->helper->get_userstate_request( $this->cmodel->name . '.search_type', 'search_type', '' ),
		) );
	}

	/**
	 * Show edit
	 **/
	public function show_edit() {
		$row = $this->cmodel->get_entry();

		$post = AC()->helper->get_request();
		if ( $post ) {
			$row = (object) array_merge( (array) $row, (array) $post );
		}

		$this->render( 'admin.view.history.couponedit', array(
			'row' => $row,
		) );
	}

	/**
	 * Save coupon
	 **/
	public function do_couponsave() {
		$errors = $this->cmodel->save( AC()->helper->get_request() );
		if ( empty( $errors ) ) {
			AC()->helper->redirect( 'history' );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

	/**
	 * Delete coupon
	 **/
	public function do_coupondelete() {
		$this->cmodel->delete( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'history' );
	}

	/**
	 * Delete coupon bulk
	 **/
	public function do_coupondeletebulk() {
		$this->cmodel->delete( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'history' );
	}

}

