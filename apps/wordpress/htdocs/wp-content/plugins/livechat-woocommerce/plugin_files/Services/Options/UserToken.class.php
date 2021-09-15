<?php
/**
 * Class UserToken
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class UserToken
 *
 * @package WooLiveChat\Services\Options
 */
class UserToken extends UserOption {
	/**
	 * UserToken constructor.
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_user_%s_token' );
	}
}
