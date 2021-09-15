<?php
/**
 * Class UserOption
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class UserOption
 *
 * @package WooLiveChat\Services\Options
 */
class UserOption extends Option {
	/**
	 * Returns JWt for given user.
	 *
	 * @param mixed $id User ID.
	 * @return string
	 */
	public function get( $id ) {
		return get_option( sprintf( $this->key, $id ), $this->fallback );
	}

	/**
	 * Removes JWt for given user.
	 *
	 * @param mixed $id User ID.
	 * @return string
	 */
	public function remove( $id ) {
		return delete_option( sprintf( $this->key, $id ) );
	}

	/**
	 * Returns JWt for given user.
	 *
	 * @param mixed  $id    User ID.
	 * @param string $value JWT.
	 * @return string
	 */
	public function set( $id, $value ) {
		return update_option( sprintf( $this->key, $id ), $value );
	}
}
