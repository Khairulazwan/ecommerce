<?php
/**
 * Class ConfirmIdentityNoticeTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

/**
 * Class ConfirmIdentityNoticeTemplate
 */
class ConfirmIdentityNoticeTemplate extends Template {
	/**
	 * Renders helper.
	 */
	public function render() {
		$context                    = array();
		$context['lcNoticeLogoUrl'] = esc_html( plugins_url( 'livechat-woocommerce' ) . '/plugin_files/images/livechat-logo.svg' );
		$context['header']          = esc_html__( 'Action required - confirm your identity', 'livechat-woocommerce' );
		$context['notice']          = esc_html__(
			'Thank you for updating LiveChat to the latest version. Please click Connect to confirm your identity and finish the installation.',
			'livechat-woocommerce'
		);
		$context['button']          = esc_html__( 'Connect', 'livechat-woocommerce' );

		$this->template_parser->parse_template( 'confirm_identity_notice.html.twig', $context );
	}
}
