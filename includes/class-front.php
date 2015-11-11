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
	 * Primary class constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {

		// Make theme locations filterable
		$locations = array(
			'before' => array(
				'hook'     => 'genesis_entry_header',
				'priority' => 13,
			),
			'after' => array(
				'hook'     => 'genesis_entry_footer',
				'priority' => 8,
			),
		);
		$locations = apply_filters( 'ea_share_count_theme_locations', $locations );

		add_action( 'wp_enqueue_scripts',         array( $this, 'header_assets'          ), 9  );
		add_action( 'wp_footer',                  array( $this, 'load_assets'            ), 1  );		
		add_action( $locations['before']['hook'], array( $this, 'display_before_content' ), $locations['before']['priority'] );
		add_action( $locations['after']['hook'],  array( $this, 'display_after_content'  ), $locations['after']['priority']  );
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

		if ( !empty( $options['theme_location'] ) && !empty( $options['post_type'] ) && is_singular( $options['post_type'] ) ) {

			$this->share_link = true;
			$this->load_assets();
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
	 * Display Share Counts based on plugin settings.
	 *
	 * @param string $location
	 * @since 1.1.0
	 */
	public function display( $location = '' ) {

		$options = ea_share()->admin->options();
		$output  = '';
		$style   = isset( $options['style'] ) ? esc_attr( $options['style'] ) : 'generic';

		foreach( $options['included_services'] as $service ) {
			$output .= $this->link( $service, false, false, $style );
		}

		echo '<div class="ea-share-count-wrap ' . sanitize_html_class( $location ) . '">';
			echo apply_filters( 'ea_share_count_display', $output, $location );
		echo '</div>';
	}
	
	/**
	 * Display Before Content
	 * 
	 * @since 1.1.0
	 */
	public function display_before_content() {

		$options = ea_share()->admin->options();

		if ( ( 'before_content' == $options['theme_location'] || 'before_after_content' == $options['theme_location'] ) && !empty( $options['post_type'] ) && is_singular( $options['post_type'] ) ) {
			$this->display( 'before_content' );
		}
	}
	
	/**
	 * Display After Content
	 * 
	 * @since 1.1.0
	 */
	public function display_after_content() {

		$options = ea_share()->admin->options();

		if ( ( 'after_content' == $options['theme_location'] || 'before_after_content' == $options['theme_location'] ) && !empty( $options['post_type'] ) && is_singular( $options['post_type'] ) ) {
			$this->display( 'after_content' );
		}
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

		if ( empty( $show_empty ) ) {
			$show_empty = $options['show_empty'];
		}

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
			$link['count'] = ea_share()->core->count( $id, $type, false, $round );

			switch ( $type ) {
				case 'facebook':
					$link['link']   = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label']  = 'Facebook';
					$link['icon']   = 'fa fa-facebook';
					$link['target'] = '_blank';
					break;
				case 'facebook_likes':
					$link['link']   = 'http://www.facebook.com/plugins/like.php?href=' . $link['url'];
					$link['label']  = 'Like';
					$link['icon']   = 'fa fa-facebook';
					$link['target'] = '_blank';
					break;
				case 'facebook_shares':
					$link['link']   = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label']  = 'Share';
					$link['icon']   = 'fa fa-facebook';
					$link['target'] = '_blank';
					break;
				case 'twitter':
					$link['link']   = 'https://twitter.com/share?url=' . $link['url'] . '&text=' . $link['title'];
					$link['label']  = 'Tweet';
					$link['icon']   = 'fa fa-twitter';
					$link['target'] = '_blank';
					break;
				case 'pinterest':
					$link['link']   = 'http://pinterest.com/pin/create/link/?url=' . $link['url'] . '&media=' . $link['img'] . ' &description=' . $link['title'];
					$link['label']  = 'Pin';
					$link['icon']   = 'fa fa-pinterest-p';
					$link['target'] = '_blank';
					break;
				case 'linkedin':
					$link['link']   = 'http://www.linkedin.com/shareArticle?mini=true&url=' . $link['url'];
					$link['label']  = 'LinkedIn';
					$link['icon']   = 'fa fa-linkedin';
					$link['target'] = '_blank';
					break;
				case 'google':
					$link['link']   = 'http://plus.google.com/share?url=' . $link['url'];
					$link['label']  = 'Google+';
					$link['icon']   = 'fa fa-google-plus';
					$link['target'] = '_blank';
					break;
				case 'stumbleupon':
					$link['link']   = 'http://www.stumbleupon.com/submit?url=' . $link['url'] . '&title=' . $link['title'];
					$link['label']  = 'StumbleUpon';
					$link['icon']   = 'fa fa-stumbleupon';
					$link['target'] = '_blank';
					break;
				case 'included_total':
					$link['link']   = '';
					$link['label']  = 'Total';
					$link['icon']   = 'fa fa-share-alt';
					$link['target'] = '';
					break;
				case 'print':
					$link['link'] = 'javascript:window.print()';
					$link['label'] = 'Print';
					$link['icon'] = 'fa fa-print';
					break;
			}

			$link   = apply_filters( 'ea_share_count_link', $link );
			$target = !empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" ' : '';

			// Add classes
			if ( '0' == $link['count'] || ( 'total' == $options['number'] && 'included_total' != $type ) ) {
				$link['class'] .= ' ea-share-no-count';
			}

			// Build button output
			if ( $type == 'included_total' ) {
				$output .= '<span class="ea-share-count-button ' . $link['class'] . ' ' . sanitize_html_class( $link['type'] ) . '">';
			} else {
				$output .= '<a href="' . $link['link'] . '"' . $target . 'class="ea-share-count-button ' . $link['class'] . ' ' . sanitize_html_class( $link['type'] ) . '">';
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