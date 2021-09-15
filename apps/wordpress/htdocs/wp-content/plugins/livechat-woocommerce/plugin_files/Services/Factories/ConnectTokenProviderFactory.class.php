<?php
/**
 * Class ConnectTokenProviderFactory
 *
 * @package WooLiveChat\Services\Factories
 */

namespace WooLiveChat\Services\Factories;

use WooLiveChat\Services\CertProvider;
use WooLiveChat\Services\ConnectTokenProvider;

/**
 * Class ConnectTokenProviderFactory
 *
 * @package WooLiveChat\Services\Factories
 */
class ConnectTokenProviderFactory {
	/**
	 * Returns new instance of ConnectTokenProvider.
	 *
	 * @param CertProvider|null $cert_provider Instance of CertProvider.
	 *
	 * @return ConnectTokenProvider
	 */
	public function create( $cert_provider = null ) {
		if ( ! $cert_provider ) {
			$cert_provider = CertProvider::create();
		}

		return ConnectTokenProvider::create( $cert_provider );
	}

	/**
	 * Returns new instance of ConnectTokenProviderFactory.
	 *
	 * @return static
	 */
	public static function get_instance() {
		return new static();
	}
}
