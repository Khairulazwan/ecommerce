<?php
/**
 * Class ConnectNoticeTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

/**
 * Class ConnectNoticeTemplate
 */
class ConnectNoticeTemplate extends Template {
	/**
	 * Renders ConnectNotice in WP dashboard.
	 *
	 * @return string
	 */
	public function render() {
		$context                    = array();
		$context['lcNoticeLogoUrl'] = esc_html(
			sprintf(
				'%s/plugin_files/images/livechat-logo.svg',
				plugins_url( 'livechat-woocommerce' )
			)
		);
		$context['noticeHeader']    = esc_html__( 'Action required - connect LiveChat', 'livechat-woocommerce' );
		$context['paragraph1']      = esc_html__( 'Please', 'livechat-woocommerce' );
		$context['paragraph2']      = esc_html__( 'connect your LiveChat account', 'livechat-woocommerce' );
		$context['paragraph3']      = esc_html__( 'to start chatting with your customers.', 'livechat-woocommerce' );
		$context['button']          = esc_html__( 'Connect', 'livechat-woocommerce' );

		return $this->template_parser->parse_template( 'connect_notice.html.twig', $context );
	}
}
