<?php
/**
 * Class PluginsConflictNotice
 *
 * @package WooLiveChat\Services\Notifications
 */

namespace WooLiveChat\Services\Notifications;

use WooLiveChat\Services\Templates\PluginsConflictNoticeTemplate;

/**
 * Class PluginsConflictNotice
 *
 * @package WooLiveChat\Services\Notifications
 */
class PluginsConflictNotice extends Notification {
	/**
	 * ConnectNotice constructor.
	 *
	 * @inheritDoc
	 */
	public function __construct( $store, $options ) {
		parent::__construct( $store, $options, PluginsConflictNoticeTemplate::create() );
	}
	/**
	 * Returns true when plugin is not connected,
	 * user is not on plugin's settings page,
	 * and plugin was migrated.
	 *
	 * @inheritDoc
	 */
	public function should_render() {
		return true;
	}
}
