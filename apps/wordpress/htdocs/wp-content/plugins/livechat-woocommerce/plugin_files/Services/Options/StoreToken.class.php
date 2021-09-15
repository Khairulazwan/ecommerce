<?php
/**
 * Class StoreToken
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class StoreToken
 *
 * @package WooLiveChat\Services\Options
 */
class StoreToken extends WritableOption {
	/**
	 * StoreToken constructor.
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_store_token' );
	}
}
