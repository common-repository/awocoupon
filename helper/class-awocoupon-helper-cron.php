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

class AwoCoupon_Helper_Cron {

	public static function process() {
		$instance = new AwoCoupon_Helper_Cron();
		$instance->run();
	}

	public static function get_last_run() {
		$instance = new AwoCoupon_Helper_Cron();
		return $instance->last_run();
	}

	public function __construct() {
		$this->params = AC()->param;
		$this->estore = AWOCOUPON_ESTORE;
		$this->cron_file = 'awocoupon_cron';
		$this->cron_path = AWOCOUPON_DIR . '/tmp';
		$this->cron_default_minutes = 30;
		$this->previous_runtime = 0;
	}

	public function run() {
		if ( ! $this->is_time_to_run() ) {
			return;
		}
		$this->check_expired();

		$this->write_file();
	}

	private function check_expired() {
		$days_expired = AC()->param->get( 'delete_expired', '' );
		if ( empty( $days_expired ) || ! ctype_digit( $days_expired ) ) {
			return;
		}

		$current_date = date( 'Y-m-d H:i:s', strtotime( '-' . $days_expired . ' days' ) );
		$list = AC()->db->get_column( 'SELECT id FROM #__awocoupon WHERE expiration<"' . $current_date . '"' );
		if ( empty( $list ) ) {
			return;
		}

		AC()->helper->add_class( 'AwoCoupon_Library_Class' );
		$class = AC()->helper->new_class( 'AwoCoupon_Admin_Class_Coupon' );
		$class->delete( $list );
	}

	private function mark_processed( $coupon_id, $user_id, $type, $status, $notes ) {
		AC()->db->query( '
			INSERT INTO #__awocoupon_cron (coupon_id,user_id,type,status,notes)
			VALUES (' . (int) $coupon_id . ',' . (int) $user_id . ',"' . $type . '","' . $status . '","' . $notes . '")
		' );
	}

	private function is_time_to_run() {
		if ( (int) $this->params->get( 'cron_enable', 0 ) != 1 ) {
			return false;
		}
		return true;

		/*
		$files = glob($this->cron_path.'/'.$this->cron_file.'.*');
		$file = array_pop($files);

		if( ! empty($file) ) {
			if( ! is_writeable($file) ) return false;
		}
		else {
		// touch a test file to make sure can write to directory
			$tmp_file = 'awocoupon_test_file_'.time();
			if( ! touch($this->cron_path.'/'.$tmp_file) ) return false;
			unlink($this->cron_path.'/'.$tmp_file);
		}

		$time = time();
		$this->previous_runtime = empty($file) ? $time-1 : substr($file,-10,10);

		if ($time <= $this->previous_runtime) return false;

		return true;
		*/
	}

	private function write_file() {

		$time_interval = (int) $this->params->get( 'cron_minutes', $this->cron_default_minutes );
		if ( $time_interval < 1 ) {
			$time_interval = $this->cron_default_minutes;
		}
		$time_interval *= 60;

		$files = glob( $this->cron_path . '/' . $this->cron_file . '.*' );

		// delete any old files
		foreach ( $files as $file ) {
			unlink( $file );
		}

		//create new runtime
		$time = time();
		$runtime = $this->previous_runtime;
		while ( $runtime < $time ) {
			$runtime += $time_interval;
		}
		//if( ! touch($this->cron_path.'/'.$this->cron_file.'.'.$runtime)) return;
		if ( file_put_contents( $this->cron_path . '/' . $this->cron_file . '.' . $runtime, time() ) === false ) {
			return;
		}
	}

	private function last_run() {
		$files = glob( $this->cron_path . '/' . $this->cron_file . '.*' );

		$file = array_pop( $files );
		if ( empty( $file ) ) {
			return;
		}

		$lastrun = (int) file_get_contents( $file );
		if ( empty( $lastrun ) ) {
			return;
		}

		return AC()->helper->get_date( $lastrun, 'Y-m-d H:i:s' );
	}

}
