<?php
/**
 * Class WidgetProvider
 *
 * @package WooLiveChat\Services
 */

namespace WooLiveChat\Services;

use Exception;
use WooLiveChat\Exceptions\ApiClientException;
use WooLiveChat\Exceptions\InvalidTokenException;
use WooLiveChat\Services\Options\WidgetURL;
use WooLiveChat\Services\Factories\UrlProviderFactory;
use WooLiveChat\Services\Templates\TrackingCodeTemplate;
use WooLiveChat\Services\Templates\CustomerTrackingTemplate;
use WC_Customer;
use WC_Cart;

/**
 * Class WidgetProvider
 *
 * @package WooLiveChat\Services
 */
class WidgetProvider {
	/**
	 * Instance of WidgetProvider.
	 *
	 * @var WidgetProvider|null
	 */
	private static $instance = null;

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
	 * Instance of WidgetURL.
	 *
	 * @var WidgetURL
	 */
	private $widget_url;

	/**
	 * Instance of ConnectTokenProvider.
	 *
	 * @var ConnectTokenProvider
	 */
	private $connect_token_provider;

	/**
	 * Instance of UrlProviderFactory.
	 *
	 * @var UrlProviderFactory
	 */
	private $url_provider_factory;

	/**
	 * Instance of TrackingCodeTemplate.
	 *
	 * @var TrackingCodeTemplate
	 */
	private $tracking_code_template;

	/**
	 * Instance of CustomerTrackingTemplate.
	 *
	 * @var CustomerTrackingTemplate
	 */
	private $customer_tracking_template;

	/**
	 * WidgetProvider constructor.
	 *
	 * @param Store                    $store                      Instance of Store.
	 * @param User                     $user                       Instance of User.
	 * @param WidgetURL                $widget_url                 Instance of WidgetURL.
	 * @param ConnectTokenProvider     $connect_token_provider     Instance of ConnectTokenProvider.
	 * @param UrlProviderFactory       $url_provider_factory       Instance of UrlProviderFactory.
	 * @param TrackingCodeTemplate     $tracking_code_template     Instance of TrackingCodeTemplate.
	 * @param CustomerTrackingTemplate $customer_tracking_template Instance of CustomerTrackingTemplate.
	 */
	public function __construct(
		$store,
		$user,
		$widget_url,
		$connect_token_provider,
		$url_provider_factory,
		$tracking_code_template,
		$customer_tracking_template
	) {
		$this->store                      = $store;
		$this->user                       = $user;
		$this->widget_url                 = $widget_url;
		$this->connect_token_provider     = $connect_token_provider;
		$this->url_provider_factory       = $url_provider_factory;
		$this->tracking_code_template     = $tracking_code_template;
		$this->customer_tracking_template = $customer_tracking_template;
	}

	/**
	 * Returns shipping address string for given customer.
	 *
	 * @param WC_Customer $customer  Woo customer.
	 * @param array       $countries WooCommerce countries.
	 *
	 * @return string
	 */
	private function get_shipping_address( $customer, $countries ) {
		return implode(
			', ',
			array_filter(
				array(
					$customer->get_shipping_address_1(),
					$customer->get_shipping_address_2(),
					implode(
						' ',
						array_filter(
							array(
								$customer->get_shipping_city(),
								$customer->get_shipping_state(),
								$customer->get_shipping_postcode(),
							)
						)
					),
					$countries[ $customer->get_shipping_country() ],
				)
			)
		);
	}

	/**
	 * Returns cart contents.
	 *
	 * @param WC_Cart $woo_cart Woo cart.
	 *
	 * @return array
	 */
	private function get_cart_content( $woo_cart ) {
		$count = $woo_cart->get_cart_contents_count();
		$cart  = array();

		if ( $count > 0 ) {
			$cart['Total count'] = $count;
			$total               = $woo_cart->get_cart_contents_total();
			$currency            = get_woocommerce_currency();
			$cart['Total value'] = "$total $currency";

			$items = $woo_cart->get_cart_contents();
			foreach ( $items as $item ) {
				$product = wc_get_product( $item['data'] );
				$url     = $product->get_permalink();
				$qty     = $item['quantity'];
				$name    = $product->get_name();

				$cart[ "{$qty}x $name" ] = $url;
			}
		}

		return $cart;
	}

	/**
	 * Returns cart and customer tracking data for AJAX action;
	 */
	public function ajax_get_customer_tracking() {
		$woocommerce  = WC();
		$woo_customer = $woocommerce->customer;

		if ( ! $woo_customer ) {
			return null;
		}

		$customer_details = array();

		if ( $this->user->check_logged() ) {
			$order                          = wc_get_customer_last_order( $woo_customer->get_id() );
			$order_url                      = $order ? $order->get_edit_order_url() : null;
			$customer_details['Last order'] = $order_url ? $order_url : '---';

			$customer_details['Shipping address'] = $this->get_shipping_address( $woo_customer, $woocommerce->countries->get_countries() );
		}

		$cart_contents = $this->get_cart_content( $woocommerce->cart );
		wp_send_json(
			array(
				'cart'     => array_merge(
					$customer_details,
					$cart_contents
				),
				'customer' => $this->user->get_user_data(),
			)
		);
	}

	/**
	 * Checks if widget URL matches RegEx. Returns true if URL is valid,
	 * otherwise returns false.
	 *
	 * @param string $widget_url Widget URL to check.
	 *
	 * @return false|int
	 */
	private function is_widget_url_valid( $widget_url ) {
		return preg_match( LC_WIDGET_URL_REGEX, $widget_url );
	}

	/**
	 * Gets URL from token
	 *
	 * @return string
	 * @throws ApiClientException Can be thrown from ConnectTokenProvider and UrlProvider.
	 * @throws InvalidTokenException Can be thrown from ConnectTokenProvider and UrlProvider.
	 */
	private function get_url_from_token() {
		$connect_token = $this->connect_token_provider->get(
			$this->store->get_store_token(),
			'store'
		);

		$api_url = $this->url_provider_factory->create( $connect_token )->get_api_url();

		return sprintf(
			'%s/api/v1/script/%s/widget.js',
			$api_url,
			$connect_token->get_store_uuid()
		);
	}

	/**
	 * Sets widget script.
	 */
	public function set_widget() {
		try {
			$widget_url = $this->widget_url->get();

			if ( ! $widget_url || ! $this->is_widget_url_valid( $widget_url ) ) {
				$widget_url = $this->get_url_from_token();
				$this->widget_url->set( $widget_url );
			}

			wp_register_script( // phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
				'livechat-widget',
				$widget_url,
				array(),
				null, // URL is cached and does not depend on plugin version.
				$in_footer = true
			);
			wp_enqueue_script( 'livechat-widget' );
			wp_add_inline_script(
				'livechat-widget',
				$this->customer_tracking_template->render(),
				'before'
			);
		} catch ( Exception $ex ) {
			$this->tracking_code_template->render();
		}
	}

	/**
	 * Returns instance of WidgetProvider (singleton pattern).
	 *
	 * @return WidgetProvider|null
	 * @throws Exception
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static(
				Store::get_instance(),
				User::get_instance(),
				WidgetURL::get_instance(),
				ConnectTokenProvider::create( CertProvider::create() ),
				UrlProviderFactory::get_instance(),
				TrackingCodeTemplate::create(),
				CustomerTrackingTemplate::create()
			);
		}

		return static::$instance;
	}
}
