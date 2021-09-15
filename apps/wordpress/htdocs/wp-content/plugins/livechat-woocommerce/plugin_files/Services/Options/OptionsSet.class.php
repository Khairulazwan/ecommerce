<?php
/**
 * Class OptionsSet
 *
 * @package WooLiveChat\Services\Options
 */

namespace WooLiveChat\Services\Options;

/**
 * Class OptionsSet
 *
 * @package WooLiveChat\Services\Options
 */
class OptionsSet {
	/**
	 * OptionsSet constructor.
	 *
	 * @param Option[] $options Array of Options.
	 */
	public function __construct( $options ) {
		foreach ( $options as $key => $option ) {
			$this->{$key} = $option;
		}
	}

	/**
	 * Returns instance of OptionsSet (singleton pattern).
	 *
	 * @return static
	 */
	public static function get_instance() {
		return new static();
	}
}
