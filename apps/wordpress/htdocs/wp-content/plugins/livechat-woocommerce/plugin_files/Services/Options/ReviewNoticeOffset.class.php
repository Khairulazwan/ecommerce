<?php
/**
 * Class ReviewNoticeOffset
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class ReviewNoticeOffset
 *
 * @package WooLiveChat\Services\Options
 */
class ReviewNoticeOffset extends WritableOption {
	/**
	 * ReviewNoticeOffset constructor.
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_review_notice_start_timestamp_offset', 0 );
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
