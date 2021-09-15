<?php
/**
 * Class DeactivationModal
 *
 * @package WooLiveChat\Services\Notifications
 */

namespace WooLiveChat\Services\Notifications;

use WooLiveChat\Services\Templates\DeactivationModalTemplate;

/**
 * Class DeactivationModal
 *
 * @package WooLiveChat\Services\Notifications
 */
class DeactivationModal extends Notification {
	/**
	 * DeactivationModal constructor.
	 *
	 * {@inheritDoc}
	 */
	public function __construct( $store, $options ) {
		parent::__construct( $store, $options, DeactivationModalTemplate::create(), 'current_screen', 'admin_footer' );
	}

	/**
	 * Returns true when plugin is connected.
	 *
	 * @inheritDoc
	 */
	public function should_render() {
		return $this->is_user_on_page( 'plugins' );
	}
}
