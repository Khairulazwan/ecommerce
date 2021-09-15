<?php
/**
 * Class DeactivationModalTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

use WooLiveChat\Services\LicenseProvider;
use WooLiveChat\Services\User;

/**
 * Class DeactivationModalTemplate
 */
class DeactivationModalTemplate extends Template {
	/**
	 * Renders modal with deactivation feedback form.
	 */
	public function render() {
		$user           = User::get_instance()->get_user_data();
		$license_number = LicenseProvider::create()->get_license_number();

		$context                                = array();
		$context['cancelButton']                = esc_html__( 'Cancel', 'livechat-woocommerce' );
		$context['lcNoticeLogo']                = esc_html(
			sprintf(
				'%s/plugin_files/images/livechat-icon.svg',
				plugins_url( 'livechat-woocommerce' )
			)
		);
		$context['header']                      = esc_html__( 'Quick Feedback', 'livechat-woocommerce' );
		$context['description']                 = esc_html__( 'If you have a moment, please let us know why you are deactivating LiveChat:', 'livechat-woocommerce' );
		$context['reasonNoLongerNeed']          = esc_html__( 'I no longer need the plugin.', 'livechat-woocommerce' );
		$context['reasonDoesNotWork']           = esc_html__( "I couldn't get the plugin to work.", 'livechat-woocommerce' );
		$context['reasonBetterPlugin']          = esc_html__( 'I found a better plugin.', 'livechat-woocommerce' );
		$context['reasonTemporaryDeactivation'] = esc_html__( "It's a temporary deactivation.", 'livechat-woocommerce' );
		$context['reasonOther']                 = esc_html__( 'Other', 'livechat-woocommerce' );
		$context['textPlaceholder']             = esc_html__( 'Tell us more...', 'livechat-woocommerce' );
		$context['options']                     = esc_html__( 'Please choose one of available options.', 'livechat-woocommerce' );
		$context['feedback']                    = esc_html__( 'Please provide additional feedback.', 'livechat-woocommerce' );
		$context['licenseNumber']               = $license_number;
		$context['name']                        = $user['name'];
		$context['email']                       = $user['email'];
		$context['skipButton']                  = esc_html__( 'Skip & continue', 'livechat-woocommerce' );
		$context['sendButton']                  = esc_html__( 'Send feedback', 'livechat-woocommerce' );

		$this->template_parser->parse_template( 'deactivation_modal.html.twig', $context );
	}
}
