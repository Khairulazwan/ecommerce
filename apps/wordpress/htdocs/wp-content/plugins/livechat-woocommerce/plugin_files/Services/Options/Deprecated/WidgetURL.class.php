<?php
/**
 * Class WidgetURL
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class WidgetURL
 *
 * @package WooLiveChat\Services\Options
 */
class WidgetURL extends WritableOption {
	/**
	 * WidgetURL constructor.
	 */
	public function __construct() {
		parent::__construct( 'woo_livechat_widget_url' );
	}
}
