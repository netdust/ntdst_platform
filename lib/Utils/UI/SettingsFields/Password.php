<?php
/**
 * Settings Password Field
 *
 * @since 1.0.0
 * @package Underpin\Factories\Settings_Fields
 */


namespace Netdust\Utils\UI\SettingsFields;

use Netdust\Utils\UI\SettingsField;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Password
 *
 * @since 1.0.0
 * @package Underpin\Factories\Settings_Fields
 */
class Password extends SettingsField {

	/**
	 * @inheritDoc
	 */
	function get_field_type() {
		return 'password';
	}

	/**
	 * @inheritDoc
	 */
	function sanitize( $value ) {
		return (string) $value;
	}
}