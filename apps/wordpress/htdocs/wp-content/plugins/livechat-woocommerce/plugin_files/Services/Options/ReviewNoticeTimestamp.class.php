<?php
/**
 * Class ReviewNoticeTimestamp
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class ReviewNoticeTimestamp
 *
 * @package WooLiveChat\Services\Options
 */
class ReviewNoticeTimestamp extends WritableOption {
	/**
	 * ReviewNoticeTimestamp constructor.
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_review_notice_start_timestamp', 0 );
	}

	/**
	 * Returns review notice timestamp
	 *
	 * @return int
	 */
	public function get() {
		return (int) parent::get();
	}

	/**
	 * Starts review notice countdown.
	 *
	 * @return bool
	 */
	public function set_current() {
		return $this->set( time() );
	}
}
