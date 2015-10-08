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

		add_action( 'admin_init',            array( $this, 'settings_init'   )     );
		add_action( 'admin_menu',            array( $this, 'settings_add'    )     );
		add_action( 'admin_enqueue_scripts', array( $this, 'settings_assets' )     );
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
						<th scope="row"><?php _e( 'SharedCount API Key', 'ea-share-count' );?></th>
						<td>
							<input type="text" name="ea_share_count_options[api_key]" value="<?php echo $options['api_key'];?>" class="regular-text" /><br />
							<a href="http://www.sharedcount.com" target="_blank"><?php _e( 'Register for one here', 'ea-share-count' );?></a>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'SharedCount API Domain', 'ea-share-count' );?></th>
						<td>
							<select name="ea_share_count_options[api_domain]">
							<?php
							$domains = array( 'https://free.sharedcount.com', 'https://plus.sharedcount.com', 'https://business.sharedcount.com' );
							foreach( $domains as $domain )
								echo '<option value="' . $domain . '" ' . selected( $domain, $options['api_domain'], false ) . '>' . $domain . '</option>';
							?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Share Count Style', 'ea-share-count' );?></th>
						<td>
							<select name="ea_share_count_options[style]">
							<?php
							$styles = array( 'bubble' => 'Bubble', 'fancy' => 'Fancy', 'gss' => 'Genesis Simple Share' );
							foreach( $styles as $key => $label ) {
								echo '<option value="' . $key . '" ' . selected( $key, $options['style'], false ) . '>' . $label . '</option>';
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
								echo '<option value="' . $key . '" ' . selected( $key, $options['show_empty'], false ) . '>' . $label . '</option>';
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
							$post_types = get_post_types( array( 'public' => true, '_builtin' => true ), 'names' );
							if ( isset( $post_types['attachment'] ) ) {
								unset( $post_types['attachment'] );
							}
							foreach( $post_types as $post_type ) {
								echo '<label for="ea-cpt-' . sanitize_html_class( $post_type )  . '">';
									echo '<input type="checkbox" name="ea_share_count_options[post_type][]" value="' . esc_attr( $post_type ). '" id="ea-cpt-' . sanitize_html_class( $post_type ) . '" ' . checked( in_array( $post_type, $options['post_type'] ), true, false ) . '>';
									echo esc_html( $post_type );
								echo '</label>';
								echo '<br>';
							}
							?>
							</fieldset>
						</td>
					</tr>

					<?php 
					// If the Genesis Framework is used then provide a setting for
					// automated button placement.
					if( 'genesis' == basename( TEMPLATEPATH ) ) {
						echo '<tr valign="top">';
							echo '<th scope="row">' . __( 'Theme Location', 'ea-share-count' ) . '</th>';
							echo '<td>';
								echo '<select name="ea_share_count_options[theme_location]">';
								$locations = array( 
									''                     => __( 'None', 'ea-share-count' ), 
									'before_content'       => __( 'Before Content', 'ea-share-count' ), 
									'after_content'        => __( 'After Content',  'ea-share-count' ), 
									'before_after_content' => __( 'Before and After Content', 'ea-share-count' ), 
								);
								foreach( $locations as $key => $label ) {
									echo '<option value="' . $key . '" ' . selected( $key, $options['theme_location'], false ) . '>' . $label . '</option>';
								}
								echo '</select>';
							echo '</td>';
						echo '</tr>';
					}
					?>

					<tr valign="top">
						<th scope="row"><?php _e( 'Included Services', 'ea-share-count' );?></th>
						<td>
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
							);
							foreach( $services as $key => $service ) {
								echo '<option value="' . $key . '" ' . selected( in_array( $key, $options['included_services'] ), true, false ) . '>' . $service . '</option>';
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

			wp_enqueue_style( 'select2', EA_SHARE_COUNT_URL . 'assets/css/select2.min.css', array(), EA_SHARE_COUNT_VERSION );
			wp_enqueue_script( 'select2', EA_SHARE_COUNT_URL  . 'assets/js/select2.min.js', array( 'jquery' ), EA_SHARE_COUNT_VERSION, $false );
			wp_enqueue_script( 'share-count-settings', EA_SHARE_COUNT_URL . 'assets/js/settings.js', array( 'jquery' ), EA_SHARE_COUNT_VERSION, $false );
		}
	}

	/**
	 * Default settings values.
	 * 
	 * @since 1.1.0
	 */
	public function settings_default() {

		return array( 
			'api_key'           => '',
			'api_domain'        => 'https://free.sharedcount.com',
			'style'             => '',
			'show_empty'        => 'true',
			'post_type'         => array( 'post' ),
			'theme_location'    => '',
			'included_services' => array( 'facebook', 'twitter', 'pinterest' ),
		);
	}

	/**
	 * Sanitize saved settings.
	 * 
	 * @since 1.1.0
	 */
	public function settings_sanitize( $input ) {

		$input['api_key']           = esc_attr( $input['api_key'] );
		$input['api_domain']        = esc_url( $input['api_domain'] );
		$input['style']             = esc_attr( $input['style'] );
		$input['show_empty']        = esc_attr( $input['show_empty'] );
		$input['post_type']         = array_map( 'esc_attr', $input['post_type'] );
		$input['theme_location']    = esc_attr( $input['theme_location'] );
		$input['included_services'] = array_map( 'esc_attr', $input['included_services'] );
		return $input;
	}

	/**
	 * Return the settings options values.
	 *
	 * @since 1.3.0
	 */
	public function options() {

		$options = get_option( 'ea_share_count_options', $this->settings_default() ); 

		return apply_filters( 'ea_share_count_options', $options );
	}
}