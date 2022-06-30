<?php
/**
 * The plugin page output of the plugin.
 *
 * @link
 * @since      1.0.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Admin;

use BrianHenryIE\AWS_SES_Bounce_Handler\Settings_Interface;

/**
 * This class adds a `Settings` link on the plugins.php page.
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Plugins_Page {

	protected Settings_Interface $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Add link to settings page in plugins.php list.
	 *
	 * @param array $links_array The existing plugin links (usually "Deactivate").
	 *
	 * @return array<mixed, string> The links to display below the plugin name on plugins.php.
	 */
	public function action_links( $links_array ): array {

		$settings_url = admin_url( '/options-general.php?page=' . $this->settings->get_plugin_slug() );
		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

	/**
	 * Add a link to GitHub repo on the plugins list.
	 *
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param string[] $plugin_meta The meta information/links displayed by the plugin description.
	 * @param string   $plugin_file_name The plugin filename to match when filtering.
	 * @param array    $plugin_data Associative array including PluginURI, slug, Author, Version.
	 * @param string   $status The plugin status, e.g. 'Inactive'.
	 *
	 * @return array The filtered $plugin_meta.
	 */
	public function row_meta( $plugin_meta, $plugin_file_name, $plugin_data, $status ): array {

		if ( $this->settings->get_plugin_basename() === $plugin_file_name ) {

			foreach ( $plugin_meta as $index => $link ) {
				$plugin_meta[ $index ] = str_replace( 'Visit plugin site', 'View plugin on GitHub', $link );
			}

			$aws_ses_console_url = 'https://console.aws.amazon.com/ses/home?region=us-east-1#home:';
			$plugin_meta[]       = '<a target="_blank" href="' . $aws_ses_console_url . '">AWS SES Console</a>';

		}

		return $plugin_meta;
	}

}
