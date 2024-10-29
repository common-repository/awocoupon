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
class AwoCoupon_Admin_Menu {

	/**
	 * Generate menu
	 **/
	public function process() {
		$this->define_menu();
		$this->define_plugin_menu();
		return $this->print_menu();
	}

	/**
	 * Define each menu item
	 **/
	public function define_menu() {

		$this->my_admin_link = AwoCoupon::instance()->admin_url() . '#';

		$img_path = AWOCOUPON_ASEET_URL . '/images';
		$this->menu_items = array(
			array(
				AC()->lang->__( 'AwoCoupon' ),
				$this->my_admin_link . '/',
				$img_path . '/awocoupon-small.png',
				array(
					array( AC()->lang->__( 'Dashboard' ), $this->my_admin_link . '/', $img_path . '/icon-16-home.png' ),
					array( AC()->lang->__( 'Configuration' ), $this->my_admin_link . '/config', $img_path . '/icon-16-config.png' ),
					array( AC()->lang->__( 'About' ), $this->my_admin_link . '/about', $img_path . '/icon-16-info.png' ),
				),
			),
			array(
				AC()->lang->__( 'Coupons' ),
				$this->my_admin_link . '/coupon',
				$img_path . '/icon-16-coupons.png',
				array(
					array( AC()->lang->__( 'New Coupon' ), $this->my_admin_link . '/coupon/edit', $img_path . '/icon-16-new.png' ),
					array( AC()->lang->__( 'Coupons' ), $this->my_admin_link . '/coupon', $img_path . '/icon-16-list.png' ),
					array( AC()->lang->__( 'Automatic Discounts' ), $this->my_admin_link . '/couponauto', $img_path . '/icon-16-auto.png' ),
					array( AC()->lang->__( 'Generate Coupons' ), $this->my_admin_link . '/coupon/generate', $img_path . '/icon-16-copy.png' ),
					array( AC()->lang->__( 'Import' ), $this->my_admin_link . '/import', $img_path.'/icon-16-import.png' ),
				),
			),
			array(
				AC()->lang->__( 'History of Uses' ),
				$this->my_admin_link . '/history',
				$img_path . '/icon-16-history.png',
				array(
					array( AC()->lang->__( 'Coupons' ), $this->my_admin_link . '/history', $img_path . '/icon-16-coupons.png' ),
				),
			),
		);
	}

	/**
	 * Extensions can add heir menu
	 **/
	public function define_plugin_menu() {

		$files = array();
		foreach ( $files as $class => $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
				$this->menu_items[] = call_user_func( array( $class, 'define_menu' ) );
			}
		}
	}

	/**
	 * Print menu
	 **/
	private function print_menu() {

		// Get all the urls into an array.
		$menu_urls = array();
		foreach ( $this->menu_items as $item ) {
			if ( ! empty( $item[1] ) ) {
				$menu_urls[] = $item[1];
			}
			if ( ! empty( $item[3] ) && is_array( $item[3] ) ) {
				foreach ( $item[3] as $item2 ) {
					if ( ! empty( $item2[1] ) ) {
						$menu_urls[] = $item2[1];
					}
					if ( ! empty( $item2[3] ) && is_array( $item2[3] ) ) {
						foreach ( $item2[3] as $item3 ) {
							if ( ! empty( $item3[1] ) ) {
								$menu_urls[] = $item3[1];
							}
						}
					}
				}
			}
		}

		// Set current url.
		$current_url = '';

		// Process.
		$html_menu = '
			<div id="awomenu">
				<div id="awomenu_container">
					<div class="navbar">
						<div class="navbar-inner">
							<ul id="" class="nav" >
		';

		foreach ( $this->menu_items as $item ) {
			if ( empty( $item ) ) {
				continue;
			}
			$is_active_1 = false;
			$html_menu_2 = '';
			if ( ! empty( $item[3] ) && is_array( $item[3] ) ) {
				$html_menu_2 = '<ul class="dropdown-menu">';
				foreach ( $item[3] as $item2 ) {
					if ( empty( $item2 ) ) {
						continue;
					}
					if ( ! empty( $item2[1] ) && $current_url == $item2[1] ) {
						$is_active_1 = true;
					}
					$is_active_2 = false;
					$html_menu_3 = '';
					if ( ! empty( $item2[3] ) && is_array( $item2[3] ) ) {
						$html_menu_3 = '<ul>';
						foreach ( $item2[3] as $item3 ) {
							if ( empty( $item3 ) ) {
								continue;
							}
							if ( ! empty( $item3[1] ) && $current_url == $item3[1] ) {
								$is_active_2 = true;
							}
							$html_menu_3 .= $this->print_menu_helper( $item3, 3, $current_url ) . '</li>';
						}
						$html_menu_3 .= '</ul>';
					}
					$html_menu_2 .= $this->print_menu_helper( $item2, 2, $current_url, $is_active_2 ) . $html_menu_3 . '</li>';
				}
				$html_menu_2 .= '</ul>';
			}
			$html_menu .= $this->print_menu_helper( $item, 1, $current_url, $is_active_1 ) . $html_menu_2 . '</li>';
		}
		$refresh_html = '<div style="display:block;float:right;padding-top:10px;"><a href="javascript:refreshPage();"><img src="' . AWOCOUPON_ASEET_URL . '/images/refresh.png" style="height:24px;"></a></div></div></div>';
		$html_menu .= '</ul>' . $refresh_html . '</div></div><div class="clr"></div>';

		return $html_menu;
	}

	/**
	 * Print each menu item
	 *
	 * @param array   $item menu item.
	 * @param int     $level the level.
	 * @param string  $current_url the current url.
	 * @param boolean $force_active if item is active.
	 **/
	private function print_menu_helper( $item, $level, $current_url, $force_active = false ) {
		$html = '';
		$image = '';
		$a_class = '';
		if ( ! empty( $item[2] ) ) {
			if ( 'class:' == substr( $item[2], 0, 6 ) ) {
				$a_class = substr( $item[2], 6 );
			} else {
				$image = '<img src="' . $item[2] . '" class="tmb"/>';
			}
		} else {
			$image = '<div style="display:inline-block;width:16px;">&nbsp;</div>';
		}

		$active_css = $force_active || ( ! empty( $item[1] ) && $current_url == $item[1] ) ? 'current' : '';

		$html .= '<li class="';
		if ( 1 == $level ) {
			$html .= ' dropdown ';
		} else {
			if ( '--separator--' == $item[0] ) {
				$html .= ' divider ';
			}
		}
		$html .= $active_css;
		$html .= '">';

		if ( '--separator--' != $item[0] ) {
			$html .= '<a class="';
			$html .= '" ';
			$html .= ' href="' . ( ! empty( $item[1] ) ? $item[1] : '#' ) . '"';
			$html .= '>' . $image . ' ' . $item[0] . '</a>';
		} else {
			$html .= '<span></span>';
		}
		return $html;
	}
}

