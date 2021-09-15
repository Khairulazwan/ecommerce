<?php
/**
 * Class SettingsOptions
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

use WooLiveChat\Services\Options\Deprecated\DeprecatedOptions;

/**
 * Class SettingsOptions
 *
 * @package WooLiveChat\Services\Options
 */
class SettingsOptions extends OptionsSet {
	/**
	 * ReviewNoticeOptions instance.
	 *
	 * @var ReviewNoticeOptions
	 */
	public $review_notice;

	/**
	 * DeprecatedOptions instance.
	 *
	 * @var DeprecatedOptions
	 */
	public $deprecated;

	/**
	 * WidgetURL instance.
	 *
	 * @var WidgetURL
	 */
	public $widget_url;

	/**
	 * SettingsOptions constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'review_notice' => ReviewNoticeOptions::get_instance(),
				'deprecated'    => DeprecatedOptions::get_instance(),
				'widget_url'    => WidgetURL::get_instance(),
			)
		);
	}
}
