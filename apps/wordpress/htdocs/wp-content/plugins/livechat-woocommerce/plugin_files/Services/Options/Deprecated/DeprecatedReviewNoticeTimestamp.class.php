<?php
/**
 * Class DeprecatedReviewNoticeTimestamp
 *
 * @package WooLiveChatServicesOptionsDeprecated
 */

namespace WooLiveChat\Services\Options\Deprecated;

use WooLiveChat\Services\Options\ReadableOption;

/**
 * Class DeprecatedReviewNoticeTimestamp
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */
class DeprecatedReviewNoticeTimestamp extends ReadableOption {
	/**
	 * DeprecatedReviewNoticeTimestamp constructor.
	 */
	public function __construct() {
		parent::__construct( 'wc-lc_review_notice_start_timestamp', 0 );
	}

	/**
	 * Returns review notice timestamp
	 *
	 * @return int
	 */
	public function get() {
		return (int) parent::get();
	}
}
