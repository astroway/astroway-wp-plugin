<?php
/**
 * Beaver Builder includes this with the module and its settings in scope.
 *
 * @package AstroWay\WPPlugin
 *
 * @var \AstroWay\WPPlugin\Builders\BeaverModule $module
 * @var object $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \AstroWay\WPPlugin\Widgets::render( $module->astroway_slug, (array) $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the card escapes its own output.
