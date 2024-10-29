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
class AwoCoupon_Admin_Controller_Import extends AwoCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'AwoCoupon_Admin_Class_Import' );
	}

	/**
	 * Show default
	 **/
	public function show_default() {
		$this->render( 'admin.view.import.default', array(
			//'row' => $this->model->get_entry(),
		) );
	}

	public function do_save() {

		$lines = array();
		$file = AC()->helper->get_request( 'file', array(), 'file' );
		$store_none_errors = AC()->helper->get_request( 'store_none_errors' );
		
		if ( strtolower( substr( $file['name'], -4 ) ) == '.csv' ) {
			ini_set('auto_detect_line_endings',TRUE); //needed for mac users
			$handle = fopen( $file['tmp_name'], 'r' );
			if ( $handle !== FALSE ) {
				$delimiter = AC()->param->get( 'csvDelimiter', ',' );
				$keys = array();
				while ( ( $row = fgetcsv( $handle, 10000, $delimiter ) ) !== FALSE ) {
					if ( empty( $row ) ) {
						continue;
					} 
					if ( empty( $keys ) ) {
						$keys = $row;
						continue;
					}
					$lines[] = array_combine( $keys, $row );
				}
				fclose($handle);
			}
		}
		
		if ( empty( $lines ) ) {
			AC()->helper->set_message( 'Empty import file', 'error' );
			return;
		}

		$data = array(
			'store_none_errors' => $store_none_errors,
			'lines' => $lines,
		);
		$errors = $this->model->save( $data );
		if ( empty( $errors ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Item(s) imported' ) );
			AC()->helper->redirect( 'coupon' );
			return;
		}

		foreach ( $errors as $id=>$errarray ) {
			$errText = '<br /><div>ID: ' . $id . '<hr /></div>';
			foreach ( $errarray as $err ) {
				$errText .= '<div style="padding-left:20px;">-- ' . $err . '</div>';
			}
			AC()->helper->set_message( $errText, 'error' );
		}
	}

	public function do_export() {
		$data = $this->model->export( AC()->helper->get_request( 'coupon_ids', null ) );

		$filename = AC()->helper->get_request( 'filename', 'file.csv' );

		// Required for IE, otherwise Content-disposition is ignored.
		if ( ini_get( 'zlib.output_compression' ) ) {
			ini_set( 'zlib.output_compression', 'Off' );
		}

		// Default: $ctype="application/force-download";.
		header( 'Pragma: public' ); // Required.
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false ); // Required for certain browsers.
		header( 'Content-Type: application/vnd.ms-excel' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . strlen( $data ) );
		echo $data;
		exit();
	}
}

