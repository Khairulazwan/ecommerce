<?php
/**
 * Class PublicKey
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class PublicKey
 *
 * @package WooLiveChat\Services\Options
 */
class PublicKey extends WritableOption {
	/**
	 * PublicKey constructor.
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_public_key' );
	}
}
