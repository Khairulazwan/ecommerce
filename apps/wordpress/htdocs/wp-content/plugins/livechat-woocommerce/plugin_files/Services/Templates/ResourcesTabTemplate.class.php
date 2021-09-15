<?php
/**
 * Class ResourcesTabTemplate
 *
 * @package WooLiveChat\Services\Templates
 */

namespace WooLiveChat\Services\Templates;

/**
 * Class ResourcesTabTemplate
 */
class ResourcesTabTemplate extends Template {
	/**
	 * Renders iframe with Resources page.
	 */
	public function render() {
		$context                 = array();
		$context['resourcesUrl'] = esc_html( LC_RESOURCES_URL );
		$this->template_parser->parse_template( 'resources.html.twig', $context );
	}
}
