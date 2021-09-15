<?php
/**
 * Class WritableOption
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class WritableOption
 *
 * @package WooLiveChat\Services\Options
 */
class WritableOption extends ReadableOption {
	/**
	 * Sets value for an option.
	 *
	 * @param mixed $value Option value.
	 *
	 * @return bool
	 */
	public function set( $value ) {
		return update_option( $this->key, $value );
	}
}
