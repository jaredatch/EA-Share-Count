<?php
/**
 * Front-end class.
 *
 * Contains functionality for the site front-end.
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.3.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
class EA_Share_Count_Front {

	/**
	 * Holds if a share link as been detected.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	public $share_link = false;

	/**
	 * Theme location placements.
	 *
	 * @since 1.5.9
	 * @var array
	 */
	public $locations;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {

		// Load assets
		add_action( 'template_redirect',          array( $this, 'theme_location'         ), 99 );
		add_action( 'wp_enqueue_scripts',         array( $this, 'header_assets'          ), 9  );
		add_action( 'wp_footer',                  array( $this, 'load_assets'            ), 1  );
		add_action( 'wp_footer',                  array( $this, 'email_modal'            ), 50 );
	}

	/**
	 * Add share buttons to theme locations
	 *
	 * @since 1.5.4
	 */
	function theme_location() {

		// Genesis Hooks
		if( 'genesis' == basename( TEMPLATEPATH ) ) {

			$locations = array(
				'before' => array(
					'hook'     => 'genesis_entry_header',
					'filter'   => false,
					'priority' => 13,
					'style'    => false,
				),
				'after' => array(
					'hook'     => 'genesis_entry_footer',
					'filter'   => false,
					'priority' => 8,
					'style'    => false,
				),
			);

		// Theme Hook Alliance
		} elseif( current_theme_supports( 'tha_hooks', array( 'entry' ) ) ) {

			$locations = array(
				'before' => array(
					'hook'     => 'tha_entry_top',
					'filter'   => false,
					'priority' => 13,
					'style'    => false,
				),
				'after' => array(
					'hook'     => 'tha_entry_bottom',
					'filter'   => false,
					'priority' => 8,
					'style'    => false,
				),
			);

		// Fallback to 'the_content'
		} else {

			$locations = array(
				'before' => array(
					'hook'     => false,
					'filter'   => 'the_content',
					'priority' => 8,
					'style'    => false,
				),
				'after' => array(
					'hook'     => false,
					'filter'   => 'the_content',
					'priority' => 12,
					'style'    => false,
				),
			);

		}

		// Filter theme locations
		$locations = apply_filters( 'ea_share_count_theme_locations', $locations );

		// Make locations available everywhere
		$this->locations = $locations;

		// Display share buttons before content
		if( $locations['before']['hook'] ) {
			add_action( $locations['before']['hook'], array( $this, 'display_before_content' ), $locations['before']['priority'] );
		} elseif( $locations['before']['filter'] ) {
			add_filter( $locations['before']['filter'], array( $this, 'display_before_content_filter' ), $locations['before']['priority'] );
		}

		// Display share buttons after content
		if( $locations['after']['hook'] ) {
			add_action( $locations['after']['hook'],  array( $this, 'display_after_content'  ), $locations['after']['priority']  );
		} elseif( $locations['after']['filter'] ) {
			add_filter( $locations['after']['filter'],  array( $this, 'display_after_content_filter'  ), $locations['after']['priority']  );
		}
	}

	/**
	 * Enqueue the assets earlier if possible.
	 *
	 * @since 1.2.0
	 */
	public function header_assets() {

		// Register assets
		wp_register_style( 'ea-share-count', EA_SHARE_COUNT_URL . 'assets/css/share-count.css', array(), EA_SHARE_COUNT_VERSION );
		wp_register_script( 'ea-share-count', EA_SHARE_COUNT_URL . 'assets/js/share-count.js', array( 'jquery' ), EA_SHARE_COUNT_VERSION, true );

		$options = ea_share()->admin->options();

		if ( !empty( $options['theme_location'] ) && !empty( $options['post_type'] ) && is_singular( $options['post_type'] ) && ! get_post_meta( get_the_ID(), 'ea_share_count_exclude', true ) ) {

			$this->share_link = true;
			$this->load_assets();
		}

		// Email sharing js if enabled
		if ( in_array( 'email', $options['included_services'] ) || apply_filters( 'ea_share_count_email_modal', false ) ) {
			$args = array(
				'url' => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'ea-share-count', 'easc', $args );
		}
	}

	/**
	 * Determines if assets need to be loaded.
	 *
	 * @since 1.0.0
	 */
	public function load_assets() {

		// Only continue if a share link was previously used in the page.
		if ( ! $this->share_link ) {
			return;
		}

		// Load CSS
		if ( apply_filters( 'ea_share_count_load_css', true ) ) {
			wp_enqueue_style( 'ea-share-count' );
		}

		// Load JS
		if ( apply_filters( 'ea_share_count_load_js', true ) ) {
			wp_enqueue_script( 'ea-share-count' );
		}
	}

	/**
	 * Email modal pop-up.
	 *
	 * This popup is output (and hidden) in the site footer if the Email
	 * service is configured in the plugin settings.
	 *
	 * @since 1.5.0
	 */
	public function email_modal() {

		// Only continue if a share link is on the page.
		if ( ! $this->share_link ) {
			return;
		}

		// Check to see the email button is configured or being overriden. The
		// filter can be used to enable the modal in use cases where the share
		// button is manually being called.
		$options = ea_share()->admin->options();
		if ( !in_array( 'email', $options['included_services'] ) && ! apply_filters( 'ea_share_count_email_modal', false ) ) {
			return;
		}

		// Labels, filterable of course.
		$labels = apply_filters( 'ea_share_count_email_labels', array(
				'title'      => 'Share this Article',
				'recipient'  => 'Friend\'s Email Address',
				'name'       => 'Your Name',
				'email'      => 'Your Email Address',
				'validation' => 'Comments',
				'submit'     => '<i class="easc-envelope"></i> Send Email',
				'close'      => '<i class="easc-close close-icon"></i>',
		) );
		?>
		<div id="easc-modal-wrap" style="display:none;">
			<div class="easc-modal">
				<span class="easc-modal-title"><?php echo $labels['title']; ?></span>
				<p>
					<label for="easc-modal-recipient"><?php echo $labels['recipient']; ?></label>
					<input type="text" id="easc-modal-recipient">
				</p>
				<p>
					<label for="easc-modal-name"><?php echo $labels['name']; ?></label>
					<input type="text" id="easc-modal-name">
				</p>
				<p>
					<label for="easc-modal-email"><?php echo $labels['email']; ?></label>
					<input type="text" id="easc-modal-email">
				</p>
				<p class="easc-modal-validation">
					<label for="easc-modal-validation"><?php echo $labels['validation']; ?></label>
					<input type="text" id="easc-modal-validation" autocomplete="off">
				</p>
				<p class="easc-modal-submit">
					<button id="easc-modal-submit"><?php echo $labels['submit']; ?></button>
				</p>
				<a href="#" id="easc-modal-close"><?php echo $labels['close']; ?></a>
				<div id="easc-modal-sent">Email sent!</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display Share Counts based on plugin settings.
	 *
	 * @param string $location
	 * @param bool $echo
	 * @param string $style
	 * @return null/string, depending on $echo
	 *
	 * @since 1.1.0
	 */
	public function display( $location = '', $echo = true, $style = false ) {

		$options = ea_share()->admin->options();
		$services  = '';

		if( ! $style && isset( $options['style'] ) ) {
			$style = esc_attr( $options['style'] );
		} elseif( ! $style )
			$style = 'generic';

		foreach( $options['included_services'] as $service ) {
			$services .= $this->link( $service, false, false, $style );
		}

		$output = '<div class="ea-share-count-wrap ' . sanitize_html_class( $location ) . '">';
		$output .= apply_filters( 'ea_share_count_display', $services, $location );
		$output .=  '</div>';

		if( $echo )
			echo $output;
		else
			return $output;
	}

	/**
	 * Display Before Content
	 *
	 * @since 1.1.0
	 */
	public function display_before_content() {

		$options = ea_share()->admin->options();

		if (
			( 'before_content' == $options['theme_location'] || 'before_after_content' == $options['theme_location'] )
			&& !empty( $options['post_type'] )
			&& is_singular( $options['post_type'] )
			&& ! get_post_meta( get_the_ID(), 'ea_share_count_exclude', true )
		) {

			// Detect if we are using a hook or filter
			if ( !empty( $this->locations['before']['hook'] ) )  {
				$this->display( 'before_content', true, $this->locations['before']['style'] );
			} elseif ( !empty( $this->locations['before']['filter'] ) ) {
				return $this->display( 'before_content', false, $this->locations['before']['style'] );
			}
		}
	}

	/**
	 * Display Before Content Filter
	 *
	 * @param string $content
	 * @return string $content
	 *
	 * @since 1.5.3
	 */
	public function display_before_content_filter( $content ) {

		return $this->display_before_content() . $content;
	}

	/**
	 * Display After Content
	 *
	 * @since 1.1.0
	 */
	public function display_after_content() {

		$options = ea_share()->admin->options();

		if (
			( 'after_content' == $options['theme_location'] || 'before_after_content' == $options['theme_location'] )
			&& !empty( $options['post_type'] )
			&& is_singular( $options['post_type'] )
			&& ! get_post_meta( get_the_ID(), 'ea_share_count_exclude', true )
		) {

			// Detect if we are using a hook or filter
			if ( !empty( $this->locations['after']['hook'] ) )  {
				$this->display( 'after_content', true, $this->locations['after']['style'] );
			} elseif ( !empty( $this->locations['after']['filter'] ) ) {
				return $this->display( 'after_content', false, $this->locations['after']['style'] );
			}
		}
	}

	/**
	 * Display After Content Filter
	 *
	 * @param string $content
	 * @return string $content
	 *
	 * @since 1.5.3
	 */
	public function display_after_content_filter( $content ) {

		return $content . $this->display_after_content();
	}

	/**
	 * Generate sharing links.
	 *
	 * @since 1.0.0
	 * @param string $types, button type
	 * @param int/string $id, pass 'site' for full site stats
	 * @param boolean $echo
	 * @param string $style
	 * @param int $round, how many significant digits on count
	 */
	public function link( $types = 'facebook', $id = false, $echo = true, $style = 'generic', $round = 2, $show_empty = '' ) {

		if ( !$id ) {
			$id = get_the_ID();
		}

		$this->share_link = true;
		$types   = (array) $types;
		$output  = '';
		$options = ea_share()->admin->options();
		$attr    = array( 'postid' => $id );
		$data    = '';

		if ( empty( $show_empty ) ) {
			$show_empty = $options['show_empty'];
		}

		foreach ( $types as $type ) {

			$link          = array();
			$link['type']  = $type;
			$link['class'] = esc_attr( 'style-' . $style );

			if ( 'site' == $id ) {
				$link['url']   = esc_url( home_url() );
				$link['title'] = wp_strip_all_tags( get_bloginfo( 'name' ) );
				$link['img']   = apply_filters( 'ea_share_count_default_image', '' );
			} elseif( 0 === strpos( $id, 'http' ) ) {
				$link['url']   = esc_url( $id );
				$link['title'] = '';
				$link['img']   = apply_filters( 'ea_share_count_default_image', '' );
			} else {
				$link['url']   = esc_url( get_permalink( $id ) );
				$link['title'] = wp_strip_all_tags( get_the_title( $id ) );
				$img           = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'full' );
				$link['img']   = isset( $img[0] ) ? $img[0] : '';
				$link['img']   = apply_filters( 'ea_share_count_single_image', $link['img'], $id );
			}

			$link['count'] = ea_share()->core->count( $id, $type, false, $round );

            $link_url = apply_filters( 'ea_share_count_link_url', $link['url'] );

			switch ( $type ) {
				case 'facebook':
					$link['link']       = 'https://www.facebook.com/sharer/sharer.php?u=' . $link_url . '&display=popup&ref=plugin&src=share_button';
					$link['label']      = 'Facebook';
					$link['icon']       = 'easc-facebook';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Facebook';
					break;
				case 'facebook_likes':
					$link['link']       = 'http://www.facebook.com/plugins/like.php?href=' . $link_url;
					$link['label']      = 'Like';
					$link['icon']       = 'easc-facebook';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Like on Facebook';
					break;
				case 'facebook_shares':
					$link['link']       = 'https://www.facebook.com/sharer/sharer.php?u=' . $link_url . '&display=popup&ref=plugin&src=share_button';
					$link['label']      = 'Share';
					$link['icon']       = 'easc-facebook';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Facebook';
					break;
				case 'twitter':
					$link['link']       = 'https://twitter.com/share?url=' . $link_url . '&text=' . $link['title'];
					$link['label']      = 'Tweet';
					$link['icon']       = 'easc-twitter';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Twitter';
					break;
				case 'pinterest':
					$link['link']       = 'http://pinterest.com/pin/create/link/?url=' . $link_url . '&media=' . $link['img'] . ' &description=' . $link['title'];
					$link['label']      = 'Pin';
					$link['icon']       = 'easc-pinterest-p';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Pinterest';
					break;
				case 'linkedin':
					$link['link']       = 'http://www.linkedin.com/shareArticle?mini=true&url=' . $link_url;
					$link['label']      = 'LinkedIn';
					$link['icon']       = 'easc-linkedin';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on LinkedIn';
					break;
				case 'google':
					$link['link']       = 'http://plus.google.com/share?url=' . $link_url;
					$link['label']      = 'Google+';
					$link['icon']       = 'easc-google-plus';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Google+';
					break;
				case 'stumbleupon':
					$link['link']       = 'http://www.stumbleupon.com/submit?url=' . $link_url . '&title=' . $link['title'];
					$link['label']      = 'StumbleUpon';
					$link['icon']       = 'easc-stumbleupon';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on StumbleUpon';
					break;
				case 'included_total':
					$link['link']       = '';
					$link['label']      = 'Total';
					$link['icon']       = 'easc-share';
					$link['target']     = '';
					break;
				case 'print':
					$link['link']       = 'javascript:window.print()';
					$link['label']      = 'Print';
					$link['icon']       = 'easc-print';
					$link['attr_title'] = 'Print this Page';
					break;
				case 'email':
					$link['link']       = '#ea-share-count-email';
					$link['label']      = 'Email';
					$link['icon']       = 'easc-envelope';
					$link['target']     = '';
					$attr['nonce']      = wp_create_nonce( 'easc_email_' . $id );
					$link['attr_title'] = 'Share via Email';
					break;
			}

			$link       = apply_filters( 'ea_share_count_link', $link );
			$target     = !empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" ' : '';
			$attr_title = !empty( $link['attr_title'] ) ? ' title="' . esc_attr( $link['attr_title'] ) . '" ' : '';

			// Add classes
			if ( '0' == $link['count'] || ( 'total' == $options['number'] && 'included_total' != $type ) ) {
				$link['class'] .= ' ea-share-no-count';
			}

			// Add data attribues
			if ( ! empty( $attr ) ) {
				foreach ( $attr as $key => $val ) {
					$data .= ' data-' . $key . '="' . $val . '"';
				}
			}

			// Build button output
			if ( $type == 'included_total' ) {
				$output .= '<span class="ea-share-count-button ' . $link['class'] . ' ' . sanitize_html_class( $link['type'] ) . '"' . $data . '>';
			} else {
				$output .= '<a href="' . $link['link'] . '"' . $attr_title . $target . ' class="ea-share-count-button ' . $link['class'] . ' ' . sanitize_html_class( $link['type'] ) . '"'. $data. '>';
			}
				$output .= '<span class="ea-share-count-icon-label">';
					$output .= '<i class="ea-share-count-icon ' . $link['icon'] . '"></i>';
					$output .= '<span class="ea-share-count-label">' . $link['label'] . '</span>';
				$output .= '</span>';
				if ( ( 'true' == $show_empty && !('total' == $options['number'] && 'included_total' != $type ) )  || ( 'true' != $show_empty && $link['count'] != '0' ) ) {
					$output .= '<span class="ea-share-count">' . $link['count'] . '</span>';
				}
			$output .=  $type == 'included_total' ? '</span>' : '</a>';
		}

		if ( $echo == true ) {
			echo $output;
		} else {
			return $output;
		}
	}
}
