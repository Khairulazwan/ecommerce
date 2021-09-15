<?php
/**
 * Class SettingsProvider
 *
 * @package WooLiveChat\Services
 */

namespace WooLiveChat\Services;

use WooLiveChat\Services\Options\SettingsOptions;
use WooLiveChat\Services\Templates\SettingsTemplate;
use Exception;
use WP_Error;

/**
 * Class SettingsProvider
 *
 * @package WooLiveChat\Services
 */
class SettingsProvider {
	/**
	 * Singleton instance
	 *
	 * @var SettingsProvider|null
	 */
	private static $instance;

	/**
	 * SettingsOptions instance
	 *
	 * @var SettingsOptions|null
	 */
	public $options;

	/**
	 * SettingsTemplate instance
	 *
	 * @var SettingsTemplate|null
	 */
	public $template;

	/**
	 * TokenValidator instance
	 *
	 * @var TokenValidator|null
	 */
	public $token_validator;

	/**
	 * User instance
	 *
	 * @var User|null
	 */
	public $user;

	/**
	 * Store instance
	 *
	 * @var Store|null
	 */
	public $store;

	/**
	 * SettingsProvider constructor.
	 *
	 * @param SettingsOptions  $options         SettingsOptions instance.
	 * @param SettingsTemplate $template        SettingsTemplate instance.
	 * @param TokenValidator   $token_validator TokenValidator instance.
	 * @param User             $user            User instance.
	 * @param Store            $store           Store instance.
	 */
	public function __construct( $options, $template, $token_validator, $user, $store ) {
		$this->options         = $options;
		$this->template        = $template;
		$this->token_validator = $token_validator;
		$this->user            = $user;
		$this->store           = $store;
	}

	/**
	 * Initialize ajax actions
	 */
	public function init() {
		add_action( 'wp_ajax_lc_connect', array( $this, 'ajax_connect' ) );
		add_action( 'wp_ajax_lc_disconnect', array( $this, 'ajax_disconnect' ) );
		add_action( 'wp_ajax_lc_store_not_found', array( $this, 'ajax_store_not_found' ) );
		add_action( 'wp_ajax_lc_user_not_found', array( $this, 'ajax_user_not_found' ) );
		add_action( 'wp_ajax_lc_widget_script_updated', array( $this, 'ajax_widget_script_updated' ) );
	}

	/**
	 * Displays settings page
	 */
	public function render() {
		$this->template->render();
	}

	/**
	 * Connects WP plugin with LiveChat account.
	 * Validates tokens and, if they are valid, stores them in WP database.
	 */
	public function ajax_connect() {
		$user_token  = null;
		$store_token = null;

		check_ajax_referer( 'wp_ajax_lc_connect', 'security' );
		if ( isset( $_POST['user_token'] ) && isset( $_POST['store_token'] ) && isset( $_POST['security'] ) ) {
			$user_token  = sanitize_text_field( wp_unslash( $_POST['user_token'] ) );
			$store_token = sanitize_text_field( wp_unslash( $_POST['store_token'] ) );
		}

		try {
			$this->token_validator->validate_tokens( $user_token, $store_token );
			$this->user->authorize_current_user( $user_token );
			$this->store->authorize_store( $store_token );
			$review_notice_dismissed = $this->options->deprecated->was_review_notice_dismissed();

			if ( $review_notice_dismissed ) {
				$this->options->review_notice->dismiss();
			} else {
				$this->options->review_notice->start();
			}

			$this->options->deprecated->clear();

			wp_send_json_success( array( 'status' => 'ok' ) );
		} catch ( Exception $e ) {
			wp_send_json_error(
				new WP_Error( $e->getCode(), $e->getMessage() )
			);
		}
	}

	/**
	 * Removes LiveChat data from database.
	 */
	public function ajax_disconnect() {
		$this->store->remove_store_data();
		$this->user->remove_authorized_users();
	}

	/**
	 * Removes store token when it was not found in LiveChat service.
	 */
	public function ajax_store_not_found() {
		$this->store->remove_store_data();
		$this->user->remove_authorized_users();
	}

	/**
	 * Removes current user token when it was not found in LiveChat service.
	 */
	public function ajax_user_not_found() {
		$this->user->remove_current_user_token();
	}

	/**
	 * Updates widget scrip URL.
	 */
	public function ajax_widget_script_updated() {
		$widget_url = null;

		check_ajax_referer( 'wp_ajax_lc_connect', 'security' );

		if ( isset( $_POST['widget_url'] ) && isset( $_POST['security'] ) ) {
			$widget_url = sanitize_text_field( wp_unslash( $_POST['widget_url'] ) );
			$this->options->widget_url->set( $widget_url );
			return true;
		}

		return false;
	}

	/**
	 * Gets singleton instance
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new static(
				SettingsOptions::get_instance(),
				SettingsTemplate::create(),
				TokenValidator::create( CertProvider::create() ),
				User::get_instance(),
				Store::get_instance()
			);
		}
		return self::$instance;
	}
}
