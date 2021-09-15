<?php
/**
 * Class DeprecatedLicenseNumber
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */

namespace WooLiveChat\Services\Options\Deprecated;

use WooLiveChat\Services\Options\ReadableOption;

/**
 * Class DeprecatedLicenseNumber
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */
class DeprecatedLicenseNumber extends ReadableOption {
	/**
	 * DeprecatedLicenseNumber constructor.
	 */
	public function __construct() {
		parent::__construct( 'wc-lc_license' );
	}
}
