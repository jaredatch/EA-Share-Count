<?php
/**
 * Core class.
 *
 * Contains core functionality.
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.3.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
class EA_Share_Count_Core{

	/**
	 * Primary class constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
	}

	/**
	 * Retreive share counts for site or post.
	 * 
	 * @since 1.0.0
	 * @param int/string $id, pass 'site' for full site stats
	 * @param boolean $array, return json o
	 * @param boolean $force, force refresh
	 * @return object $share_count
	 */
	public function counts( $id = false, $array = false, $force = false ) {
		
		// Primary site URL or Offsite/non post URL
		if ( 'site' == $id || 0 === strpos( $id, 'http') ) {

			$post_date    = true;
			$post_url     = 'site' == $id ? apply_filters( 'ea_share_count_site_url', home_url() ) : esc_url( $id );
			$hash         = md5( $post_url );
			$share_option = get_option( 'ea_share_count_urls', array() );
			$share_count  = !empty( $share_option[$hash]['count'] ) ? $share_option[$hash]['count'] : false;
			$last_updated = !empty( $share_option[$hash]['datetime'] ) ? $share_option[$hash]['datetime'] : false;
			
		// Post type URL
		} else {
	
			$post_id      = $id ? $id : get_the_ID();
			$post_date    = get_the_date( 'U', $post_id );
			$post_url     = get_permalink( $post_id );
			$share_count  = get_post_meta( $post_id, 'ea_share_count', true );
			$last_updated = get_post_meta( $post_id, 'ea_share_count_datetime', true );
		}

		// Rebuild and update meta if necessary
		if ( ! $share_count || ! $last_updated || $this->needs_updating( $last_updated, $post_date ) || $force ) {
			
			$share_count = $this->query_api( $post_url );

			if ( $share_count && ( 'site' == $id || 0 === strpos( $id, 'http' ) ) ) {

				$share_option[$hash]['count'] = $share_count;
				$share_option[$hash]['datetime'] = time();
				$share_option[$hash]['url'] = $post_url;

				$total = $this->total_count( $share_count );
				if ( $total ) {
					$share_option[$hash]['total'] = $share_count;
				}

				update_option( 'ea_share_count_urls', $share_option );
			
			} elseif ( $share_count ) {

				update_post_meta( $post_id, 'ea_share_count', $share_count );
				update_post_meta( $post_id, 'ea_share_count_datetime', time() );

				$total = $this->total_count( json_decode( $share_count, true ) );
				if ( $total ) {
					update_post_meta( $post_id, 'ea_share_count_total', $total );
				}
			}
		}

		if ( $share_count && $array == true ) {
			$share_count = json_decode( $share_count, true );
		}

		return $share_count;
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
		
		if ( $round ) {
			$share_count = $this->round_count( $share_count, $round );
		}

		if ( $echo ) {
			echo $share_count;
		} else {
			return $share_count;
		}
	}

	/**
	 * Calculate total shares across all services
	 *
	 * @since 1.0.2
	 * @param array $share_count
	 * @return int $total_shares
	 */
	public function total_count( $share_count ) {

		if ( empty( $share_count ) || ! is_array( $share_count ) ) {
			return 0;
		}
			
		$total = 0;

		foreach ( $share_count as $service => $count ) {
			if ( is_int( $count ) ) {
				$total += (int) $count;
			} elseif( is_array( $count ) && isset( $count['total_count'] ) ) {
				$total += (int) $count['total_count'];
			}
		}
		
		return apply_filters( 'ea_share_count_total', $total, $share_count );
	}

	/**
	 * Round to Significant Figures
	 *
	 * @since 1.0.0
	 * @param int $num, actual number
	 * @param int $n, significant digits to round to
	 * @return $num, rounded number
	 */
	public function round_count( $num = 0, $n = 0 ) {
		
		if ( $num == 0 ) {
			return 0;
		}
		
		$num       = (int) $num;
		$d         = ceil( log( $num < 0 ? -$num : $num, 10 ) );
		$power     = $n - $d;
		$magnitude = pow( 10, $power );
		$shifted   = round( $num * $magnitude );
		$output    = $shifted/$magnitude;
		
		if ( $output >= 1000000 ) {
			$output = $output / 1000000 . 'm';
		} elseif( $output >= 1000 ) {
			$output = $output / 1000 . 'k';
		}
		
		return $output;
	}

	/**
	 * Check if share count needs updating.
	 *
	 * @since 1.0.0
	 * @param int $last_updated, unix timestamp
	 * @param int $post_date, unix timestamp
	 * @return bool $needs_updating
	 */
	public function needs_updating( $last_updated = false, $post_date ) {

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
	public function query_api( $url = false ) {

		$options = ea_share()->admin->options();
	
		// Check that URL and API key are set
		if ( ! $url || empty( $options['api_key'] ) ) {
			return;
		}

		$query_args = apply_filters( 'ea_share_count_api_params', array( 'url' => $url, 'apikey' => $options['api_key'] ) );
		$query      = add_query_arg( $query_args, $options['api_domain'] . '/url' );
		$results    = wp_remote_get( $query );

		if ( ! is_wp_error( $results) && 200 == $results['response']['code'] ) {
			return $results['body'];
		} else {
			return false;
		}
	}

	/**
	 * Prime the pump
	 *
	 * Ensure we have share count data for at least 100 posts. 
	 * Useful when querying based on share count data.
	 * @link https://gist.github.com/billerickson/0f316f75430f3fd3a87c
	 *
	 * @since 1.1.0
	 * @param int $count, how many posts should have sharing data
	 * @param int $interval, how many should be updated at once
	 * @param bool $messages, whether to display messages during the update
	 *
	 */
	public function prime_the_pump( $count = 100, $interval = 20, $messages = false ) {
	
		$current = new WP_Query( array( 
			'fields'         => 'ids',
			'posts_per_page' => $count,
			'meta_query'     => array( 
				array(
					'key'     => 'ea_share_count',
					'compare' => 'EXISTS',
				)
			)
		) );
		$current = count( $current->posts );

		if ( $messages && function_exists( 'ea_pp' ) ) {
			ea_pp( 'Currently ' . $current . ' posts with share counts' );
		}
	
		if ( $current < $count ) {

			$update = new WP_Query( array(
				'fields'         => 'ids',
				'posts_per_page' => ( $count - $current ),
				'meta_query'     => array(
					array(
						'key'     => 'ea_share_count',
						'value'   => 1,
						'compare' => 'NOT EXISTS',
					)
				)
			) );

			if ( $update->have_posts() ) {
				foreach( $update->posts as $i => $post_id ) {
					if ( $interval > $i ) {
						$this->count( $post_id );
						do_action( 'ea_share_count_primed', $post_id );
					}
				}
				
				if ( $messages && function_exists( 'ea_pp' ) ) {
					$total_updated = $interval > count( $update->posts ) ? count( $update->posts ) : $interval;
					ea_pp( 'Updated ' . $total_updated . ' posts with share counts' );
				}
			}
		}
	}
}