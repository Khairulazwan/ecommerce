<?php
/**
 * Class Notification
 *
 * @package WooLiveChat\Services\Notifications
 */

namespace WooLiveChat\Services\Notifications;

use WooLiveChat\Services\Templates\Template;
use WooLiveChat\Services\Store;
use WooLiveChat\Services\Options\Deprecated\DeprecatedOptions;

/**
 * Class Notification
 *
 * @package WooLiveChat\Services\Notifications
 */
class Notification {
	/**
	 * WP Hook on which a Notification should be registered.
	 *
	 * @var string
	 */
	private $register_hook;

	/**
	 * WP Hook on which a Notification should be rendered.
	 *
	 * @var string
	 */
	private $render_hook;

	/**
	 * Instance of Store.
	 *
	 * @var Store
	 */
	protected $store;

	/**
	 * Instance of DeprecatedOptions.
	 *
	 * @var DeprecatedOptions|null
	 */
	protected $options;

	/**
	 * Instance of template that should be rendered.
	 *
	 * @var Template|null
	 */
	private $template;

	/**
	 * Notification constructor.
	 *
	 * @param Store                  $store         Store instance.
	 * @param DeprecatedOptions|null $options       DeprecatedOptions instance.
	 * @param Template|null          $template      Template instance.
	 * @param string                 $register_hook WP hook used for register.
	 * @param string                 $render_hook   WP hook used for render.
	 */
	public function __construct(
		$store,
		$options,
		$template = null,
		$register_hook = 'current_screen',
		$render_hook = 'admin_notices'
	) {
		$this->store         = $store;
		$this->options       = $options;
		$this->template      = $template;
		$this->register_hook = $register_hook;
		$this->render_hook   = $render_hook;
	}

	/**
	 * Returns render hook.
	 *
	 * @return string
	 */
	public function get_render_hook() {
		return $this->render_hook;
	}

	/**
	 * Returns register hook.
	 *
	 * @return string
	 */
	public function get_register_hook() {
		return $this->register_hook;
	}

	/**
	 * Allows to perform actions before rendering.
	 */
	public function register() {}

	/**
	 * Returns true if user is on given page.
	 *
	 * @param string $page_id ID of desired page.
	 * @return bool
	 */
	protected function is_user_on_page( $page_id ) {
		$screen = get_current_screen();
		return ! is_null( $screen ) ? $page_id === $screen->id : false;
	}

	/**
	 * Returns true if plugin was migrated from 3.X version.
	 *
	 * @return bool
	 */
	protected function was_migrated() {
		return max( 0, $this->options->license->get() ) > 0;
	}

	/**
	 * Returns true if a notification should be rendered.
	 *
	 * @return bool
	 */
	public function should_render() {
		return true;
	}

	/**
	 * Registers a notification.
	 */
	public function render() {
		if ( $this->should_render() ) {
			$this->template->render();
		}
	}

	/**
	 * Returns instance of Notification (singleton pattern).
	 *
	 * @return static
	 */
	public static function get_instance() {
		return new static( Store::get_instance(), DeprecatedOptions::get_instance() );
	}
}
