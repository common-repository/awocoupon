<?php
/**
 * Plugin Name: AwoCoupon
 * Plugin URI: http://awodev.com/
 * Description: Coupons enhanced
 * Version: 3.1.8
 * Author: Seyi Awofadeju
 * Author URI: http://awodev.com
 * Requires at least: 4.8
 *
 * Text Domain: awocoupon
 * Domain Path: /media/language/
 *
 * @package AwoCoupon
 * @category Core
 * @author Seyi Awofadeju
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * AwoCoupon class
 */
final class AwoCoupon {

	/**
	 * AwoCoupon version
	 *
	 * @var string
	 */
	public $version = '3.1.8';

	/**
	 * Create instance of class
	 *
	 * @var AwoCoupon
	 */
	protected static $_instance = null;

	/**
	 * Class for database helper
	 *
	 * @var AwoCoupon_Helper_Db
	 */
	public $db = null;
	/**
	 * Class for global helper
	 *
	 * @var AwoCoupon_Helper_Helper
	 */
	public $helper = null;
	/**
	 * Class for awocoupon global parameters
	 *
	 * @var AwoCoupon_Helper_Param
	 */
	public $param = null;
	/**
	 * Class for store global helper
	 *
	 * @var AwoCoupon_Helper_Estore_[store]_Helper
	 */
	public $store = null;
	/**
	 * Class for store currency helper
	 *
	 * @var AwoCoupon_Helper_Estore_[store]_Currency
	 */
	public $storecurrency = null;
	/**
	 * Class for store coupon helper
	 *
	 * @var AwoCoupon_Helper_Estore_[store]_Coupon
	 */
	public $storecoupon = null;
	/**
	 * Calss for global coupon helper.
	 *
	 * @var AwoCoupon_Helper_Coupon
	 */
	public $coupon = null;


	/**
	 * Create instance of class
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Construct.
	 */
	public function __construct() {
		$this->define( '_AWO_', 1 );

		if ( ! class_exists( 'AwoCoupon_Helper_Helper' ) ) {
			require dirname( __FILE__ ) . '/helper/class-awocoupon-helper-helper.php';
		}
		$this->helper = new AwoCoupon_Helper_Helper();
		add_action( 'init', array( $this, 'init' ), 0 );

		if ( ! class_exists( 'AwoCoupon_Helper_Hook' ) ) {
			require dirname( __FILE__ ) . '/helper/class-awocoupon-helper-hook.php';
		}
		$hook = new AwoCoupon_Helper_Hook();
		$hook->register( __FILE__ );
	}

	/**
	 * Init AwoCoupon when WordPress Initialises.
	 */
	public function init() {

		// Define constants.
		$this->define( 'AWOCOUPON_PLUGIN_FILE', __FILE__ );
		$this->define( 'AWOCOUPON_DIR', dirname( __FILE__ ) );
		$this->define( 'AWOCOUPON_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'AWOCOUPON_VERSION', $this->version );
		$this->define( 'AWOCOUPON_ESTORE', 'woocommerce' );
		$this->define( 'AWOCOUPON_ASEET_URL', $this->plugin_url() . '/media/assets' );

		// Add includes.
		$this->helper->add_class( 'AwoCoupon_Library_Class' );
		$this->helper->add_class( 'AwoCoupon_Library_Controller' );

		if ( $this->is_request( 'admin' ) ) {
			$this->helper->add_class( 'AwoCoupon_Admin_Admin' );
		} else {
		}

		// Set up localisation.
		load_plugin_textdomain( 'awocoupon', false, AWOCOUPON_DIR . '/media/languages/' );

		// Define variables.

		$this->db = $this->helper->new_class( 'AwoCoupon_Helper_Database' );
		$this->param = $this->helper->new_class( 'Awocoupon_Helper_Param' );
		$this->lang = $this->helper->new_class( 'AwoCoupon_Helper_Language' );
		$this->coupon = $this->helper->new_class( 'AwoCoupon_Helper_Coupon' );

		$this->store = $this->helper->new_class( 'Awocoupon_Helper_Estore_Woocommerce_Helper' );
		$this->storecurrency = $this->helper->new_class( 'AwoCoupon_Helper_Estore_Woocommerce_Currency' );
		$this->storecoupon = $this->helper->new_class( 'AwoCoupon_Helper_Estore_Woocommerce_Coupon' );

	}

	/**
	 * Define constants if they do not exist
	 *
	 * @param string $name the constant.
	 * @param string $value the value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Check the page request
	 *
	 * @param string $type admin, ajax, cron, frontend.
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ! is_admin() && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Get ajax url
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php?action=awocoupon_ajax_call', 'relative' );
	}

	/**
	 * Get plugin url
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}


	/**
	 * Get admin url
	 */
	public function admin_url() {
		return admin_url( 'admin.php?page=awocoupon' );
	}

	/**
	 * Get post url
	 */
	public function post_url() {
		return admin_url( 'post.php' );
	}

}

AwoCoupon::instance();
