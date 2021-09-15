<?php
/**
 * Class DeprecatedWidgetSettings
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */

namespace WooLiveChat\Services\Options\Deprecated;

use WooLiveChat\Services\Options\ReadableOption;

/**
 * Class DeprecatedWidgetSettings
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */
class DeprecatedWidgetSettings extends ReadableOption {
	/**
	 * DeprecatedWidgetSettings constructor.
	 */
	public function __construct() {
		parent::__construct( 'wc-lc_customDataSettings' );
	}

	/**
	 * Returns array of settings.
	 *
	 * @return array
	 */
	public function get() {
		$settings = parent::get();

		if ( ! is_array( $settings ) ) {
			return null;
		}

		return $this->extract_widget_options( $settings );
	}

	/**
	 * Returns deprecated widget settings.
	 *
	 * @param array $settings Array with legacy settings.
	 *
	 * @return array
	 */
	public function extract_widget_options( $settings ) {
		$options = null;

		if ( array_key_exists( 'wc-lc_disableGuest', $settings ) ) {
			$options['hideForGuests'] = $settings['wc-lc_disableGuest'];
		}

		if ( array_key_exists( 'wc-lc_disableMobile', $settings ) ) {
			$options['hideOnMobile'] = $settings['wc-lc_disableMobile'];
		}

		return $options;
	}
}
