<?php
/**
 * Class DeprecatedReviewNoticeOffset
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */

namespace WooLiveChat\Services\Options\Deprecated;

use WooLiveChat\Services\Options\ReadableOption;

/**
 * Class DeprecatedReviewNoticeOffset
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */
class DeprecatedReviewNoticeOffset extends ReadableOption {
	/**
	 * DeprecatedReviewNoticeOffset constructor.
	 */
	public function __construct() {
		parent::__construct( 'wc-lc_review_notice_start_timestamp_offset', 0 );
	}

	/**
	 * Returns postpone offset in days
	 *
	 * @return int
	 */
	public function get() {
		return (int) parent::get();
	}
}
