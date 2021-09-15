<?php
/**
 * Class AuthorizedUsers
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class AuthorizedUsers
 *
 * @package WooLiveChat\Services\Options
 */
class AuthorizedUsers extends WritableOption {
	/**
	 * AuthorizedUsers constructor.
	 *
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_authorized_users' );
	}

	/**
	 * Gets authorized user IDs.
	 *
	 * @return string[]|null
	 */
	public function get() {
		return explode( ',', parent::get() );
	}

	/**
	 * Sets authorized user IDs.
	 *
	 * @param string[] $authorized_users Array of authorized user IDs.
	 *
	 * @return bool
	 */
	public function set( $authorized_users ) {
		return parent::set( implode( ',', $authorized_users ) );
	}
}
