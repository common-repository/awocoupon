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

class AwoCoupon_Helper_Install {
	
	public static function start_install() {
		$installer = new AwoCoupon_Helper_Install();
		$installer->install();
	}

	public static function start_uninstall() {
		$installer = new AwoCoupon_Helper_Install();
		$installer->uninstall();
	}

	public static function start_disable() {
		$installer = new AwoCoupon_Helper_Install();
		$installer->disable();
	}
	

	/** @var array DB updates and callbacks that need to be run per version */
	private $db_updates = array(
	);

	/** @var object Background update class */
	private static $background_updater;


	/**
	 * Install WC.
	 */
	public function install() {
		// called on activate
		global $wpdb;
		if ( ! is_blog_installed() ) {
			return;
		}

		AC()->init();

		# create_tables
		$table_name = $wpdb->prefix . 'awocoupon';
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name . '"' ) != $table_name ) {

			// awocoupon table not installed
			$this->run_sql_file( AWOCOUPON_DIR . '/helper/install/mysql.install.sql' );
		}

		{ # cron jobs
			wp_clear_scheduled_hook( 'awocoupon_cron_action' );
			wp_schedule_event( time(), 'hourly', 'awocoupon_cron_action' );
		}

		// Queue upgrades/setup wizard
		$current_version    = get_option( 'awocoupon_version', null );
		$current_db_version    = get_option( 'awocoupon_db_version', null );

		// No versions? This is a new install :)
		if ( is_null( $current_version ) && is_null( $current_db_version ) ) {
		}

		if ( ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( $this->db_updates ) ), '<' ) ) {
			$this->update();
		} else {
			$this->update_db_version();
		}

		delete_option( 'awocoupon_version' );
		add_option( 'awocoupon_version', AC()->version );
	}

	public function disable() {
		// called on deactivate
		AC()->init();
		wp_clear_scheduled_hook( 'awocoupon_cron_action' );
	}

	public function uninstall() {
		global $wpdb;
		$this->disable();

		# drop tables
		$this->run_sql_file( AWOCOUPON_DIR . '/helper/install/mysql.uninstall.sql' );

		delete_option( 'awocoupon_version' );
		delete_option( 'awocoupon_db_version' );
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private function update() {
		$current_db_version = get_option( 'awocoupon_db_version' );

		$upgrader = AC()->helper->new_class( 'AwoCoupon_Helper_Install_Update' );
		foreach ( $this->db_updates as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$upgrader->{$update_callback}();
				}
			}
		}
	}

	/**
	 * Update DB version to current.
	 * @param string $version
	 */
	public function update_db_version( $version = null ) {
		delete_option( 'awocoupon_db_version' );
		add_option( 'awocoupon_db_version', is_null( $version ) ? AWOCOUPON_VERSION : $version );
	}

	/**
	 * Execute the sql file
	 *
	 * @param string $sqlfile filename.
	 */
	protected function run_sql_file( $sqlfile ) {
		$buffer = file_get_contents( $sqlfile );
		if ( false !== $buffer ) {
			$queries = $this->split_sql( $buffer );
			if ( count( $queries ) != 0 ) {
				foreach ( $queries as $query ) {
					$query = trim( $query );
					if ( '' !== $query && substr( trim( $query ), 0, 1 ) !== '#' ) {
						AC()->db->query( $query );
					}
				}
			}
		}
		return true;
	}

	/**
	 * Split sql statements
	 *
	 * @param string $sql the statements.
	 */
	protected function split_sql( $sql ) {

		$start = 0;
		$open = false;
		$comment = false;
		$end_string = '';
		$end = strlen( $sql );
		$queries = array();
		$query = '';

		for ( $i = 0; $i < $end; $i++ ) {
			$current = substr( $sql, $i, 1 );
			$current2 = substr( $sql, $i, 2 );
			$current3 = substr( $sql, $i, 3 );
			$len_end_string = strlen( $end_string );
			$test_end = substr( $sql, $i, $len_end_string );

			if ( '"' == $current || "'" == $current || '--' == $current2
				|| ( '/*' == $current2 && '/*!' != $current3 && '/*+' != $current3 )
				|| ( '#' == $current && '#__' != $current3 )
				|| ($comment && $test_end == $end_string ) ) {
				// Check if quoted with previous backslash.
				$n = 2;

				while ( substr( $sql, $i - $n + 1, 1 ) == '\\' && $n < $i ) {
					$n++;
				}

				// Not quoted.
				if ( 0 == ( $n % 2 ) ) {
					if ( $open ) {
						if ( $test_end == $end_string ) {
							if ( $comment ) {
								$comment = false;
								if ( $len_end_string > 1 ) {
									$i += ( $len_end_string - 1 );
									$current = substr( $sql, $i, 1 );
								}
								$start = $i + 1;
							}
							$open = false;
							$end_string = '';
						}
					} else {
						$open = true;
						if ( '--' == $current2 ) {
							$end_string = "\n";
							$comment = true;
						} elseif ( '/*' == $current2 ) {
							$end_string = '*/';
							$comment = true;
						} elseif ( '#' == $current ) {
							$end_string = "\n";
							$comment = true;
						} else {
							$end_string = $current;
						}
						if ( $comment && $start < $i ) {
							$query = $query . substr( $sql, $start, ( $i - $start ) );
						}
					}
				}
			}

			if ( $comment ) {
				$start = $i + 1;
			}

			if ( ( ';' == $current && ! $open ) || $i == $end - 1 ) {
				if ( $start <= $i ) {
					$query = $query . substr( $sql, $start, ( $i - $start + 1 ) );
				}
				$query = trim( $query );

				if ( $query ) {
					if ( ( $i == $end - 1 ) && ( ';' != $current ) ) {
						$query = $query . ';';
					}
					$queries[] = $query;
				}

				$query = '';
				$start = $i + 1;
			}
		}

		return $queries;
	}

}

