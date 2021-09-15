<?php
/**
 * Class DeprecatedOptions
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */

namespace WooLiveChat\Services\Options\Deprecated;

use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use WooLiveChat\Services\Options\OptionsSet;

/**
 * Class DeprecatedOptions
 *
 * @package WooLiveChat\Services\Options\Deprecated
 */
class DeprecatedOptions extends OptionsSet {
	/**
	 * Instance of DeprecatedLicenseNumber.
	 *
	 * @var DeprecatedLicenseNumber
	 */
	public $license;

	/**
	 * Instance of DeprecatedLicenseEmail.
	 *
	 * @var DeprecatedLicenseEmail
	 */
	public $license_email;

	/**
	 * Instance of DeprecatedReviewNoticeDismissed.
	 *
	 * @var DeprecatedReviewNoticeDismissed
	 */
	public $review_notice_dismissed;

	/**
	 * Instance of DeprecatedReviewNoticeTimestamp.
	 *
	 * @var DeprecatedReviewNoticeTimestamp
	 */
	public $review_notice_timestamp;

	/**
	 * Instance of DeprecatedReviewNoticeOffset.
	 *
	 * @var DeprecatedReviewNoticeOffset
	 */
	public $review_notice_offset;

	/**
	 * Instance of DeprecatedWidgetSettings.
	 *
	 * @var DeprecatedWidgetSettings
	 */
	public $widget_settings;

	/**
	 * DeprecatedOptions constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'license'                 => DeprecatedLicenseNumber::get_instance(),
				'license_email'           => DeprecatedLicenseEmail::get_instance(),
				'review_notice_dismissed' => DeprecatedReviewNoticeDismissed::get_instance(),
				'review_notice_timestamp' => DeprecatedReviewNoticeTimestamp::get_instance(),
				'review_notice_offset'    => DeprecatedReviewNoticeOffset::get_instance(),
				'widget_settings'         => DeprecatedWidgetSettings::get_instance(),
			)
		);
	}

	/**
	 * Returns true if review notice was dismissed.
	 *
	 * @return bool
	 */
	public function was_review_notice_dismissed() {
		return $this->review_notice_dismissed->get();
	}

	/**
	 * Removes all deprecated options.
	 *
	 * @return bool
	 */
	public function clear() {
		return $this->license->remove() &&
				$this->license_email->remove() &&
				$this->review_notice_dismissed->remove() &&
				$this->review_notice_timestamp->remove() &&
				$this->review_notice_offset->remove() &&
				$this->widget_settings->remove();
	}
}
