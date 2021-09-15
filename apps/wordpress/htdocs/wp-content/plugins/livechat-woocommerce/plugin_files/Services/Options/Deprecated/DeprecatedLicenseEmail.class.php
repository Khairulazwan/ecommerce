<?php
/**
 * Class DeprecatedLicenseEmail
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */

namespace WooLiveChat\Services\Options\Deprecated;

use WooLiveChat\Services\Options\ReadableOption;

/**
 * Class DeprecatedLicenseEmail
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */
class DeprecatedLicenseEmail extends ReadableOption {
	/**
	 * DeprecatedLicenseEmail constructor.
	 */
	public function __construct() {
		parent::__construct( 'wc-lc_licenseEmail' );
	}
}
