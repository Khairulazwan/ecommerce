<?php
/**
 * Class DeprecatedReviewNoticeDismissed
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */

namespace WooLiveChat\Services\Options\Deprecated;

use WooLiveChat\Services\Options\ReadableOption;

/**
 * Class DeprecatedReviewNoticeDismissed
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */
class DeprecatedReviewNoticeDismissed extends ReadableOption {
	/**
	 * DeprecatedReviewNoticeDismissed constructor.
	 */
	public function __construct() {
		parent::__construct( 'wc-lc_review_notice_dismissed', false );
	}

	/**
	 * Returns true if review notice was dismissed
	 *
	 * @return bool
	 */
	public function get() {
		return (bool) parent::get();
	}
}
