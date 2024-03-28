<?php
/**
 * Plugin Name: Ngrok Local
 * Plugin URI: https://wp-stream.com/
 * Description: Translate host on the fly to expose local server to the web using ngrok.
 * Version: 0.0.1
 * Author: Jonathan Bardo
 * Author URI: http://jonathanbardo.com
 * License: GPLv2+
 */

/**
 * Class Ngrok_Local
 */
class Ngrok_Local {

	/**
	 * The local site url.
	 *
	 * @var string
	 */
	private string $site_url;

	/**
	 * Store the site url and set the constants.
	 */
	public function __construct() {
		$this->site_url = site_url() . '/';

		if (
			! defined( 'WP_SITEURL' ) &&
			! defined( 'WP_HOME' ) &&
			isset( $_SERVER['HTTP_HOST'] )
		) {
			$protocol = is_ssl() ? 'https://' : 'http://';

			define( 'WP_SITEURL', $protocol . $_SERVER['HTTP_HOST'] );
			define( 'WP_HOME', $protocol . $_SERVER['HTTP_HOST'] );
		} else {
			// Bail if those constants are already defined.
			return;
		}

		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
	}

	/**
	 * Replace the site url with the ngrok url in content.
	 *
	 * @return void
	 */
	public function template_redirect(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wp_ngrok_autoload'] ) ) {
			$protocol = is_ssl() ? 'https://' : 'http://';

			$request  = wp_remote_get(
				add_query_arg(
					'wp_ngrok_autoload',
					1,
					$protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
				)
			);
			$response = wp_remote_retrieve_body( $request );

			if ( defined( 'WP_LOCAL_NGROK_URL' ) ) {
				$ngrok_url = WP_LOCAL_NGROK_URL;
			} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
				$parsed_ngrok_url = wp_parse_url( $_SERVER['HTTP_REFERER'] );
				$ngrok_url        = $parsed_ngrok_url['scheme'] . '://' . $parsed_ngrok_url['host'];
			} else {
				$ngrok_url = wp_make_link_relative( $this->site_url );
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo str_replace(
				$this->site_url,
				$ngrok_url,
				$response
			);
			exit;
		}
	}
}

new Ngrok_Local();
