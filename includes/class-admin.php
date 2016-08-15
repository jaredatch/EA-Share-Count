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

		// Settings
		add_action( 'admin_init',                                 array( $this, 'settings_init'   )        );
		add_action( 'admin_menu',                                 array( $this, 'settings_add'    )        );
		add_action( 'admin_enqueue_scripts',                      array( $this, 'settings_assets' )        );
		add_filter( 'plugin_action_links_' . EA_SHARE_COUNT_BASE, array( $this, 'settings_link'   )        );
		add_filter( 'plugin_row_meta',                            array( $this, 'author_links'    ), 10, 2 );
		// Metabox
		add_action( 'admin_init',                                 array( $this, 'metabox_add'     )        );
		add_action( 'wp_ajax_ea_share_refresh',                   array( $this, 'metabox_ajax'    )        );
		add_action( 'admin_enqueue_scripts',                      array( $this, 'metabox_assets'  )        );
		add_action( 'save_post',                                  array( $this, 'metabox_save'    ), 10, 2 );
		// Notices
		add_action( 'admin_notices',                              array( $this, 'admin_notices' )          );
		add_action( 'admin_enqueue_scripts',                      array( $this, 'notice_assets' )          );
		add_action( 'wp_ajax_ea_share_count_dismissible_notice',  array( $this, 'notice_dismissal_ajax'  ) );
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
		add_options_page( __( 'Share Count Settings', 'ea-share-count' ), __( 'Share Count', 'ea-share-count' ), 'manage_options', 'ea_share_count_options', array( $this, 'settings_page' ) );
	}
	
	/**
	 * Build the Settings page.
	 * 
	 * @since 1.1.0
	 */
	public function settings_page() {
		?>
		<div class="wrap">

			<h2><?php _e( 'Share Count Settings', 'ea-share-count' );?></h2>

			<form method="post" action="options.php">

				<?php 
				settings_fields( 'ea_share_count_options' );
				$options = get_option( 'ea_share_count_options', $this->settings_default() ); 
				?>

				<table class="form-table">

					<tr valign="top">
						<th scope="row"><?php _e( 'Retrieve Share Counts From', 'ea-share-count' );?></th>
						<td>
							<fieldset>
							<?php 
							$services = $this->query_services();
							foreach( $services as $service ) {
								echo '<label for="ea-query-service-' . sanitize_html_class( $service['key'] )  . '">';
									echo '<input type="checkbox" name="ea_share_count_options[query_services][]" value="' . esc_attr( $service['key'] ). '" id="ea-query-service-' . sanitize_html_class( $service['key'] ) . '" ' . checked( in_array( $service['key'], $this->settings_value( 'query_services') ), true, false ) . ' ' . disabled( $service['disabled'], true, false ) . '>';
									echo esc_html( $service['label'] );
									if( $service['disabled'] && isset( $service['disabled_message'] ) )
										echo ' - <em>' . esc_html( $service['disabled_message'] ) . '</em>';
								echo '</label>';
								echo '<br>';
							}
							?>
							</fieldset>
							<p><?php _e( 'Each service requires a separate API request, so using many services could cause performance issues.', 'ea-share-count' );?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Facebook Access Token', 'ea-share-count' );?></th>
						<td>
							<input type="text" name="ea_share_count_options[fb_access_token]" value="<?php echo $this->settings_value( 'fb_access_token' ); ?>" class="regular-text" /><br />
							<a href="https://smashballoon.com/custom-facebook-feed/access-token/" target="_blank"><?php _e( 'Follow these instructions to get a Facebook Access Token', 'ea-share-count' );?></a>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Share Buttons to Display', 'ea-share-count' );?></th>
						<td>
							<input type="hidden" name="ea_share_count_options[included_services_raw]" value="<?php echo $this->settings_value( 'included_services_raw' );?>" class="share-count-services-raw">
							<select name="ea_share_count_options[included_services][]" class="share-count-services" multiple="multiple" style="min-width:350px;">
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
							if ( !empty( $options['included_services_raw'] ) ) {
								$services = array_merge( array_flip( $options['included_services'] ), $services );
							}
							foreach( $services as $key => $service ) {
								echo '<option value="' . $key . '" ' . selected( in_array( $key, $this->settings_value( 'included_services' ) ), true, false ) . '>' . $service . '</option>';
							}
							?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Share Button Style', 'ea-share-count' );?></th>
						<td>
							<select name="ea_share_count_options[style]">
							<?php
							$styles = array( 'fancy' => 'Fancy', 'gss' => 'Slim', 'bubble' => 'Bubble' );
							$styles = apply_filters( 'ea_share_count_styles', $styles );
							foreach( $styles as $key => $label ) {
								echo '<option value="' . $key . '" ' . selected( $key, $this->settings_value( 'style' ), false ) . '>' . $label . '</option>';
							}
							?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Theme Location', 'ea-share-count' );?></th>
						<td>
							<select name="ea_share_count_options[theme_location]">
								<?php
								$locations = array( 
									''                     => __( 'None', 'ea-share-count' ), 
									'before_content'       => __( 'Before Content', 'ea-share-count' ), 
									'after_content'        => __( 'After Content',  'ea-share-count' ), 
									'before_after_content' => __( 'Before and After Content', 'ea-share-count' ), 
								);
								foreach( $locations as $key => $label ) {
									echo '<option value="' . $key . '" ' . selected( $key, $this->settings_value( 'theme_location' ), false ) . '>' . $label . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row"><?php _e( 'Supported Post Types', 'ea-share-count' );?></th>
						<td>
							<fieldset>
							<?php 
							$post_types = get_post_types( array( 'public' => true ), 'names' );
							if ( isset( $post_types['attachment'] ) ) {
								unset( $post_types['attachment'] );
							}
							foreach( $post_types as $post_type ) {
								echo '<label for="ea-cpt-' . sanitize_html_class( $post_type )  . '">';
									echo '<input type="checkbox" name="ea_share_count_options[post_type][]" value="' . esc_attr( $post_type ). '" id="ea-cpt-' . sanitize_html_class( $post_type ) . '" ' . checked( in_array( $post_type, $this->settings_value( 'post_type') ), true, false ) . '>';
									echo esc_html( $post_type );
								echo '</label>';
								echo '<br>';
							}
							?>
							</fieldset>
						</td>
					</tr>		

					<tr valign="top">
						<th scope="row"><?php _e( 'Share Count Number', 'ea-share-count' );?></th>
						<td>
							<select name="ea_share_count_options[number]">
							<?php
							$number = array( 'all' => 'All Services', 'total' => 'Total Only' );
							foreach( $number as $key => $label ) {
								echo '<option value="' . $key . '" ' . selected( $key, $this->settings_value( 'number' ), false ) . '>' . $label . '</option>';
							}
							?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Show Empty Counts', 'ea-share-count' );?></th>
						<td>
							<select name="ea_share_count_options[show_empty]">
							<?php
							$show_empty = array( 'true' => 'Yes', 'false' => 'No' );
							foreach( $show_empty as $key => $label ) {
								echo '<option value="' . $key . '" ' . selected( $key, $this->settings_value( 'show_empty' ), false ) . '>' . $label . '</option>';
							}
							?>
							</select>
						</td>
					</tr>

				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'ea-share-count' ); ?>" />
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

		if ( 'settings_page_ea_share_count_options' == $hook ) {

			wp_enqueue_style( 'select2', EA_SHARE_COUNT_URL . 'assets/css/select2.css', array(), EA_SHARE_COUNT_VERSION );
			wp_enqueue_script( 'select2', EA_SHARE_COUNT_URL  . 'assets/js/select2.min.js', array( 'jquery' ), EA_SHARE_COUNT_VERSION, false );
			wp_enqueue_script( 'share-count-settings', EA_SHARE_COUNT_URL . 'assets/js/admin-settings.js', array( 'jquery' ), EA_SHARE_COUNT_VERSION, false );
		}
	}

	/**
	 * Default settings values.
	 * 
	 * @since 1.1.0
	 */
	public function settings_default() {

		return array( 
			'fb_access_token'       => '',
			'style'                 => '',
			'number'                => 'all',
			'show_empty'            => 'true',
			'post_type'             => array( 'post' ),
			'theme_location'        => '',
			'included_services'     => array( 'facebook', 'twitter', 'pinterest' ),
			'included_services_raw' => 'facebook,twitter,pinterest',
			'query_services'        => array(),
			'dismissed_notices'     => array(),
		);
	}
	
	/**
	 * Return settings value.
	 *
	 * @since 1.7.0
	 */
	function settings_value( $key = false ) {

		$defaults = $this->settings_default();		
		$options  = get_option( 'ea_share_count_options', $defaults ); 

		if( isset( $options[$key] ) )
			return $options[$key];

		elseif( isset( $defaults[$key] ) )
			return $defaults[$key];

		else
			return false;
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
				'key'              => 'facebook',
				'label'            => 'Facebook',
				'disabled'         => empty( $this->settings_value( 'fb_access_token' ) ),
				'disabled_message' => 'You must provide a Facebook Access Token'
			),
			array(
				'key'              => 'pinterest',
				'label'            => 'Pinterest',
				'disabled'         => false,
			),
			array(
				'key'              => 'linkedin',
				'label'            => 'LinkedIn',
				'disabled'         => false,
			),
			array(
				'key'              => 'google',
				'label'            => 'Google+',
				'disabled'         => false,
			),
			array(
				'key'              => 'stumbleupon',
				'label'            => 'StumbleUpon',
				'disabled'         => false,
			)
		);

		$services = apply_filters( 'ea_share_count_query_services', $services );
		return $services;

	}
	
	/**
	 * Sanitize saved settings.
	 * 
	 * @since 1.1.0
	 */
	public function settings_sanitize( $input ) {

		// Reorder services based on the order they were provided
		$services_array = array();
		$services_raw   = explode( ',', $input['included_services_raw'] );
		foreach( $services_raw as $service ) {
			$services_array[] = $service;
		}

		$input['query_services']        = array_map( 'esc_attr', $input['query_services'] );
		$input['fb_access_token']       = esc_attr( $input['fb_access_token'] );
		$input['style']                 = esc_attr( $input['style'] );
		$input['number']                = esc_attr( $input['number'] );
		$input['show_empty']            = esc_attr( $input['show_empty'] );
		$input['post_type']             = array_map( 'esc_attr', $input['post_type'] );
		$input['theme_location']        = esc_attr( $input['theme_location'] );
		$input['included_services']     = array_map( 'esc_attr', $services_array );
		$input['included_services_raw'] = esc_attr( $input['included_services_raw'] );
		
		// Remove query services if they are disabled
		$services = $this->query_services();
		foreach( $services as $service ) {
			if( $service['disabled'] && in_array( $service['key'], $input['query_services'] ) ) {
				$key = array_search( $service['key'], $input['query_services'] );
				unset( $input['query_services'][$key] );
			}
				
		}
		
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
	 * Plugin author name links
	 *
	 * @since 1.5.2
	 * @param array $link
	 * @param string $file
	 */
	function author_links( $links, $file ) {

		if ( strpos( $file, 'ea-share-count.php' ) !== false ) {
			$links[1] = 'By <a href="http://www.billerickson.net">Bill Erickson</a> & <a href="http://www.jaredatchison.com">Jared Atchison</a>';
		}
		return $links;	
	}

	/**
	 * Initialize the metabox for supported post types.
	 *
	 * @since 1.3.0
	 */
	public function metabox_add() {

		$options = $this->options();
		if ( !empty( $options['post_type'] ) ) {
			$post_types = (array) $options['post_type'];
			foreach( $post_types as $post_type ) {
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

		if ( 'publish' != $post->post_status ) {
			echo '<p>' . __( 'Entry must be published to view share counts.', 'ea-share-count' ) . '</p>';
			return;
		}

		$counts = get_post_meta( $post->ID, 'ea_share_count', true );

		if ( !empty( $counts ) ) {
			$counts = json_decode( $counts, true );
			echo '<ul id="ea-share-count-list">';
				echo $this->metabox_counts( $counts, $post->ID );
			echo '</ul>';
			$date = get_post_meta( $post->ID, 'ea_share_count_datetime', true );
			$date = $date+( get_option( 'gmt_offset' ) * 3600 );
			echo '<p id="ea-share-count-date">Last updated ' . date( 'M j, Y g:ia', $date ) . '</span></p>';
		} else {
			echo '<p id="ea-share-count-empty">' . __( 'No share counts downloaded for this entry', 'ea-share-count' ) . '</p>';
		}
		
		echo '<a href="#" class="button" id="ea-share-count-refresh" data-nonce="' . wp_create_nonce( 'ea-share-count-refresh-' . $post->ID ) . '" data-postid="' . $post->ID . '">'. __( 'Refresh Share Counts', 'ea-share-count' ) . '</a>';

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
	 * @return string
	 */
	public function metabox_counts( $counts, $post_id ) {

		if ( empty( $counts) || !is_array( $counts ) )
			return;

		$output  = '';
		$output .= '<li>Facebook Likes: <strong>' . ( !empty( $counts['Facebook']['like_count'] ) ? number_format( absint( $counts['Facebook']['like_count'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Facebook Shares: <strong>' . ( !empty( $counts['Facebook']['share_count'] ) ? number_format( absint( $counts['Facebook']['share_count'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Facebook Comments: <strong>' . ( !empty( $counts['Facebook']['comment_count'] ) ? number_format( absint( $counts['Facebook']['comment_count'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Twitter: <strong>' . ( !empty( $counts['Twitter'] ) ? number_format( absint( $counts['Twitter'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>Pinterest: <strong>' . ( !empty( $counts['Pinterest'] ) ? number_format( absint( $counts['Pinterest'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>LinkedIn: <strong>' . ( !empty( $counts['LinkedIn'] ) ? number_format( absint( $counts['LinkedIn'] ) ) : '0'  ) . '</strong></li>';
		$output .= '<li>StumbleUpon: <strong>' . ( !empty( $counts['StumbleUpon'] ) ? number_format( absint( $counts['StumbleUpon'] ) ) : '0'  ) . '</strong></li>';
		
		// Show Email shares if enabled
		$options = $this->options();
		if ( in_array( 'email', $options['included_services'] ) ) {
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

		// Run a security check
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ea-share-count-refresh-' . $_POST['post_id'] ) ) {
			wp_send_json_error( array( 'msg' => __( 'Failed security', 'ea-share-count' ), 'class' => 'error' ) );
		}

		// Check for permissions
		if ( !current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'msg' => __( 'You do not have permission', 'ea-share-count' ), 'class' => 'error' ) );
		}

		$id     = absint( $_POST['post_id'] );
		$counts = ea_share()->core->counts( $id, true, true );
		$date   = '<p id="ea-share-count-date">Last updated ' . date( 'M j, Y g:ia', time()+( get_option( 'gmt_offset' ) * 3600 ) ) . '</span></p>';
		$list   = '<ul id="ea-share-count-list">' . $this->metabox_counts( $counts, $id ) . '<ul>';

		wp_send_json_success( array( 
			'msg'   => __( 'Share counts updated.', 'ea-share-count' ), 
			'class' => 'success',
			'date'  => $date,
			'list'  => $list,
		) );
	}

	/**
	 * Load metabox assets
	 *
	 * @since 1.0.0
	 * @param string $hook
	 */
	public function metabox_assets( $hook ) {

		global $post;
		$options = $this->options();

		if ( empty( $options['post_type'] ) )
			return;

		if ( 'post.php' == $hook && in_array( $post->post_type, $options['post_type'] )  ) {
			wp_enqueue_script( 'share-count-settings', EA_SHARE_COUNT_URL . 'assets/js/admin-metabox.js', array( 'jquery' ), EA_SHARE_COUNT_VERSION, false );
		}
	}
	
	/**
	 * Save the Metabox
	 *
	 */
	function metabox_save( $post_id, $post ) {

		// Security check
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
		
		if( isset( $_POST['ea_share_count_exclude'] ) ) {
			update_post_meta( $post_id, 'ea_share_count_exclude', 1 );
		} else {
			delete_post_meta( $post_id, 'ea_share_count_exclude' );
		}

	}

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
	 * Admin Notices
	 *
	 * @since 1.7.0
	 *
	 */
	function admin_notices() {
	
		if( ! current_user_can( 'manage_options' ) )
			return;
	
		$notices = array(
			array(
				'key'     => '170',
				'class'   => 'error notice is-dismissible',
				'message' => sprintf( 
					__( 'EA Share Count must be <a href="%s">configured</a> due to recent Facebook API changes. <a href="%s" target="_blank">More information here</a>.', 'ea-share-count' ), 
					admin_url( 'options-general.php?page=ea_share_count_options' ), 
					esc_url( 'https://github.com/jaredatch/EA-Share-Count/wiki/No-longer-using-SharedCount' ) 
				)
			)
		);
		
		$dismissed = $this->settings_value( 'dismissed_notices' );		
		foreach( $notices as $notice ) {
			if( !in_array( $notice['key'], $dismissed ) )
				echo '<div class="' . esc_attr( $notice['class'] ) . '" data-key="' . sanitize_key( $notice['key'] ) . '"><p>' . $notice['message'] . '</p></div>';
		}
	
	}
	
	/**
	 * Admin Notice Assets
	 *
	 * @since 1.7.0
	 *
	 */
	function notice_assets() {
		
		wp_enqueue_script( 'ea-share-count-notice', EA_SHARE_COUNT_URL . 'assets/js/share-count-notice.js', array( 'jquery' ), EA_SHARE_COUNT_VERSION, false );
		wp_localize_script( 'ea-share-count-notice', 'ea_share_count_notice', array(
			'nonce' => wp_create_nonce( 'ea-share-count-notice' ),
		) );
		
	}
	
	/**
	 * AJAX Callback for Admin Notice Dismissal
	 *
	 * @since 1.7.0
	 */
	function notice_dismissal_ajax() {
		
		if (  ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'ea-share-count-notice' ) || ! current_user_can( 'manage_options' ) ) {
			return false;
		}	
		
		$options = get_option( 'ea_share_count_options' );
		if( ! isset( $options['dismissed_notices'] ) )
			$options['dismissed_notices'] = array();
			
		$notice = sanitize_key( $_POST['notice'] );
		$options['dismissed_notices'][] = $notice;
		
		update_option( 'ea_share_count_options', $options );
			
	}
}
