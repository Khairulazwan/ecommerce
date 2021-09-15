<?php
/**
 * Class NotificationsRenderer
 *
 * @package WooLiveChat\Services
 */

namespace WooLiveChat\Services;

use WooLiveChat\Services\Notifications\ConfirmIdentityNotice;
use WooLiveChat\Services\Notifications\ConnectNotice;
use WooLiveChat\Services\Notifications\DeactivationModal;
use WooLiveChat\Services\Notifications\Notification;
use WooLiveChat\Services\Notifications\ReviewNotice;
use WooLiveChat\Services\Notifications\PluginsConflictNotice;

/**
 * Class NotificationsRenderer
 *
 * @package WooLiveChat\Services
 */
class NotificationsRenderer {
	/**
	 * Array of Notifications.
	 *
	 * @var Notification[]
	 */
	private $notifications;

	/**
	 * NotificationsRenderer constructor.
	 *
	 * @param Notification[] $notifications Array of Notifications.
	 */
	public function __construct( $notifications ) {
		$this->notifications = $notifications;
	}

	/**
	 * Initializes modals and notices.
	 */
	public function init() {
		foreach ( $this->notifications as $notification ) {
			add_action( $notification->get_register_hook(), array( $notification, 'register' ) );
			add_action( $notification->get_render_hook(), array( $notification, 'render' ) );
		}
	}

	/**
	 * Returns instance of NotificationsRenderer (singleton pattern).
	 *
	 * @return NotificationsRenderer
	 */
	public static function get_instance() {
		return new static(
			array(
				ConnectNotice::get_instance(),
				ConfirmIdentityNotice::get_instance(),
				DeactivationModal::get_instance(),
				ReviewNotice::get_instance(),
			)
		);
	}


	/**
	 * Returns instance of NotificationsRenderer (singleton pattern) when plugins are in conflict.
	 *
	 * @return NotificationsRenderer
	 */
	public static function get_instance_for_plugins_conflict() {
		return new static( array( PluginsConflictNotice::get_instance() ) );
	}
}
