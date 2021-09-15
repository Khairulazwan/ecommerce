<?php
/**
 * Class ReviewNoticeOptions
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class ReviewNoticeOptions
 *
 * @package WooLiveChat\Services\Options
 */
class ReviewNoticeOptions extends OptionsSet {
	/**
	 * ReviewNoticeDismissed instance.
	 *
	 * @var ReviewNoticeDismissed
	 */
	public $dismissed;

	/**
	 * ReviewNoticeTimestamp instance.
	 *
	 * @var ReviewNoticeTimestamp
	 */
	public $timestamp;

	/**
	 * ReviewNoticeOffset instance.
	 *
	 * @var ReviewNoticeOffset
	 */
	public $offset;

	/**
	 * ReviewNoticeOptions constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'dismissed' => ReviewNoticeDismissed::get_instance(),
				'timestamp' => ReviewNoticeTimestamp::get_instance(),
				'offset'    => ReviewNoticeOffset::get_instance(),
			)
		);
	}

	/**
	 * Returns true if review notice was dismissed.
	 *
	 * @return bool
	 */
	public function is_dismissed() {
		return $this->dismissed->get();
	}

	/**
	 * Dismisses review notice.
	 *
	 * @return bool
	 */
	public function dismiss() {
		return $this->dismissed->set( true );
	}

	/**
	 * Postpones review notice.
	 *
	 * @return bool
	 */
	public function postpone() {
		return $this->timestamp->set_current() && $this->offset->set( 7 );
	}

	/**
	 * Starts review notice countdown.
	 *
	 * @return bool
	 */
	public function start() {
		return $this->timestamp->set_current() && $this->offset->set( 15 );
	}

	/**
	 * Starts review notice countdown.
	 *
	 * @return bool
	 */
	public function clear() {
		return $this->dismissed->remove() &&
				$this->timestamp->remove() &&
				$this->offset->remove();
	}
}
