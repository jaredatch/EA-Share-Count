<?php
/**
 * Admin class.
 *
 * Contains functionality for the admin dashboard (is_admin()).
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.3.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
class EA_Share_Count_Admin {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {

		// Settings.
		add_action( 'admin_init',                                 array( $this, 'settings_init'   )        );
		add_action( 'admin_menu',                                 array( $this, 'settings_add'    )        );
		add_action( 'admin_enqueue_scripts',                      array( $this, 'settings_assets' )        );
		add_filter( 'plugin_action_links_' . EA_SHARE_COUNT_BASE, array( $this, 'settings_link'   )        );
		add_filter( 'plugin_row_meta',                            array( $this, 'author_links'    ), 10, 2 );

		// Metabox.
		add_action( 'admin_init',                                 array( $this, 'metabox_add'     )        );
		add_action( 'wp_ajax_ea_share_refresh',                   array( $this, 'metabox_ajax'    )        );
		add_action( 'admin_enqueue_scripts',                      array( $this, 'metabox_assets'  )        );
		add_action( 'save_post',                                  array( $this, 'metabox_save'    ), 10, 2 );
	}

	// ********************************************************************** //
	//
	// Settings - these methods wrangle our settings and related functionality.
	//
	// ********************************************************************** //

	/**
	 * Return the settings options values.
	 *
	 * Used globally. Options are filterable.
	 *
	 * @since 1.3.0
	 */
	public function options() {

		$options = get_option( 'ea_share_count_options', $this->settings_default() );

		return apply_filters( 'ea_share_count_options', $options );
	}

	/**
	 * Initialize the Settings page options.
	 *
	 * @since 1.1.0
	 */
	public function settings_init() {

		register_setting( 'ea_share_count_options', 'ea_share_count_options', array( $this, 'settings_sanitize' ) );
	}

	/**
	 * Add the Settings page.
	 *
	 * @since 1.1.0
	 */
	public function settings_add() {

		add_options_page( __( 'EA Share Count Settings', 'ea-share-count' ), __( 'Share Count', 'ea-share-count' ), 'manage_options', 'ea_share_count_options', array( $this, 'settings_page' ) );
	}

	/**
	 * Build the Settings page.
	 *
	 * @since 1.1.0
	 */
	public function settings_page() {

		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'EA Share Count Settings', 'ea-share-count' ); ?></h1>

			<p><?php esc_html_e( 'Welcome to EA Share Count. Our goal is to display share count badges on your site, with just the right amount of options, in a manner that keeps your site fast.', 'ea-share-count' ); ?></p>

			<form method="post" action="<?php echo admin_url( 'options.php' ); ?>" id="easc-settings-form">

				<?php
				settings_fields( 'ea_share_count_options' );
				$options = get_option( 'ea_share_count_options', $this->settings_default() );
				?>

				<!-- Count Settings, as in the numbers -->

				<h2 class="title"><?php esc_html_e( 'Share Counts', 'ea-share-count' ); ?></h2>

				<table class="form-table">

					<!-- Count Source -->
					<tr valign="top" id="easc-setting-row-count_source">
						<th scope="row"><label for="easc-setting-count_source"><?php esc_html_e( 'Count Source', 'ea-share-count' ); ?></label></th>
						<td>
							<select name="ea_share_count_options[count_source]" id="easc-setting-count_source">
								<?php
								$opts = array(
									'none'        => __( 'None', 'ea-share-count' ),
									'sharedcount' => __( 'SharedCount.com', 'ea-share-count' ),
									'native'      => __( 'Native', 'ea-share-count' ),
								);
								foreach ( $opts as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $key, $this->settings_value( 'count_source' ), false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description" style="margin-bottom: 10px;">
								<?php esc_html_e( 'This determines the source of the share counts.', 'ea-share-count' ); ?>
							</p>
							<p class="description" style="margin-bottom: 10px;">
								<?php _e( '<strong>None</strong>: no counts are displayed and your website will not connect to an outside API, useful if you want simple badges without the counts or associated overhead.', 'ea-share-count' ); ?>
							</p>
							<p class="description" style="margin-bottom: 10px;">
								<?php _e( '<strong>SharedCount.com</strong>: counts are retrieved from the SharedCount.com API. This is our recommended option for those wanting share counts. This method allows fetching all counts for with only 2 API calls, so it is best for performance.', 'ea-share-count' ); ?>
							</p>
							<p class="description">
								<?php _e( '<strong>Native</strong>: counts are retrieved from their native service. Eg Facebook API for Facebook counts, Pinterest API for Pin counts, etc. This method is more "expensive" since depending on the counts desired uses more API calls (6 API calls if all services are enabled).', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

					<!-- ShareCount API Key (ShareCount only) -->
					<tr valign="top" id="easc-setting-row-sharedcount_key">
						<th scope="row"><label for="easc-setting-sharedcount_key"><?php esc_html_e( 'SharedCount API Key', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="text" name="ea_share_count_options[sharedcount_key]" value="<?php echo esc_attr( $this->settings_value( 'sharedcount_key' ) ); ?>" class="regular-text" />
							<p class="description">
								<?php _e( 'Sign up on SharedCount.com for your (free) API key. SharedCount provides 1,000 API requests daily, or 10,000 request daily if you connect to Facebook. With our caching, this works with sites that receive millions of page views a month and is adaquate for most sites.', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

					<!-- Twitter Counts (SharedCount only) -->
					<tr valign="top" id="easc-setting-row-twitter_counts">
						<th scope="row"><label for="easc-setting-twitter_counts"><?php esc_html_e( 'Include Twitter Counts', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="checkbox" name="ea_share_count_options[twitter_counts]" value="1" id="easc-setting-twitter_counts" <?php checked( $this->settings_value( 'twitter_counts' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'SharedCount.com does not provide Twitter counts. Checking this option will seperately pull Twitter counts from NewShareCounts.com, which is the service that tracks Twitter counts.', 'ea-share-count' ); ?><br><a href="http://newsharecounts.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sign up for NewShareCounts.com (free).', 'ea-share-count' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Retrieve Share Counts From (Native only) -->
					<tr valign="top" id="easc-setting-row-service">
						<th scope="row"><?php esc_html_e( 'Retrieve Share Counts From', 'ea-share-count' ); ?></th>
						<td>
							<fieldset>
							<?php
							$services = $this->query_services();
							foreach ( $services as $service ) {
								echo '<label for="easc-setting-service-' . sanitize_html_class( $service['key'] ) . '">';
									printf(
										'<input type="checkbox" name="ea_share_count_options[query_services][]" value="%s" id="easc-setting-service-%s" %s>',
										esc_attr( $service['key'] ),
										sanitize_html_class( $service['key'] ),
										checked( in_array( $service['key'], $this->settings_value( 'query_services' ), true ), true, false )
									);
									echo esc_html( $service['label'] );
								echo '</label><br />';
							}
							?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Each service requires a separate API request, so using many services could cause performance issues. Alternately, consider using SharedCounts for the count source.', 'ea-share-count' ); ?>
								<br><br><?php esc_html_e( 'Twitter does provide counts; Twitter share counts will pull from NewShareCounts.com, which is the service that tracks Twitter counts.', 'ea-share-count' ); ?><br><a href="http://newsharecounts.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sign up for NewShareCounts.com (free).', 'ea-share-count' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Facebook Access Token (Native only) -->
					<tr valign="top" id="easc-setting-row-fb_access_token">
						<th scope="row"><label for="easc-setting-fb_access_token"><?php esc_html_e( 'Facebook Access Token', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="text" name="ea_share_count_options[fb_access_token]" value="<?php echo esc_attr( $this->settings_value( 'fb_access_token' ) ); ?>" id="easc-setting-fb_access_token" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'If you have trouble receiving Facebook counts, you may need to setup an access token.', 'ea-share-count' ); ?><br><a href="https://smashballoon.com/custom-facebook-feed/access-token/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Follow these instructions.', 'ea-share-count' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Count Total Only (SharedCount / Native only) -->
					<tr valign="top" id="easc-setting-row-total_only">
						<th scope="row"><label for="easc-setting-total_only"><?php esc_html_e( 'Count Total Only', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="checkbox" name="ea_share_count_options[total_only]" value="1" id="easc-setting-total_only" <?php checked( $this->settings_value( 'total_only' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Check this if you would like to only display the share count total. This is useful if you would like to display the total counts (via Total Counts button) but not the individual counts for each service.', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

					<!-- Empty Counts (SharedCount / Native only) -->
					<tr valign="top" id="easc-setting-row-hide_empty">
						<th scope="row"><label for="easc-setting-hide_empty"><?php esc_html_e( 'Hide Empty Counts', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="checkbox" name="ea_share_count_options[hide_empty]" value="1" id="easc-setting-hide_empty" <?php checked( $this->settings_value( 'hide_empty' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Optionally, empty counts (0) can be hidden.', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

					<!-- Preserve non-HTTPS counts -->
					<?php if ( is_ssl() ) : ?>
					<tr valign="top" id="easc-setting-row-preserve_http">
						<th scope="row"><label for="easc-setting-preserve_http"><?php esc_html_e( 'Preserve HTTP Counts', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="checkbox" name="ea_share_count_options[preserve_http]" value="1" id="easc-setting-preserve_http" <?php checked( $this->settings_value( 'hide_empty' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Check this if you would also like to include non-SSL (http://) share counts. This is useful if the site was originally used http:// but has since moved to https://. Enabling this option will double the API calls. ', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>
					<?php endif; ?>

				</table>

				<hr />

				<!-- Display settings -->

				<h2 class="title"><?php esc_html_e( 'Display', 'ea-share-count' ); ?></h2>

				<table class="form-table">

					<!-- Buttons Display -->
					<tr valign="top" id="easc-setting-row-included_services">
						<th scope="row"><?php esc_html_e( 'Share Buttons to Display', 'ea-share-count' ); ?></th>
						<td>
							<select name="ea_share_count_options[included_services][]" id="easc-setting-included_services" class="share-count-services" multiple="multiple" style="min-width:350px;">
								<?php
								$services = array(
									'facebook'        => 'Facebook',
									'facebook_likes'  => 'Facebook Like',
									'facebook_shares' => 'Facebook Share',
									'twitter'         => 'Twitter',
									'pinterest'       => 'Pinterest',
									'linkedin'        => 'LinkedIn',
									'google'          => 'Google+',
									'stumbleupon'     => 'Stumble Upon',
									'included_total'  => 'Total Counts',
									'print'           => 'Print',
									'email'           => 'Email',
								);
								$services = apply_filters( 'ea_share_count_admin_services', $services );
								$selected = $this->settings_value( 'included_services' );

								// Output selected elements first to preserve order.
								foreach ( $selected as $opt ) {
									if ( isset( $services[ $opt ] ) ) {
										printf(
											'<option value="%s" selected>%s</option>',
											esc_attr( $opt ),
											esc_html( $services[ $opt ] )
										);
										unset( $services[ $opt ] );
									}
								}
								// Now output other items.
								foreach ( $services as $key => $label ) {
									printf(
										'<option value="%s">%s</option>',
										esc_attr( $key ),
										esc_html( $label )
									);
								}
								?>
							</select>
						</td>
					</tr>

					<!-- Enable Email reCAPTCHA (if email button is configured) -->
					<tr valign="top" id="easc-setting-row-recaptcha">
						<th scope="row"><label for="easc-setting-recaptcha"><?php esc_html_e( 'Enable Email reCAPTCHA', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="checkbox" name="ea_share_count_options[recaptcha]" value="1" id="easc-setting-recaptcha" <?php checked( $this->settings_value( 'recaptcha' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Highly recommended, Google\'s v2 reCAPTCHA will protect the email sharing feature from abuse.', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

					<!-- Google reCAPTCHA Site key (if recaptcha is enabled) -->
					<tr valign="top" id="easc-setting-row-recaptcha_site_key">
						<th scope="row"><label for="easc-setting-recaptcha_site_key"><?php esc_html_e( 'reCAPTCHA Site Key', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="text" name="ea_share_count_options[recaptcha_site_key]" value="<?php echo esc_attr( $this->settings_value( 'recaptcha_site_key' ) ); ?>" id="easc-setting-recaptcha_site_key" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'After signing up for Google\'s v2 reCAPTCHA (free), provide your site key here.', 'ea-share-count' ); ?><br><a href="https://www.google.com/recaptcha/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more.', 'ea-share-count' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Google reCAPTCHA Secret key (if recaptcha is enabled) -->
					<tr valign="top" id="easc-setting-row-recaptcha_secret_key">
						<th scope="row"><label for="easc-setting-recaptcha_secret_key"><?php esc_html_e( 'reCAPTCHA Secret Key', 'ea-share-count' ); ?></label></th>
						<td>
							<input type="text" name="ea_share_count_options[recaptcha_secret_key]" value="<?php echo esc_attr( $this->settings_value( 'recaptcha_secret_key' ) ); ?>" id="easc-setting-recaptcha_secret_key" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'After signing up for Google\'s v2 reCAPTCHA (free), provide your secret key here.', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

					<!-- Button style -->
					<tr valign="top" id="easc-setting-row-style">
						<th scope="row"><label for="easc-setting-style"><?php esc_html_e( 'Share Button Style', 'ea-share-count' ); ?></label></th>
						<td>
							<select name="ea_share_count_options[style]" id="easc-setting-style">
								<?php
								$opts = apply_filters( 'ea_share_count_styles', array(
									'fancy'  => esc_html__( 'Fancy', 'ea-share-count' ),
									'gss'    => esc_html__( 'Slim', 'ea-share-count' ),
									'bubble' => esc_html__( 'Bubble', 'ea-share-count' ),
								) );
								foreach ( $opts as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $key, $this->settings_value( 'style' ), false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php printf( __( 'Three different share button counts are available; see <a href="%s" target="_blank" rel="noopener noreferrer">our GitHub page</a> for screenshots.', 'ea-share-count' ), 'https://github.com/jaredatch/EA-Share-Count' ); ?>
							</p>
						</td>
					</tr>

					<!-- Theme location -->
					<tr valign="top" id="easc-setting-row-theme_location">
						<th scope="row"><label for="easc-setting-theme_location"><?php esc_html_e( 'Theme Location', 'ea-share-count' ); ?></label></th>
						<td>
							<select name="ea_share_count_options[theme_location]" id="easc-setting-theme_location">
								<?php
								$opts = array(
									''                     => esc_html__( 'None', 'ea-share-count' ),
									'before_content'       => esc_html__( 'Before Content', 'ea-share-count' ),
									'after_content'        => esc_html__( 'After Content',  'ea-share-count' ),
									'before_after_content' => esc_html__( 'Before and After Content', 'ea-share-count' ),
								);
								foreach ( $opts as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $key, $this->settings_value( 'theme_location' ), false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Automagically add the share buttons before and/or after your post content.', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

					<!-- Supported Post Types (Hide if theme location is None) -->
					<tr valign="top" id="easc-setting-row-post_type">
						<th scope="row"><?php esc_html_e( 'Supported Post Types', 'ea-share-count' ); ?></th>
						<td>
							<fieldset>
							<?php
							$opts = get_post_types(
								array(
									'public' => true,
								),
								'names'
							);
							if ( isset( $opts['attachment'] ) ) {
								unset( $opts['attachment'] );
							}
							foreach ( $opts as $post_type ) {
								echo '<label for="easc-setting-post_type-' . sanitize_html_class( $post_type ) . '">';
									printf(
										'<input type="checkbox" name="ea_share_count_options[post_type][]" value="%s" id="easc-setting-post_type-%s" %s>',
										esc_attr( $post_type ),
										sanitize_html_class( $post_type ),
										checked( in_array( $post_type, $this->settings_value( 'post_type'), true ), true, false )
									);
									echo esc_html( $post_type );
								echo '</label><br/>';
							}
							?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Which content type(s) you would like to display the share buttons on.', 'ea-share-count' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'ea-share-count' ); ?>" />
				</p>

			</form>

		</div>
		<?php
	}

	/**
	 * Load settings page assets
	 *
	 * @since 1.0.0
	 * @param string $hook
	 */
	public function settings_assets( $hook ) {

		if ( 'settings_page_ea_share_count_options' === $hook ) {

			// Choices CSS.
			wp_enqueue_style(
				'choices',
				EA_SHARE_COUNT_URL . 'assets/css/choices.css',
				array(),
				'3.0.2'
			);

			// Select2 JS library.
			wp_enqueue_script(
				'choices',
				EA_SHARE_COUNT_URL . 'assets/js/choices.min.js',
				array( 'jquery' ),
				'3.0.2',
				false
			);

			// jQuery Conditions JS library.
			wp_enqueue_script(
				'jquery-conditionals',
				EA_SHARE_COUNT_URL . 'assets/js/jquery.conditions.min.js',
				array( 'jquery' ),
				'1.0.0',
				false
			);

			// Our settings JS.
			wp_enqueue_script(
				'share-count-settings',
				EA_SHARE_COUNT_URL . 'assets/js/admin-settings.js',
				array( 'jquery' ),
				EA_SHARE_COUNT_VERSION,
				false
			);
		}
	}

	/**
	 * Default settings values.
	 *
	 * @since 1.1.0
	 */
	public function settings_default() {

		return array(
			'count_source'          => 'none',
			'fb_access_token'       => '',
			'sharedcount_key'       => '',
			'twitter_counts'        => '',
			'style'                 => '',
			'total_only'            => '',
			'hide_empty'            => '',
			'preserve_http'         => '',
			'post_type'             => array( 'post' ),
			'theme_location'        => '',
			'included_services'     => array( 'facebook', 'twitter', 'pinterest' ),
			'query_services'        => array(),
			'recaptcha'             => '',
			'recpatcha_site_key'    => '',
			'recaptcha_secret_key'  => '',
		);
	}

	/**
	 * Return settings value.
	 *
	 * @since 1.7.0
	 * @param string $key
	 * @return bool|string
	 */
	function settings_value( $key = false ) {

		$defaults = $this->settings_default();
		$options  = get_option( 'ea_share_count_options', $defaults );

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		} elseif ( isset( $defaults[ $key ] ) ) {
			return $defaults[ $key ];
		} else {
			return false;
		}
	}

	/**
	 * Query Services
	 *
	 * @since 1.7.0
	 * @return array $services
	 */
	function query_services() {

		$services = array(
			array(
				'key'   => 'facebook',
				'label' => 'Facebook',
			),
			array(
				'key'   => 'twitter',
				'label' => 'Twitter',
			),
			array(
				'key'   => 'pinterest',
				'label' => 'Pinterest',
			),
			array(
				'key'   => 'linkedin',
				'label' => 'LinkedIn',
			),
			array(
				'key'   => 'stumbleupon',
				'label' => 'StumbleUpon',
			),
		);

		$services = apply_filters( 'ea_share_count_query_services', $services );

		return $services;
	}

	/**
	 * Sanitize saved settings.
	 *
	 * @since 1.1.0
	 * @param array $input
	 * @return array
	 */
	public function settings_sanitize( $input ) {

		// Reorder services based on the order they were provided.
		$input['count_source']         = sanitize_text_field( $input['count_source'] );
		$input['total_only']           = isset( $input['total_only'] ) ? '1' : '';
		$input['hide_empty']           = isset( $input['hide_empty'] ) ? '1' : '';
		$input['preserve_http']        = isset( $input['preserve_http'] ) ? '1' : '';
		$input['query_services']       = isset( $input['query_services'] ) ? array_map( 'sanitize_text_field', $input['query_services'] ) : array();
		$input['fb_access_token']      = sanitize_text_field( $input['fb_access_token'] );
		$input['sharedcount_key']      = sanitize_text_field( $input['sharedcount_key'] );
		$input['twitter_counts']       = isset( $input['twitter_counts'] ) ? '1' : '';
		$input['style']                = sanitize_text_field( $input['style'] );
		$input['post_type']            = isset( $input['post_type'] ) ? array_map( 'sanitize_text_field', $input['post_type'] ) : array();
		$input['theme_location']       = sanitize_text_field( $input['theme_location'] );
		$input['included_services']    = isset( $input['included_services'] ) ? array_map( 'sanitize_text_field', $input['included_services'] ) : array();
		$input['recaptcha']            = isset( $input['recaptcha'] ) ? '1' : '';
		$input['recaptcha_site_key']   = sanitize_text_field( $input['recaptcha_site_key'] );
		$input['recaptcha_secret_key'] = sanitize_text_field( $input['recaptcha_secret_key'] );

		return $input;
	}

	/**
	 * Add settings link to the Plugins page.
	 *
	 * @since 1.3.0
	 * @param array $links
	 * @return array $links
	 */
	public function settings_link( $links ) {

		$setting_link = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'ea_share_count_options' ), admin_url( 'options-general.php' ) ), __( 'Settings', 'ea-share-count' ) );
		array_unshift( $links, $setting_link );
		return $links;
	}

	/**
	 * Plugin author name links.
	 *
	 * @since 1.5.2
	 * @param array $links
	 * @param string $file
	 * @return string
	 */
	function author_links( $links, $file ) {

		if ( strpos( $file, 'ea-share-count.php' ) !== false ) {
			$links[1] = 'By <a href="http://www.billerickson.net">Bill Erickson</a> & <a href="http://www.jaredatchison.com">Jared Atchison</a>';
		}
		return $links;
	}

	// ********************************************************************** //
	//
	// Metabox - these methods register and handle the post edit metabox.
	//
	// ********************************************************************** //

	/**
	 * Initialize the metabox for supported post types.
	 *
	 * @since 1.3.0
	 */
	public function metabox_add() {

		$options = $this->options();

		// If we are not collecting share counts, disable the metabox.
		if ( ! empty( $options['count_source'] ) && 'none' === $options['count_source'] ) {
			return;
		}

		if ( ! empty( $options['post_type'] ) ) {
			$post_types = (array) $options['post_type'];
			foreach ( $post_types as $post_type ) {
				add_meta_box( 'ea-share-count-metabox', __( 'Share Counts', 'ea-share-count' ), array( $this, 'metabox' ), $post_type, 'side', 'low' );
			}
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @since 1.3.0
	 */
	public function metabox() {

		global $post;

		if ( 'publish' !== $post->post_status ) {
			echo '<p>' . __( 'Entry must be published to view share counts.', 'ea-share-count' ) . '</p>';
			return;
		}

		$counts = get_post_meta( $post->ID, 'ea_share_count', true );

		if ( ! empty( $counts ) ) {
			$counts = json_decode( $counts, true );
			echo '<ul id="ea-share-count-list">';
				echo $this->metabox_counts( $counts, $post->ID );
			echo '</ul>';
			$date = get_post_meta( $post->ID, 'ea_share_count_datetime', true );
			$date = $date + ( get_option( 'gmt_offset' ) * 3600 );
			echo '<p id="ea-share-count-date">' . __( 'Last updated', 'ea-share-count' ) . ' ' . date( 'M j, Y g:ia', $date ) . '</span></p>';
		} else {
			echo '<p id="ea-share-count-empty">' . __( 'No share counts downloaded for this entry', 'ea-share-count' ) . '</p>';
		}

		echo '<button class="button" id="ea-share-count-refresh" data-nonce="' . wp_create_nonce( 'ea-share-count-refresh-' . $post->ID ) . '" data-postid="' . $post->ID . '">' . __( 'Refresh Share Counts', 'ea-share-count' ) . '</button>';

		wp_nonce_field( 'ea_share_count', 'ea_share_count_nonce' );
		$exclude = intval( get_post_meta( $post->ID, 'ea_share_count_exclude', true ) );
		$post_type_object = get_post_type_object( get_post_type( $post->ID ) );
		echo '<p><input type="checkbox" name="ea_share_count_exclude" id="ea_share_count_exclude" value="' . $exclude . '" ' . checked( 1, $exclude, false ) . ' /> <label for="ea_share_count_exclude">' . __( 'Don\'t display buttons on this', 'ea-share-count' ) . ' ' . strtolower( $post_type_object->labels->singular_name ) . '</label></p>';
	}

	/**
	 * Build the metabox list item counts.
	 *
	 * @since 1.3.0
	 * @param array $counts
	 * @param int $post_id
	 * @return string
	 */
	public function metabox_counts( $counts, $post_id ) {

		if ( empty( $counts ) || ! is_array( $counts ) ) {
			return;
		}

		$output  = '';
		$output .= '<li>Facebook Total: <strong>' . ( ! empty( $counts['Facebook']['total_count'] ) ? number_format( absint( $counts['Facebook']['total_count'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Facebook Likes: <strong>' . ( ! empty( $counts['Facebook']['like_count'] ) ? number_format( absint( $counts['Facebook']['like_count'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Facebook Shares: <strong>' . ( ! empty( $counts['Facebook']['share_count'] ) ? number_format( absint( $counts['Facebook']['share_count'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Facebook Comments: <strong>' . ( ! empty( $counts['Facebook']['comment_count'] ) ? number_format( absint( $counts['Facebook']['comment_count'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Twitter: <strong>' . ( ! empty( $counts['Twitter'] ) ? number_format( absint( $counts['Twitter'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Pinterest: <strong>' . ( ! empty( $counts['Pinterest'] ) ? number_format( absint( $counts['Pinterest'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>LinkedIn: <strong>' . ( ! empty( $counts['LinkedIn'] ) ? number_format( absint( $counts['LinkedIn'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>StumbleUpon: <strong>' . ( ! empty( $counts['StumbleUpon'] ) ? number_format( absint( $counts['StumbleUpon'] ) ) : '0'  ) . '</strong></li>';

		// Show Email shares if enabled.
		$options = $this->options();
		if ( in_array( 'email', $options['included_services'], true ) ) {
			$output .= '<li>Email: <strong>' . absint( get_post_meta( $post_id, 'ea_share_count_email', 'true' ) ) . '</strong></li>';
		}

		return $output;
	}

	/**
	 * Metabox AJAX functionality.
	 *
	 * @since 1.3.0
	 */
	function metabox_ajax() {

		// Run a security check.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ea-share-count-refresh-' . $_POST['post_id'] ) ) {
			wp_send_json_error(
				array(
					'msg'   => __( 'Failed security', 'ea-share-count' ),
					'class' => 'error',
				)
			);
		}

		// Check for permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'msg'   => __( 'You do not have permission', 'ea-share-count' ),
					'class' => 'error',
				)
			);
		}

		$id     = absint( $_POST['post_id'] );
		$counts = ea_share()->core->counts( $id, true, true );
		$date   = '<p id="ea-share-count-date">Last updated ' . date( 'M j, Y g:ia', time() + ( get_option( 'gmt_offset' ) * 3600 ) ) . '</span></p>';
		$list   = '<ul id="ea-share-count-list">' . $this->metabox_counts( $counts, $id ) . '<ul>';

		wp_send_json_success( array(
			'msg'    => __( 'Share counts updated.', 'ea-share-count' ),
			'class'  => 'success',
			'date'   => $date,
			'list'   => $list,
			'counts' => $counts,
		) );
	}

	/**
	 * Load metabox assets.
	 *
	 * @since 1.0.0
	 * @param string $hook
	 */
	public function metabox_assets( $hook ) {

		global $post;
		$options = $this->options();

		if ( empty( $options['post_type'] ) ) {
			return;
		}

		if ( 'post.php' === $hook && in_array( $post->post_type, $options['post_type'], true ) ) {
			wp_enqueue_script(
				'share-count-settings',
				EA_SHARE_COUNT_URL . 'assets/js/admin-metabox.js',
				array( 'jquery' ),
				EA_SHARE_COUNT_VERSION,
				false
			);
		}
	}

	/**
	 * Save the Metabox.
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param object $post
	 */
	function metabox_save( $post_id, $post ) {

		// Security check.
		if ( ! isset( $_POST['ea_share_count_nonce'] ) || ! wp_verify_nonce( $_POST['ea_share_count_nonce'], 'ea_share_count' ) ) {
			return;
		}

		// Bail out if running an autosave, ajax, cron.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		// Bail out if the user doesn't have the correct permissions to update the slider.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['ea_share_count_exclude'] ) ) {
			update_post_meta( $post_id, 'ea_share_count_exclude', 1 );
		} else {
			delete_post_meta( $post_id, 'ea_share_count_exclude' );
		}
	}
}
