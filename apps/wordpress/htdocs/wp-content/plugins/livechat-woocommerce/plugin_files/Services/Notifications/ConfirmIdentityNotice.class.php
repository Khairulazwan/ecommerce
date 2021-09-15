<?php
/**
 * Class ConfirmIdentityNotice
 *
 * @package WooLiveChat\Services\Notifications
 */

namespace WooLiveChat\Services\Notifications;

use WooLiveChat\Services\Templates\ConfirmIdentityNoticeTemplate;

/**
 * Class ConfirmIdentityNotice
 *
 * @package WooLiveChat\Services\Notifications
 */
class ConfirmIdentityNotice extends Notification {
	/**
	 * ConnectNotice constructor.
	 *
	 * @inheritDoc
	 */
	public function __construct( $store, $options ) {
		parent::__construct( $store, $options, ConfirmIdentityNoticeTemplate::create() );
	}
	/**
	 * Returns true when plugin is not connected,
	 * user is not on plugin's settings page,
	 * and plugin was migrated.
	 *
	 * @inheritDoc
	 */
	public function should_render() {
		return ! $this->store->is_connected() &&
				! $this->is_user_on_page( 'livechat_page_livechat_settings' ) &&
				$this->was_migrated();
	}
}
