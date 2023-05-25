<?php
/**
 * Settings Select Field
 *
 * @since 1.0.0
 * @package Underpin\Factories\Settings_Fields
 */


namespace Netdust\Loaders\Admin\Factories\SettingsFields;

use Netdust\Loaders\Admin\Abstracts\SettingsField;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Select
 *
 * @since 1.0.0
 * @package Underpin\Factories\Settings_Fields
 */
class Select extends SettingsField {

	/**
	 * @inheritDoc
	 */
	function get_field_type() {
		return 'select';
	}

	/**
	 * @inheritDoc
	 */
	function sanitize( $value ) {
		return (string) $value;
	}
}