<?php
/**
 * Class TrackingCodeTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

use WooLiveChat\Services\LicenseProvider;
use WooLiveChat\Services\Options\Deprecated\DeprecatedWidgetSettings;
use WooLiveChat\Services\Options\SettingsOptions;
use WooLiveChat\Services\Store;
use WooLiveChat\Services\TemplateParser;
use WooLiveChat\Services\User;

// TODO: it's used only for migration. Should be removed.
/**
 * Class TrackingCodeTemplate
 *
 * @package WooLiveChat\Services\Templates
 */
class TrackingCodeTemplate extends Template {
	/**
	 * Instance of Store.
	 *
	 * @var Store
	 */
	private $store;

	/**
	 * Instance of User.
	 *
	 * @var User
	 */
	private $user;

	/**
	 * Instance of LicenseProvider.
	 *
	 * @var LicenseProvider
	 */
	private $license_provider;

	/**
	 * Instance of DeprecatedWidgetSettings.
	 *
	 * @var DeprecatedWidgetSettings
	 */
	private $widget_settings;

	/**
	 * TrackingCodeTemplate constructor.
	 *
	 * @param Store                    $store            Instance of Store.
	 * @param User                     $user             Instance of User.
	 * @param LicenseProvider          $license_provider Instance of LicenseProvider.
	 * @param TemplateParser           $template_parser  Instance of TemplateParser.
	 * @param DeprecatedWidgetSettings $widget_settings  Instance of DeprecatedWidgetSettings.
	 */
	public function __construct( $store, $user, $license_provider, $template_parser, $widget_settings ) {
		parent::__construct( $template_parser );
		$this->store            = $store;
		$this->user             = $user;
		$this->license_provider = $license_provider;
		$this->widget_settings  = $widget_settings;
	}

	/**
	 * Checks if visitor is on mobile device.
	 *
	 * @return bool
	 */
	private function check_mobile() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$regex      = '/((Chrome).*(Mobile))|((Android).*)|((iPhone|iPod).*Apple.*Mobile)|((Android).*(Mobile))/i';
		return preg_match( $regex, $user_agent );
	}

	/**
	 * Injects scripts with tracking code.
	 *
	 * @return string
	 */
	public function render() {
		if ( ! $this->license_provider->has_deprecated_license_number() || $this->store->is_connected() ) {
			return '';
		}

		$settings        = $this->widget_settings->get();
		$hide_on_mobile  = $settings['hideOnMobile'];
		$hide_for_guests = $settings['hideForGuests'];
		$is_mobile       = $this->check_mobile();
		$is_logged       = $this->user->check_logged();

		if ( ( $hide_on_mobile && $is_mobile ) || ( $hide_for_guests && ! $is_logged ) ) {
			return '';
		}

		$context                  = array();
		$context['licenseNumber'] = $this->license_provider->get_license_number();

		$visitor                 = $this->user->get_user_data();
		$context['visitorName']  = $visitor['name'];
		$context['visitorEmail'] = $visitor['email'];

		return $this->template_parser->parse_template( 'tracking_code.html.twig', $context );
	}

	/**
	 * Returns new instance of TrackingCodeTemplate.
	 *
	 * @return static
	 */
	public static function create() {
		return new static(
			Store::get_instance(),
			User::get_instance(),
			LicenseProvider::create(),
			TemplateParser::create( '../templates' ),
			DeprecatedWidgetSettings::get_instance()
		);
	}
}
