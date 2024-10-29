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

class AwoCoupon_Helper_Language {

	public function get_languages() {
		static $languages = array();
		if ( ! empty( $languages ) ) {
			return $languages;
		}
		if ( is_plugin_active( 'polylang/polylang.php' ) ) {
			//plugin is activated
			if ( ! class_exists( 'PLL_Model' ) ) {
				require dirname( AWOCOUPON_DIR ) . '/polylang/includes/model.php';
			}
			$options = array();
			$class = new PLL_Model( $options );
			$languages = $class->get_languages_list();
		}

		if ( empty( $languages ) ) {
			$languages[] = (object) array(
				'locale' => 'en_US',
				'name' => 'English',
			);
		}
		return $languages;
	}

	public function write_fields( $type, $field, $data, $params = null ) {
		$languages = $this->get_languages();

		$r = '';

		$editor_html_1 = '';
		$editor_html_2 = '';
		$editor_html_3 = '';
		$editor_html_4 = '';
		$editor_html_5 = '';
		if ( 'editor' == $type ) {
			$editor_html_1 = 'style="width:100%;margin-right:-100px;float:left;"';
			$editor_html_2 = '<div style="margin-right:104px;">';
			$editor_html_3 = '</div>';
			$editor_html_4 = 'style="float:left;width:100px;"';
			$editor_html_5 = '<div class="clear"></div>';
		}
		foreach ( $languages as $language ) {
			if ( count( $languages ) > 1 ) {
				$r .= '
					<div class="translatable-field lang-' . $language->locale . '">
						<div class="col-lg-9" ' . $editor_html_1 . '>' . $editor_html_2 . '
				';
			}
			if ( 'text' == $type ) {
				$r .= '
							<input type="text" 
								name="idlang[' . $language->locale . '][' . ( isset( $params['name'] ) ? $params['name'] : $field ) . ']"
								class="inputbox ' . ( isset( $params['class'] ) ? $params['class'] : '' ) . '"
								' . ( isset( $params['style'] ) ? 'style="' . $params['style'] . '"' : '' ) . '
								value="' . ( isset( $data[ $language->locale ]->{$field} ) ? $data[ $language->locale ]->{$field} : '' ) . '" />
				';
			} elseif ( 'editor' == $type ) {
				$name = isset( $params['name'] ) ? $params['name'] : $field;
				$settings = array(
					'textarea_name' => 'idlang[' . $language->locale . '][' . $name . ']',
				);
				if ( isset( $params['rows'] ) ) {
					$settings['textarea_rows'] = $params['rows'];
				}
				if ( isset( $params['editor_height'] ) ) {
					$settings['editor_height'] = $params['editor_height'];
				}

				$content = isset( $data[ $language->locale ]->{$field} ) ? $data[ $language->locale ]->{$field} : '';
				$r .= AC()->helper->get_editor( $content, 'idlang_' . $language->locale . '_' . ( isset( $params['id'] ) ? $params['id'] : $name ), $settings );
			} elseif ( 'textarea' == $type ) {
				$r .= '
							<textarea 
								name="idlang[' . $language->locale . '][' . ( isset( $params['name'] ) ? $params['name'] : $field ) . ']"
								' . ( isset( $params['class'] ) ? 'class="' . $params['class'] . '"' : '' ) . '
								' . ( isset( $params['style'] ) ? 'style="' . $params['style'] . '"' : '' ) . '
								' . ( isset( $params['rows'] ) ? 'rows="' . $params['rows'] . '"' : '' ) . '
								' . ( isset( $params['cols'] ) ? 'cols="' . $params['cols'] . '"' : '' ) . '
								>' . ( isset( $data[ $language->locale ]->{$field} ) ? $data[ $language->locale ]->{$field} : '' ) . '</textarea>
				';
			}
			if ( ! empty( $params['after_text'] ) ) {
				$r .= $params['after_text'];
			}
			if ( count( $languages ) > 1 ) {
				$r .= '
						' . $editor_html_3 . '
						</div>
						<div class="col-lg-2" ' . $editor_html_4 . '>
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
								' . $language->locale . '
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu language">
				';
				foreach ( $languages as $language2 ) {
					$r .= '
								<li><a href="javascript:hideOtherLanguage(\'' . $language2->locale . '\');">' . $language2->name . '</a></li>
					';
				}
				$r .= '
							</ul>
						</div>
					</div>
					' . $editor_html_5 . '
				';
			}
		}
		return $r;
	}

	public function get_current() {
		return get_locale();
	}

	public function trx( $text ) {
		return __( $text, 'awocoupon' );
	}

	public function __( $text ) {
		return __( $text, 'awocoupon' );
	}

	public function _e_valid( $text ) {
		return sprintf( AC()->lang->__( '%1$s: enter a valid value' ), $text );
	}

	public function _e_select( $text ) {
		return sprintf( AC()->lang->__( '%1$s: make a selection' ), $text );
	}

	public function get_user_lang( $user_id = 0, $can_be_anonymous = false ) {
		$languages = array();
		$user_id = (int) $user_id;

		if ( ! is_admin() ) {
			$languages[] = get_locale(); // current front end language
		}

		if ( empty( $user_id ) ) {
			if ( ! $can_be_anonymous ) {
				$languages[] = get_user_locale();
			}
		} else {
			$languages[] = get_user_locale( $user_id );
		}

		$languages[] = get_locale();
		$languages[] = 'en_US';

		return array_unique( $languages );
	}

	public function get_data( $elem_id, $user_id = 0, $default = null, $can_be_anonymous = false ) {
		$elem_id = (int) $elem_id;
		if ( empty( $elem_id ) ) {
			return;
		}

		static $stored_languages;
		if ( ! isset( $stored_languages[ $user_id ] ) ) {
			$stored_languages[ $user_id ] = $this->get_user_lang( $user_id, $can_be_anonymous );
		}

		$languages = implode( '","', $stored_languages[ $user_id ] );
		$text = AC()->db->get_value( 'SELECT text FROM #__awocoupon_lang_text WHERE elem_id=' . $elem_id . ' AND lang IN ("' . $languages . '") ORDER BY FIELD(lang,"' . $languages . '") LIMIT 1' );

		return ! empty( $text ) ? $text : $default;
	}

	public function save_data( $elem_id, $text, $lang = null ) {
		$elem_id = (int) $elem_id;

		$text = AC()->db->escape( $text );

		if ( empty( $lang ) ) {
			$lang = $this->get_current();
		}

		if ( empty( $text ) && ! empty( $elem_id ) ) {
			// delete the data from db
			AC()->db->query( 'DELETE FROM #__awocoupon_lang_text WHERE elem_id=' . (int) $elem_id . ' AND lang="' . $lang . '"' );
			return;
		}

		if ( empty( $elem_id ) ) {
			if ( empty( $text ) ) {
				return;
			}

			$elem_id = (int) AC()->db->get_value( 'SELECT MAX(elem_id) FROM #__awocoupon_lang_text' );
			$elem_id++;
		}

		AC()->db->query( 'INSERT INTO #__awocoupon_lang_text (elem_id,lang,text) VALUES (' . $elem_id . ',"' . $lang . '","' . $text . '") ON DUPLICATE KEY UPDATE text="' . $text . '"' );

		return $elem_id;
	}

}
