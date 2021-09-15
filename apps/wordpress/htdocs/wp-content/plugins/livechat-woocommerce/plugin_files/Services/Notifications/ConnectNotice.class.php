<?php
/**
 * Class ConnectNotice
 *
 * @package WooLiveChat\Services\Notifications
 */

namespace WooLiveChat\Services\Notifications;

use WooLiveChat\Services\Templates\ConnectNoticeTemplate;

/**
 * Class ConnectNotice
 *
 * @package WooLiveChat\Services\Notifications
 */
class ConnectNotice extends Notification {
	/**
	 * ConnectNotice constructor.
	 *
	 * @inheritDoc
	 */
	public function __construct( $store, $options ) {
		parent::__construct( $store, $options, ConnectNoticeTemplate::create() );
	}

	/**
	 * Returns true when plugin is not installed,
	 * user is not on plugin's settings page,
	 * and plugin wasn't migrated.
	 *
	 * @inheritDoc
	 */
	public function should_render() {
		return ! $this->store->is_connected() &&
				! $this->is_user_on_page( 'livechat_page_livechat_settings' ) &&
				! $this->was_migrated();
	}
}
