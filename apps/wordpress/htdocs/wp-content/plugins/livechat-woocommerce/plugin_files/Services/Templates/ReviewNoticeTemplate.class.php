<?php
/**
 * Class ReviewNoticeTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

/**
 * Class ReviewNoticeTemplate
 */
class ReviewNoticeTemplate extends Template {
	/**
	 * Renders review notice.
	 */
	public function render() {
		$context                     = array();
		$context['lcNoticeLogo']     = esc_html(
			sprintf(
				'%s/plugin_files/images/livechat-logo.svg',
				plugins_url( 'livechat-woocommerce' )
			)
		);
		$context['lcTeam']           = esc_html__( 'The LiveChat Team', 'livechat-woocommerce' );
		$context['description']      = wp_kses(
			__(
				'Hey, you’ve been using <strong>LiveChat</strong> for more than 14 days - that’s awesome! Could you please do us a BIG favour and <strong>give LiveChat a 5-star rating on WordPress</strong>? Just to help us spread the word and boost our motivation.',
				'livechat-woocommerce'
			),
			array( 'strong' => array() )
		);
		$context['lcTeam']           = wp_kses( __( '<strong>&ndash; The LiveChat Team</strong>' ), array( 'strong' => array() ) );
		$context['answerDeserveIt']  = esc_html__( 'Ok, you deserve it', 'livechat-woocommerce' );
		$context['answerMaybeLater'] = esc_html__( 'Maybe later', 'livechat-woocommerce' );
		$context['answerNoThanks']   = esc_html__( 'No, thanks', 'livechat-woocommerce' );

		$this->template_parser->parse_template( 'review_notice.html.twig', $context );
	}
}
