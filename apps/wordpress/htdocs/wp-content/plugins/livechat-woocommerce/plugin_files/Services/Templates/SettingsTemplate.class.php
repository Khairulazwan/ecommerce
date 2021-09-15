<?php
/**
 * Class ConnectServiceTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

use Exception;
use WooLiveChat\Services\CertProvider;
use WooLiveChat\Services\ConnectTokenProvider;
use WooLiveChat\Services\ModuleConfiguration;
use WooLiveChat\Services\Options\Deprecated\DeprecatedWidgetSettings;
use WooLiveChat\Services\Options\SettingsOptions;
use WooLiveChat\Services\Store;
use WooLiveChat\Services\TemplateParser;
use WooLiveChat\Services\UrlProvider;
use WooLiveChat\Services\User;
use WooLiveChat\Services\LicenseProvider;

/**
 * Class ConnectServiceTemplate
 */
class SettingsTemplate extends Template {
	/**
	 * ModuleConfiguration instance
	 *
	 * @var ModuleConfiguration|null
	 */
	private $module = null;

	/**
	 * Current user instance
	 *
	 * @var User|null
	 */
	private $user = null;

	/**
	 * Current store instance.
	 *
	 * @var Store|null
	 */
	private $store = null;


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
	 * ConnectServiceTemplate constructor.
	 *
	 * @param ModuleConfiguration      $module           ModuleConfiguration class instance.
	 * @param User                     $user             User class instance.
	 * @param Store                    $store            Store class instance.
	 * @param TemplateParser           $template_parser  Instance of TemplateParser.
	 * @param LicenseProvider          $license_provider Instance of LicenseProvider.
	 * @param DeprecatedWidgetSettings $widget_settings  Instance of DeprecatedWidgetSettings.
	 */
	public function __construct( $module, $user, $store, $template_parser, $license_provider, $widget_settings ) {
		parent::__construct( $template_parser );
		$this->module           = $module;
		$this->user             = $user;
		$this->store            = $store;
		$this->license_provider = $license_provider;
		$this->widget_settings  = $widget_settings;
	}

	/**
	 * Returns app url with region from store token.
	 *
	 * @return string
	 */
	private function get_app_url() {
		try {
			$decoded_token = ConnectTokenProvider::create( CertProvider::create() )->get( $this->store->get_store_token(), 'store' );
			return UrlProvider::create( $decoded_token )->get_app_url();
		} catch ( Exception $exception ) {
			return UrlProvider::create()->get_app_url();
		}
	}

	/**
	 * Renders iframe with Connect service.
	 */
	public function render() {
		$context                  = array();
		$context['appUrl']        = esc_html( $this->get_app_url() );
		$context['siteUrl']       = esc_html( $this->module->get_site_url() );
		$context['userEmail']     = esc_html( $this->user->get_user_data()['email'] );
		$context['userName']      = esc_html( $this->user->get_user_data()['name'] );
		$context['wpVer']         = esc_html( $this->module->get_wp_version() );
		$context['wooVer']        = esc_html( $this->module->get_woo_version() );
		$context['moduleVer']     = esc_html( $this->module->get_plugin_version() );
		$context['lcToken']       = esc_html( $this->user->get_current_user_token() );
		$context['storeToken']    = esc_html( $this->store->get_store_token() );
		$context['partnerId']     = esc_html( LC_PARTNER_ID );
		$context['utmCampaign']   = esc_html( LC_UTM_CAMPAIGN );
		$context['license']       = esc_html( $this->license_provider->get_license_number() );
		$context['legacyOptions'] = wp_json_encode( $this->widget_settings->get() );

		$this->template_parser->parse_template( 'connect.html.twig', $context );
	}

	/**
	 * Returns new instance of ConnectServiceTemplate.
	 *
	 * @return static
	 */
	public static function create() {
		return new static(
			ModuleConfiguration::get_instance(),
			User::get_instance(),
			Store::get_instance(),
			TemplateParser::create( '../templates' ),
			LicenseProvider::create(),
			DeprecatedWidgetSettings::get_instance()
		);
	}
}
