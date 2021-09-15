<?php


namespace WooLiveChat\Services\Options;

use Exception;

class Option {
	/**
	 * Option storage key.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Fallback value.
	 *
	 * @var string
	 */
	protected $fallback;

	/**
	 * ReadableOption constructor.
	 *
	 * @param string $key      Option storage key.
	 * @param mixed  $fallback Fallback value.
	 *
	 * @throws Exception Can be thrown when $key is not provided.
	 */
	public function __construct( $key, $fallback = null ) {
		if ( empty( $key ) ) {
			throw new Exception( 'Option cannot be declared without a storage key.' );
		}
		$this->key      = $key;
		$this->fallback = $fallback;
	}

	/**
	 * Returns instance of ReadableOption (singleton pattern).
	 *
	 * @return static
	 * @throws Exception Can be thrown from ReadableOption constructor.
	 */
	public static function get_instance() {
		return new static();
	}
}
