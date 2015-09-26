<?php
/**
 * Plugin Name: EA Share Count
 * Plugin URI:  https://github.com/jaredatch/EA-Share-Count
 * Description: A lean plugin that leverages SharedCount.com API to quickly retrieve, cache, and display various social sharing counts.
 * Author:      Bill Erickson & Jared Atchison
 * Version:     1.0.5
 *
 * EA Share Count is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * EA Share Count is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EA Share Count. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main class
 *
 * @since 1.0.0
 * @package EA_Share_Count
 */
final class EA_Share_Count {

	/**
	 * Instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $version = '1.0.1';

	/**
	 * Domain for accessing SharedCount API.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $api_domain;
	
	/**
	 * API Key for SharedCount.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $api_key;

	/**
	 * Holds if a share link as been output.
	 *
	 * @since  1.0.0
	 */
	public $share_link = false;
	
	/** 
	 * Share Count Instance.
	 *
	 * @since 1.0.0
	 * @return EA_Share_Count
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EA_Share_Count ) ) {
			self::$instance = new EA_Share_Count;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Start the engines.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'init',      array( $this, 'load'          )    );
		add_action( 'wp_footer', array( $this, 'footer_assets' ), 1 );
		
		// Settings Page
		add_action( 'admin_init', array( $this, 'settings_page_init' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page'  ) );
	}

	/**
	 * Load properties
	 *
	 * @since 1.0.0
	 */
	public function load() {
		
		$options = get_option( 'ea_share_count_options', $this->default_options() ); 
		$this->api_key    = apply_filters( 'ea_share_count_key', $options['api_key'] );
		$this->api_domain = apply_filters( 'ea_share_count_domain', $options['api_domain'] );
		
	}
	
	/**
	 * Retreive share counts for site or post.
	 * 
	 * @since 1.0.0
	 * @param int/string $id, pass 'site' for full site stats
	 * @param boolean $array, return json o
	 * @return object $share_count
	 */
	public function counts( $id = false, $array = false ) {
		
		if ( 'site' == $id ) {
			$post_date    = true;
			$post_url     = apply_filters( 'ea_share_count_site_url', home_url() );
			$share_count  = get_option( 'ea_share_count' );
			$last_updated = get_option( 'ea_share_count_datetime' );
			
		} elseif( 0 === strpos( $id, 'http' ) ) {

			$post_date    = true;
			$post_url     = esc_url( $id );
			$share_count  = get_option( 'ea_share_count_' . md5( $id ) );
			$last_updated = get_option( 'ea_share_count_datetime_' . md5( $id ) );
			
		} else {
			$post_id      = $id ? $id : get_the_ID();
			$post_date    = get_the_date( 'U', $post_id );
			$post_url     = get_permalink( $post_id );
			$share_count  = get_post_meta( $post_id, 'ea_share_count', true );
			$last_updated = get_post_meta( $post_id, 'ea_share_count_datetime', true );
		}

		// Rebuild and update meta if necessary
		if ( ! $share_count || ! $last_updated || $this->needs_updating( $last_updated, $post_date ) ) {
			
			$share_count = $this->query_api( $post_url );

			if ( $share_count && 'site' == $id ) {
				update_option( 'ea_share_count', $share_count );
				update_option( 'ea_share_count_datetime', time() );
				$total = $this->total_count( $share_count );
				if( $total )
					update_option( 'ea_share_count_total', $total );
			} elseif( $share_count && 0 === strpos( $id, 'http' ) ) {			
				update_option( 'ea_share_count_' . md5( $id ), $share_count );
				update_option( 'ea_share_count_datetime_' . md5( $id ), time() );
				$total = $this->total_count( $share_count );
				if( $total )
					update_option( 'ea_share_count_total_' . md5( $id ), $total );
			
			} elseif ( $share_count ) {
				update_post_meta( $post_id, 'ea_share_count', $share_count );
				update_post_meta( $post_id, 'ea_share_count_datetime', time() );
				$total = $this->total_count( json_decode( $share_count, true ) );
				if( $total )
					update_post_meta( $post_id, 'ea_share_count_total', $total );
			}
		}

		if ( $share_count && $array == true ) {
			$share_count = json_decode( $share_count, true );
		}

		return $share_count;
	}
	
	/**
	 * Calculate total shares across all services
	 *
	 * @since 1.0.2
	 * @param array $share_count
	 * @return int $total_shares
	 */
	public function total_count( $share_count ) {

		if( empty( $share_count ) || ! is_array( $share_count ) )
			return 0;
			
		$total = 0;
		foreach( $share_count as $service => $count ) {
			if( is_int( $count ) )
				$total += (int) $count;
			elseif( is_array( $count ) && isset( $count['total_count'] ) )
				$total += (int) $count['total_count'];
		}
		
		return $total;
			
	}

	/**
	 * Retreive a single share count for a site or post.
	 *
	 * @since 1.0.0
	 * @param int/string $id, pass 'site' for full site stats
	 * @param string $type
	 * @param boolean $echo
	 * @param int $round, how many significant digits on count
	 * @return int
	 */
	public function count( $id = false, $type = 'facebook', $echo = false, $round = 2 ) {

		$counts = $this->counts( $id, true );
		$total  = $this->total_count( $counts );

		if ( $counts == false ) {
			$share_count = '0';
		} else {
			switch ( $type ) {
				case 'facebook':
					$share_count = $counts['Facebook']['total_count'];
					break;
				case 'facebook_likes':
					$share_count = $counts['Facebook']['like_count'];
					break;
				case 'facebook_shares':
					$share_count = $counts['Facebook']['share_count'];
					break;
				case 'facebook_comments':
					$share_count = $counts['Facebook']['comment_count'];
					break;
				case 'twitter':
					$share_count = $counts['Twitter'];
					break;
				case 'pinterest':
					$share_count = $counts['Pinterest'];
					break;
				case 'linkedin':
					$share_count = $counts['LinkedIn'];
					break;
				case 'google':
					$share_count = $counts['GooglePlusOne'];
					break;
				case 'stumbleupon':
					$share_count = $counts['StumbleUpon'];
					break;
				case 'total':
					$share_count = $total;
					break;
				default:
					$share_count = apply_filters( 'ea_share_count_single', '0', $counts );
					break;
			}
		}

		if ( empty( $share_count ) ) {
			$share_count = '0';
		}
		
		if( $round )
			$share_count = $this->round_count( $share_count, $round );

		if ( $echo ) {
			echo $share_count;
		} else {
			return $share_count;
		}
	}

	/**
	 * Check if share count needs updating.
	 *
	 * @since 1.0.0
	 * @param int $last_updated, unix timestamp
	 * @param int $post_date, unix timestamp
	 * @return bool $needs_updating
	 */
	function needs_updating( $last_updated = false, $post_date ) {

		if ( ! $last_updated ) {
			return true;
		}
	
		$update_increments = array(
			array(
				'post_date' => strtotime( '-1 day' ),
				'increment' => strtotime( '-30 minutes'),
			),
			array(
				'post_date' => strtotime( '-5 days' ),
				'increment' => strtotime( '-6 hours' )
			),
			array(
				'post_date' => 0,
				'increment' => strtotime( '-2 days' ),
			)
		);
		$update_increments = apply_filters( 'ea_share_count_update_increments', $update_increments );
		
		$increment = false;
		foreach ( $update_increments as $i ) {
			if ( $post_date > $i['post_date'] ) {
				$increment = $i['increment'];
				break;
			}
		}
		
		return $last_updated < $increment;
	}

	/**
	 * Query the SharedCount API
	 *
	 * @since 1.0.0
	 * @param string $url
	 * @return object $share_count
	 */
	function query_api( $url = false ) {
	
		// Check that URL and API key are set
		if ( ! $url || empty( $this->api_key ) ) {
			return;
		}
		
		$query_args = apply_filters( 'ea_share_count_api_params', array( 'url' => $url, 'apikey' => $this->api_key ) );
		$query      = add_query_arg( $query_args, $this->api_domain . '/url' );
		$results    = wp_remote_get( $query );

		if ( ! is_wp_error( $results) && 200 == $results['response']['code'] ) {
			return $results['body'];
		} else {
			return false;
		}
	}

	/**
	 * Generate sharing links.
	 *
	 * For styling: https://gist.github.com/billerickson/a67bf451675296b144ea
	 *
	 * @since 1.0.0
	 * @param string $types, button type
	 * @param int/string $id, pass 'site' for full site stats
	 * @param boolean $echo
	 * @param string $style
	 * @param int $round, how many significant digits on count
	 */
	function link( $types = 'facebook', $id = false, $echo = true, $style = 'generic', $round = 2 ) {

		if ( !$id ) {
			$id = get_the_ID();
		}

		$this->share_link = true;
		$types  = (array) $types;
		$output = '';

		foreach ( $types as $type ) {
			$link          = array();
			$link['type']  = $type;
			$link['class'] = esc_attr( 'style-' . $style );

			if ( 'site' == $id ) {
				$link['url']   = home_url();
				$link['title'] = get_bloginfo( 'name' );
				$link['img']   = apply_filters( 'ea_share_count_default_image', '' );
			} elseif( 0 === strpos( $id, 'http' ) ) {
				$link['url']   = esc_url( $id );
				$link['title'] = '';
				$link['img']   = apply_filters( 'ea_share_count_default_image', '' );
			} else {
				$link['url']   = get_permalink( $id );
				$link['title'] = get_the_title( $id );
				$img           = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'full' );
				$link['img']   = isset( $img[0] ) ? $img[0] : '';
				$link['img']   = apply_filters( 'ea_share_count_single_image', $link['img'], $id );
			}
			$link['count'] = $this->count( $id, $type, false, $round );

			switch ( $type ) {
				case 'facebook':
					$link['link']  = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label'] = 'Facebook';
					$link['icon']  = 'fa fa-facebook';
					$link['target'] = '_blank';
					break;
				case 'facebook_likes':
					$link['link']  = 'http://www.facebook.com/plugins/like.php?href=' . $link['url'];
					$link['label'] = 'Like';
					$link['icon']  = 'fa fa-facebook';
					$link['target'] = '_blank';
					break;
				case 'facebook_shares':
					$link['link']  = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label'] = 'Share';
					$link['icon']  = 'fa fa-facebook';
					$link['target'] = '_blank';
					break;
				case 'twitter':
					$link['link']  = 'https://twitter.com/share?url=' . $link['url'] . '&text=' . $link['title'];
					$link['label'] = 'Tweet';
					$link['icon']  = 'fa fa-twitter';
					$link['target'] = '_blank';
					break;
				case 'pinterest':
					$link['link']  = 'http://pinterest.com/pin/create/button/?url=' . $link['url'] . '&media=' . $link['img'] . ' &description=' . $link['title'];
					$link['label'] = 'Pin';
					$link['icon']  = 'fa fa-pinterest-p';
					$link['target'] = '_blank';
					break;
				case 'linkedin':
					$link['link']  = 'http://www.linkedin.com/shareArticle?mini=true&url=' . $link['url'];
					$link['label'] = 'LinkedIn';
					$link['icon']  = 'fa fa-linkedin';
					$link['target'] = '_blank';
					break;
				case 'google':
					$link['link']  = 'http://plus.google.com/share?url=' . $link['url'];
					$link['label'] = 'Google+';
					$link['icon']  = 'fa fa-google-plus';
					$link['target'] = '_blank';
					break;
				case 'stumbleupon':
					$link['link']  = 'http://www.stumbleupon.com/submit?url=' . $link['url'] . '&title=' . $link['title'];
					$link['label'] = 'StumbleUpon';
					$link['icon']  = 'fa fa-stumbleupon';
					$link['target'] = '_blank';
					break;
			}

			$link = apply_filters( 'ea_share_count_link', $link );
			$target = !empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" ' : '';

			$output .= '<a href="' . $link['link'] . '"' . $target . 'class="ea-share-count-button ' . $link['class'] . ' ' . sanitize_html_class( $link['type'] ) . '">';
				$output .= '<span class="ea-share-count-icon-label">';
					$output .= '<i class="ea-share-count-icon ' . $link['icon'] . '"></i>';
					$output .= '<span class="ea-share-count-label">' . $link['label'] . '</span>';
				$output .= '</span>';
				$output .= '<span class="ea-share-count">' . $link['count'] . '</span>'; 
			$output .= '</a>';
		}

		

		if ( $echo == true ) {
			echo $output;
		} else {
			return $output;
		}
	}
	
	/**
	 * Round to Significant Figures
	 *
	 * @param int $num, actual number
	 * @param int $n, significant digits to round to
	 * @return $num, rounded number
	 */
	function round_count( $num = 0, $n = 0 ) {
		if( $num == 0 )
			return 0;
		
		$num = (int) $num;
		$d = ceil( log( $num < 0 ? -$num : $num, 10 ) );
		$power = $n - $d;
		$magnitude = pow( 10, $power );
		$shifted = round( $num * $magnitude );
		$output = $shifted/$magnitude;
		
		if( $output >= 1000000 )
			$output = $output / 1000000 . 'm';
		elseif( $output >= 1000 )
			$output = $output / 1000 . 'k';
		
		return $output;
	}
	
	/**
	 * Determines if assets need to be loaded in the footer.
	 *
	 * @since 1.0.0
	 */
	public function footer_assets() {

		// Only continue if a share link was previously used in the page.
		if ( ! $this->share_link ) {
			return;
		}

		// Load CSS
		if ( apply_filters( 'ea_share_count_load_css', true ) ) {
			wp_enqueue_style( 'ea-share-count', plugins_url( 'share-count.css', __FILE__ ), array(), $this->version );
		}

		// Load JS
		if ( apply_filters( 'ea_share_count_load_js', true ) ) {
			wp_enqueue_script( 'jquery' );
			?>
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$('.ea-share-count-button[target="_blank"]').click(function(event){
					event.preventDefault();
					var window_size = '';
					var url = this.href;
					var domain = url.split("/")[2];
					switch(domain) {
						case "www.facebook.com":
							window_size = "width=585,height=368";
							break;
						case "twitter.com":
							window_size = "width=585,height=261";
							break;
						case "plus.google.com":
							window_size = "width=517,height=511";
							break;
						case "pinterest.com":
							window_size = "width=700,height=300";
							break;
						default:
							window_size = "width=585,height=515";
					}
					window.open(url, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,' + window_size);
					$(this).trigger("ea-share-click");
				});
			});
			</script>
			<?php
		}
	}
	
	/**
	 * Initialize the settings page options
	 *
	 */
	function settings_page_init() {
		register_setting( 'ea_share_count_options', 'ea_share_count_options', array( $this, 'ea_share_count_validate' ) );
	}
	
	/**
	 * Add Settings Page
	 *
	 */
	function add_settings_page() {
		add_options_page( __( 'Share Count Settings', 'ea-share-count' ), __( 'Share Count Settings', 'ea-share-count' ), 'manage_options', 'ea_share_count_options', array( $this, 'settings_page' ) );
	}
	
	/**
	 * Build the Settings Page 
	 *
	 */
	function settings_page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Share Count Settings', 'ea-share-count' );?></h2>
			<form method="post" action="options.php">
				<?php 
				settings_fields( 'ea_share_count_options' );
				$options = get_option( 'ea_share_count_options', $this->default_options() ); 
				?>
				<table class="form-table">
					<tr valign="top"><th scope="row"><?php _e( 'SharedCount API Key', 'ea-share-count' );?></th>
						<td><input type="text" name="ea_share_count_options[api_key]" value="<?php echo $options['api_key'];?>" /><br /><a href="http://www.sharedcount.com" target="_blank">Register for one here</a></td>
					</tr>
					<tr valign="top"><th scope="row"><?php _e( 'SharedCount API Domain', 'ea-share-count' );?></th>
						<td><select name="ea_share_count_options[api_domain]">
						<?php
						$domains = array( 'https://free.sharedcount.com', 'https://plus.sharedcount.com', 'https://business.sharedcount.com' );
						foreach( $domains as $domain )
							echo '<option value="' . $domain . '" ' . selected( $domain, $options['api_domain'], false ) . '>' . $domain . '</option>';
						?>
						</select></td>
					</tr>
					<tr valign="top"><th scope="row"><?php _e( 'Share Count Style', 'ea-share-count' );?></th>
						<td><select name="ea_share_count_options[style]">
						<?php
						$styles = array( 'bubble' => 'Bubble', 'fancy' => 'Fancy', 'gss' => 'Genesis Simple Share' );
						foreach( $styles as $key => $label ) {
							echo '<option value="' . $key . '" ' . selected( $key, $options['style'], false ) . '>' . $label . '</option>';
						}
						?>
						</select></td>
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
	 * Default Option Values
	 *
	 */
	function default_options() {
		return array( 
			'api_key' => '',
			'api_domain' => 'http://free.sharedcount.com',
		);
	}
	
	/**
	 * Validate Options
	 *
	 */
	function ea_share_count_validate( $input ) {
		$input['api_key'] = esc_attr( $input['api_key'] );
		$input['api_domain'] = esc_url( $input['api_domain'] );
		return $input;
	}

}

/**
 * The function provides access to the sharing methods.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 1.0.0
 * @return object
 */
function ea_share() {
	return EA_Share_Count::instance();
}
ea_share();
