<?php
/**
 * Class PluginsConflictNoticeTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

/**
 * Class PluginsConflictNoticeTemplate
 */
class PluginsConflictNoticeTemplate extends Template {
	/**
	 * Renders helper.
	 */
	public function render() {
		$context                    = array();
		$context['lcNoticeLogoUrl'] = esc_html( plugins_url( 'livechat-woocommerce' ) . '/plugin_files/images/livechat-logo.svg' );
		$context['header1']         = esc_html__( 'Action required:', 'livechat-woocommerce' );
		$context['header2']         = esc_html__( 'LiveChat – WP live chat plugin for WordPress', 'livechat-woocommerce' );
		$context['header3']         = esc_html__( 'detected', 'livechat-woocommerce' );
		$context['notice1']         = esc_html__(
			'You cannot run multiple LiveChat plugins at the same time. To continue using this plugin (LiveChat WooCommerce), please disable',
			'livechat-woocommerce'
		);
		$context['notice2']         = esc_html__( 'LiveChat', 'livechat-woocommerce' );
		$context['notice3']         = esc_html__( 'in the Plugins tab.', 'livechat-woocommerce' );
		$context['url']             = wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=wp-live-chat-software-for-wordpress%2Flivechat.php', 'deactivate-plugin_wp-live-chat-software-for-wordpress/livechat.php' );
		$context['button']          = esc_html__( 'Deactivate plugin', 'livechat-woocommerce' );

		$this->template_parser->parse_template( 'plugins_conflict_notice.html.twig', $context );
	}
}
