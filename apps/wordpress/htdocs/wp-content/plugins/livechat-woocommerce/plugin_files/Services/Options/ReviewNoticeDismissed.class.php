<?php
/**
 * Class ReviewNoticeDismissed
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class ReviewNoticeDismissed
 *
 * @package WooLiveChat\Services\Options
 */
class ReviewNoticeDismissed extends WritableOption {
	/**
	 * ReviewNoticeDismissed constructor.
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_review_notice_dismissed', false );
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
