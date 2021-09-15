<?php
/**
 * Class ReadableOption
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class ReadableOption
 *
 * @package WooLiveChat\Services\Options
 */
class ReadableOption extends Option {
	/**
	 * Returns option value.
	 *
	 * @return mixed
	 */
	public function get() {
		return get_option( $this->key, $this->fallback );
	}

	/**
	 * Removes an option.
	 *
	 * @return bool
	 */
	public function remove() {
		return delete_option( $this->key );
	}
}
