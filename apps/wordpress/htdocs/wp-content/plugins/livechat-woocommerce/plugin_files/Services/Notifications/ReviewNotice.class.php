<?php
/**
 * Class ReviewNotice
 *
 * @package WooLiveChat\Services\Notifications
 */

namespace WooLiveChat\Services\Notifications;

use WooLiveChat\Services\Factories\ApiClientFactory;
use WooLiveChat\Services\ModuleConfiguration;
use WooLiveChat\Services\Options\ReviewNoticeOptions;
use WooLiveChat\Services\Templates\ReviewNoticeTemplate;
use WooLiveChat\Services\Store;
use WooLiveChat\Services\Options\Deprecated\DeprecatedOptions;
use Exception;

/**
 * Class ReviewNotice
 *
 * @package WooLiveChat\Services\Notifications
 */
class ReviewNotice extends Notification {
	/**
	 * Instance of ApiClientFactory.
	 *
	 * @var ApiClientFactory
	 */
	private $api_client_factory;

	/**
	 * Instance of ModuleConfiguration.
	 *
	 * @var ModuleConfiguration
	 */
	private $module;

	/**
	 * Instance of ReviewNoticeOptions.
	 *
	 * @var ReviewNoticeOptions
	 */
	private $review_notice;

	/**
	 * ReviewNotice constructor.
	 *
	 * @inheritDoc
	 * @param ApiClientFactory    $api_client_factory ApiClientFactory instance.
	 * @param ModuleConfiguration $module             ModuleConfiguration instance.
	 * @param ReviewNoticeOptions $review_notice      ReviewNoticeOptions instance.
	 */
	public function __construct( $store, $options, $api_client_factory, $module, $review_notice ) {
		parent::__construct( $store, $options, ReviewNoticeTemplate::create() );
		$this->api_client_factory = $api_client_factory;
		$this->module             = $module;
		$this->review_notice      = $review_notice;
		add_action( 'wp_ajax_lc_review_dismiss', array( $this, 'ajax_review_dismiss' ) );
		add_action( 'wp_ajax_lc_review_postpone', array( $this, 'ajax_review_postpone' ) );
	}

	/**
	 * Checks if LiveChat license is active.
	 *
	 * @return boolean
	 */
	private function is_license_active() {
		try {
			$result = $this->api_client_factory->create()->license_info();
			return array_key_exists( 'isActive', $result ) ? $result['isActive'] : false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Returns time in seconds since plugin conneciton.
	 *
	 * @return int
	 */
	private function get_time_since_connection() {
		return time() - $this->review_notice->timestamp->get();
	}

	/**
	 * Returns offset time in seconds.
	 *
	 * @return int
	 */
	private function get_offset_time() {
		return 60 * 60 * 24 * $this->review_notice->offset->get();
	}

	/**
	 * Returns true when license is active,
	 * notice wasn't dismissed,
	 * time offset has passed.
	 * It also means that plugins is connected,
	 * as we need token for license check.
	 *
	 * @inheritDoc
	 */
	public function should_render() {
		return $this->is_license_active() &&
				! $this->review_notice->is_dismissed() &&
				$this->get_time_since_connection() >= $this->get_offset_time() &&
				! $this->is_user_on_page( 'livechat_page_livechat_settings' ) &&
				! $this->is_user_on_page( 'livechat_page_livechat_resources' );
	}

	/**
	 * Loads review notice script and styles,
	 * registers AJAX dismiss/postpone actions.
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
	}

	/**
	 * Loads scripts and styles for review notice.
	 */
	public function enqueue_scripts_and_styles() {
		wp_enqueue_script(
			'livechat-review',
			$this->module->get_plugin_url() . 'js/livechat-review.js',
			'jquery',
			$this->module->get_plugin_version(),
			true
		);
		wp_enqueue_style(
			'livechat-review',
			$this->module->get_plugin_url() . 'css/livechat-review.css',
			false,
			$this->module->get_plugin_version()
		);
	}

	/**
	 * Marks review as dismissed in WP options.
	 */
	public function ajax_review_dismiss() {
		$this->review_notice->dismiss();
		wp_send_json_success( array( 'status' => 'ok' ) );
	}

	/**
	 * Marks review as postponed in WP options.
	 */
	public function ajax_review_postpone() {
		$this->review_notice->postpone();
		wp_send_json_success( array( 'status' => 'ok' ) );
	}

	/**
	 * Returns instance of ReviewNotice (singleton pattern).
	 *
	 * @return static
	 */
	public static function get_instance() {
		return new static(
			Store::get_instance(),
			DeprecatedOptions::get_instance(),
			ApiClientFactory::get_instance(),
			ModuleConfiguration::get_instance(),
			ReviewNoticeOptions::get_instance()
		);
	}
}
