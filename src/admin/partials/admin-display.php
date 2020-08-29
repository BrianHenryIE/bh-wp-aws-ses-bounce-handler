<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin/partials
 */

use BH_WP_AWS_SES_Bounce_Handler\API\Settings_Interface;

/**
 * The plugin Settings class. Repeated here for code completion.
 *
 * @var Settings_Interface $settings
 */
$settings = $this->settings;

?>

<div class="wrap bh-wp-aws-ses-bounce-handler">

	<h1>AWS SES Bounce Handler</h1>

	<h3>Marks user accounts and WooCommerce orders for bounced emails; unsubscribes users who mark email as spam.</h3>

	<p>Follow Amazon's <a target="_blank" href="https://docs.aws.amazon.com/ses/latest/DeveloperGuide/configure-sns-notifications.html">Configuring Amazon SNS Notifications for Amazon SES</a> document to set up notification SNS topics in <a target="_blank" href="https://console.aws.amazon.com/ses/home?#home:">AWS SES</a> for both your verified domains and email adresses.</p>

	<div>
	<span style="display: inline-block;">Use endpoint:</span>
	<div style="display:inline-block;">
	<div class="bounce-handler-endpoint-text"><?php echo esc_url( $settings->get_endpoint() ); ?></div>
	<div><input class="bounce-handler-endpoint" value="<?php echo esc_url( $settings->get_endpoint() ); ?>"></div>
	</div> when configuring the <a target="_blank" href="https://console.aws.amazon.com/sns/v3/home?#/topics">AWS SNS topics</a>. Subscriptions are automatically confirmed when received at this endpoint.
	</div>

	<h4>Confirmed ARNs:</h4>

	<?php

	if ( empty( $settings->get_confirmed_arns() ) ) {
		echo '<div class="notice inline notice-error"><p>No SNS ARN subscriptions have been added.</p></div>';
	}

	foreach ( $settings->get_confirmed_arns() as $arn ) {
		echo '<div class="notice inline notice-success"><p><a target="_blank" href="' . esc_url( 'https://console.aws.amazon.com/sns/v3/home?#/topic/' . $arn ) . '">' . esc_html( $arn ) . '</a></p></div>';
	}
	?>

	<h2>WordPress <code>wp_mail()</code> Status</h2>

	<?php echo wp_kses( $this->get_wp_mail_info(), wp_kses_allowed_html( 'post' ) ); ?>

	<h2>Testing</h2>

	<p>This plugin can test the configuration by creating a test WordPress user, test data for integrated plugins, and sending an email to <em>bounce@simulator.amazonses.com</em>.</p>

	<form id="run-ses-bounce-test-form" action="">
		<input type="hidden" name="action" value="run_ses_bounce_test" />
		<?php wp_nonce_field( 'run-ses-bounce-test-form' ); ?>
		<input class="button" id="run-ses-bounce-test-button" type="submit" value="Run Test" />
		<img class="bounce-test-running-spinner" alt="Is bounce test running spinner" src="<?php echo esc_url( admin_url( '/images/spinner-2x.gif' ) ); ?>" />
	</form>

	<div id="run-ses-bounce-test-response"></div>


	<h2>Integrations:</h2>

	<ul >
		<?php
		$integrations = $settings->get_integrations();
		foreach ( $integrations as $name => $integration ) {
			echo '<li>' . esc_html( $name ) . ': ' . wp_kses( $integration->get_description(), wp_kses_allowed_html( 'data' ) ) . '</li>' . "\n";
		}
		?>
		<li>Fires actions <code>handle_ses_bounce</code> and <code>handle_ses_complaint</code></li>
	</ul>

	<p><a target="_blank" href="https://github.com/BrianHenryIE/bh-wp-aws-ses-bounce-handler">View code on GitHub</a> &#x2022; <a target="_blank" href="https://BrianHenry.ie">Plugin by BrianHenryIE</a> &#x2022; <a target="_blank" href="https://EnhancedAthlete.com">Plugin for Enhanced Athlete</a></p>

</div>
