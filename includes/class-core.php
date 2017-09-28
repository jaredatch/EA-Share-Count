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
class EA_Share_Count_Core {

	/**
	 * Holds list of posts that need share count refreshed.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	public $update_queue = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_easc_email',        array( $this, 'email_ajax'          ) );
		add_action( 'wp_ajax_nopriv_easc_email', array( $this, 'email_ajax'          ) );
		add_action( 'shutdown',                  array( $this, 'update_share_counts' ) );
	}

	/**
	 * Process and send email share AJAX requests.
	 *
	 * @since 1.5.0
	 */
	public function email_ajax() {

		// Check spam honeypot.
		if ( ! empty( $_POST['validation'] ) ) {
			wp_send_json_error( __( 'Honeypot triggered.', 'share-count-plugin' ) );
		}

		// Check required fields.
		if ( empty( $_POST['recipient'] ) || empty( $_POST['name'] ) || empty( $_POST['email'] ) ) {
			wp_send_json_error( __( 'Required field missing.', 'share-count-plugin' ) );
		}

		// Check email addresses.
		if ( ! is_email( $_POST['recipient'] ) || ! is_email( $_POST['email'] ) ) {
			wp_send_json_error( __( 'Invalid email.', 'share-count-plugin' ) );
		}

		// Check if reCAPTCHA is enabled.
		$options   = ea_share()->admin->options();
		$recaptcha = ! empty( $options['recaptcha'] ) && ! empty( $options['recaptcha_site_key'] ) && ! empty( $options['recaptcha_secret_key'] );

		// reCAPTCHA is enabled, so verify it.
		if ( $recaptcha ) {

			if ( empty( $_POST['recaptcha'] ) ) {
				wp_send_json_error( __( 'reCAPTCHA is required.', 'share-count-plugin' ) );
			}

			$data  = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $options['recaptcha_secret_key'] . '&response=' . $_POST['recaptcha'] );
			$data  = json_decode( wp_remote_retrieve_body( $data ) );
			if ( empty( $data->success ) ) {
				wp_send_json_error( __( 'Incorrect reCAPTCHA, please try again.', 'share-count-plugin' ) );
			}
		}

		$post_id    = absint( $_POST['postid'] );
		$recipient  = sanitize_text_field( $_POST['recipient'] );
		$from_email = sanitize_text_field( $_POST['email'] );
		$from_name  = sanitize_text_field( $_POST['name'] );
		$site_name  = sanitize_text_field( get_bloginfo( 'name' ) );
		$site_root  = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $site_root, 0, 4 ) === 'www.' ) {
			$site_root = substr( $site_root, 4 );
		}

		$headers = array(
			'From'     => "$site_name <noreply@$site_root>",
			'Reply-To' => "$from_name <$from_email>",
		);
		$subject = "Your friend $from_name has shared an article with you";
		$body    = html_entity_decode( get_the_title( $post_id ), ENT_QUOTES ) . "\r\n";
		$body   .= get_permalink( $post_id ) . "\r\n";

		wp_mail(
			$recipient,
			apply_filters( 'ea_share_count_email_subject', $subject, $post_id, $recipient, $from_name, $from_email ),
			apply_filters( 'ea_share_count_email_body',    $body,    $post_id, $recipient, $from_name, $from_email ),
			apply_filters( 'ea_share_count_email_headers', $headers, $post_id, $recipient, $from_name, $from_email )
		);

		$count  = absint( get_post_meta( $post_id, 'ea_share_count_email', true ) );
		$update = update_post_meta( $post_id, 'ea_share_count_email', $count++ );

		wp_send_json_success();
	}

	/**
	 * Retreive share counts for site or post.
	 *
	 * @since 1.0.0
	 * @param int/string $id pass 'site' for full site stats.
	 * @param bool $array return json o.
	 * @param bool $force force refresh.
	 * @return object $share_count
	 */
	public function counts( $id = false, $array = false, $force = false ) {

		if ( 'site' === $id || 0 === strpos( $id, 'http') ) {
			// Primary site URL or Offsite/non post URL.
			$post_date    = true;
			$post_url     = 'site' === $id ? apply_filters( 'ea_share_count_site_url', home_url() ) : esc_url( $id );
			$hash         = md5( $post_url );
			$share_option = get_option( 'ea_share_count_urls', array() );
			$share_count  = ! empty( $share_option[ $hash ]['count'] ) ? $share_option[ $hash ]['count'] : false;
			$last_updated = ! empty( $share_option[ $hash ]['datetime'] ) ? $share_option[ $hash ]['datetime'] : false;

		} else {
			// Post type URL.
			$post_id      = $id ? $id : get_the_ID();
			$post_date    = get_the_date( 'U', $post_id );
			$post_url     = get_permalink( $post_id );
			$share_count  = get_post_meta( $post_id, 'ea_share_count', true );
			$last_updated = get_post_meta( $post_id, 'ea_share_count_datetime', true );
		}

		// Rebuild and update meta if necessary.
		if ( ! $share_count || ! $last_updated || $this->needs_updating( $last_updated, $post_date ) || $force ) {

			$id = isset( $post_id ) ? $post_id : $id;

			$this->update_queue[ $id ] = $post_url;

			// If this update was forced then we process immediately. Otherwise
			// add the the queue which processes on shutdown (for now).
			if ( $force ) {
				$this->update_share_counts();
				$share_count = $this->counts( $id );
			}
		}

		if ( $share_count && true === $array ) {
			$share_count = json_decode( $share_count, true );
		}

		return $share_count;
	}

	/**
	 * Retreive a single share count for a site or post.
	 *
	 * @since 1.0.0
	 * @param int/string $id pass 'site' for full site stats.
	 * @param string $type
	 * @param boolean $echo
	 * @param int $round how many significant digits on count.
	 * @return int
	 */
	public function count( $id = false, $type = 'facebook', $echo = false, $round = 2 ) {

		$counts = $this->counts( $id, true );
		$total  = $this->total_count( $counts );

		if ( false === $counts ) {
			$share_count = '0';
		} else {
			switch ( $type ) {
				case 'facebook':
					$share_count = isset( $counts['Facebook']['total_count'] ) ? $counts['Facebook']['total_count'] : '0';
					break;
				case 'facebook_likes':
					$share_count = isset( $counts['like_count'] ) ? $counts['like_count'] : '0' ;
					break;
				case 'facebook_shares':
					$share_count = isset( $counts['share_count'] ) ? $counts['share_count'] : '0' ;
					break;
				case 'facebook_comments':
					$share_count = isset( $counts['comment_count'] ) ? $counts['comment_count'] : '0' ;
					break;
				case 'twitter':
					$share_count = isset( $counts['Twitter'] ) ? $counts['Twitter'] : '0' ;
					break;
				case 'pinterest':
					$share_count = isset( $counts['Pinterest'] ) ? $counts['Pinterest'] : '0' ;
					break;
				case 'linkedin':
					$share_count = isset( $counts['LinkedIn'] ) ? $counts['LinkedIn'] : '0' ;
					break;
				case 'google':
					$share_count = isset( $counts['GooglePlusOne'] ) ? $counts['GooglePlusOne'] : '0' ;
					break;
				case 'stumbleupon':
					$share_count = isset( $counts['StumbleUpon'] ) ? $counts['StumbleUpon'] : '0' ;
					break;
				case 'included_total':
					$share_count = '0';
					$options = ea_share()->admin->options();
					// Service total only applies to services we are displaying.
					if ( ! empty( $options['included_services'] ) ) {
						foreach ( $options['included_services'] as $service ) {
							if ( 'included_total' !== $service ) {
								$share_count = $share_count + $this->count( $id, $service, false, false );
							}
						}
					}
					break;
				case 'print':
					$share_count = 0;
					break;
				case 'email':
					$share_count = absint( get_post_meta( $id, 'ea_share_count_email', true ) );
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

		if ( $round && $share_count >= 1000 ) {
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
			} elseif ( is_array( $count ) && isset( $count['total_count'] ) ) {
				$total += (int) $count['total_count'];
			}
		}

		return apply_filters( 'ea_share_count_total', $total, $share_count );
	}

	/**
	 * Round to Significant Figures
	 *
	 * @since 1.0.0
	 * @param int $num actual number.
	 * @param int $n significant digits to round to.
	 * @return $num rounded number.
	 */
	public function round_count( $num = 0, $n = 0 ) {

		if ( 0 == $num ) {
			return 0;
		}

		$num       = (int) $num;
		$d         = ceil( log( $num < 0 ? -$num : $num, 10 ) );
		$power     = $n - $d;
		$magnitude = pow( 10, $power );
		$shifted   = round( $num * $magnitude );
		$output    = $shifted / $magnitude;

		if ( $output >= 1000000 ) {
			$output = $output / 1000000 . 'm';
		} elseif ( $output >= 1000 ) {
			$output = $output / 1000 . 'k';
		}

		return $output;
	}

	/**
	 * Check if share count needs updating.
	 *
	 * @since 1.0.0
	 * @param int $last_updated unix timestamp.
	 * @param int $post_date unix timestamp.
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
				'increment' => strtotime( '-6 hours' ),
			),
			array(
				'post_date' => 0,
				'increment' => strtotime( '-2 days' ),
			),
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
	 * Query the Social Service APIs
	 *
	 * @since 1.0.0
	 * @param string $url
	 * @param string $id
	 * @return object $share_count
	 */
	public function query_api( $url = false, $id = '' ) {

		if ( empty( $url ) ) {
			return;
		}

		$count_source = ea_share()->admin->settings_value( 'count_source' );

		// Default share counts, filterable.
		$share_count = array(
			'Facebook'      => array(
				'share_count'   => 0,
				'like_count'    => 0,
				'comment_count' => 0,
				'total_count'   => 0,
			),
			'Twitter'       => 0,
			'Pinterest'     => 0,
			'LinkedIn'      => 0,
			'GooglePlusOne' => 0,
			'StumbleUpon'   => 0,
		);
		$share_count = apply_filters( 'ea_share_count_default_counts', $share_count, $url, $id );

		if ( 'sharedcount' === $count_source ) {
			$share_count = $this->query_sharedcount_api( $url, $share_count );
		} elseif ( 'native' === $count_source ) {
			$share_count = $this->query_native_api( $url, $share_count );
		}

		$global_args = apply_filters( 'ea_share_count_api_params', array(
			'url' => $url,
		) );

		// Modify API query results, or query additional APIs.
		$share_count = apply_filters( 'ea_share_count_query_api', $share_count, $global_args, $url, $id );

		// Sanitize.
		array_walk_recursive( $share_count, 'absint' );

		// Final counts.
		return wp_json_encode( $share_count );
	}

	/**
	 * Retrieve counts from SharedCounts.com.
	 *
	 * @since 2.0.0
	 * @param string $url
	 * @param array $share_count
	 * @return array
	 */
	public function query_sharedcount_api( $url, $share_count ) {

		$api_key = ea_share()->admin->settings_value( 'sharedcount_key' );

		if ( empty( $api_key ) ) {
			return $share_count;
		}

		// Fetch counts from SharedCount API.
		$global_args = apply_filters( 'ea_share_count_api_params', array(
			'url' => $url,
		) );

		$api_query = add_query_arg(
			array(
				'url' => $global_args['url'],
				'apikey' => trim( $api_key ),
			),
			'https://api.sharedcount.com/v1.0/'
		);

		$api_response = wp_remote_get( $api_query, array(
			'sslverify'  => false,
			'user-agent' => 'LC ShareCounts',
		) );

		error_log( print_r( $api_response, true ) );

		if ( ! is_wp_error( $api_response ) && 200 == wp_remote_retrieve_response_code( $api_response ) ) {

			$results = json_decode( wp_remote_retrieve_body( $api_response ), true );

			// Update counts.
			$share_count['Facebook']['comment_count'] = isset( $results['Facebook']['comment_count'] ) ? $results['Facebook']['comment_count'] : $share_count['Facebook']['comment_count'];
			$share_count['Facebook']['share_count']   = isset( $results['Facebook']['share_count'] ) ? $results['Facebook']['share_count'] : $share_count['Facebook']['share_count'];
			$share_count['Facebook']['total_count']   = isset( $results['Facebook']['total_count'] ) ? $results['Facebook']['total_count'] : $share_count['Facebook']['total_count'];
			$share_count['Pinterest']                 = isset( $results['Pinterest'] ) ? $results['Pinterest'] : $share_count['Pinterest'];
			$share_count['StumbleUpon']               = isset( $results['StumbleUpon'] ) ? $results['StumbleUpon'] : $share_count['StumbleUpon'];
			$share_count['LinkedIn']                  = isset( $results['LinkedIn'] ) ? $results['LinkedIn'] : $share_count['LinkedIn'];
			$share_count['GooglePlusOne']             = isset( $results['GooglePlusOne'] ) ? $results['GooglePlusOne'] : $share_count['GooglePlusOne'];
		}

		// Check if we also need to fetch Twitter counts.
		$twitter = ea_share()->admin->settings_value( 'twitter_counts' );

		// Fetch Twitter counts if needed.
		if ( '1' === $twitter ) {
			$twitter_count = $this->query_newsharecounts_api( $global_args['url'] );
			$share_count['Twitter'] = false !== $twitter_count ? $twitter_count : $share_count['Twitter'];
		}

		return $share_count;
	}

	/**
	 * Retrieve counts from SharedCounts.com.
	 *
	 * @since 2.0.0
	 * @param string $url
	 * @return int|false
	 */
	public function query_newsharecounts_api( $url ) {

		$api_query = add_query_arg(
			array(
				'url' => $url,
			),
			'https://public.newsharecounts.com/count.json'
		);

		$api_response = wp_remote_get( $api_query, array(
			'sslverify'  => false,
			'user-agent' => 'EA Share Counts',
		) );

		if ( ! is_wp_error( $api_response ) && 200 == wp_remote_retrieve_response_code( $api_response ) ) {

			$body = json_decode( wp_remote_retrieve_body( $api_response ) );

			if ( isset( $body->count ) ) {
				return $body->count;
			}
		}

		return false;
	}

	/**
	 * Retrieve counts from SharedCounts.com.
	 *
	 * @since 2.0.0
	 * @param string $url
	 * @param array $share_count
	 * @return int|false
	 */
	public function query_native_api( $url, $share_count ) {

		$services = ea_share()->admin->settings_value( 'query_services' );

		if ( empty( $services ) ) {
			return $share_count;
		}

		$global_args = apply_filters( 'ea_share_count_api_params', array(
			'url' => $url,
		) );

		// Provide a filter so certain service queries can be bypassed. Helpful
		// if you want to run your own request against other APIs.
		$services = apply_filters( 'ea_share_count_query_requests', $services, $global_args );

		if ( ! empty( $services ) ) {

			foreach ( $services as $service ) {

				switch ( $service ) {

					case 'facebook':
						$args = array(
							'id' => urlencode( $global_args['url'] ),
						);

						$token = ea_share()->admin->settings_value( 'fb_access_token' );
						if ( $token ) {
							$query_args['access_token'] = urlencode( $token );
						}

						$api_query = add_query_arg( $query_args, 'https://graph.facebook.com/' );

						$api_response = wp_remote_get( $api_query, array(
							'sslverify'  => false,
							'user-agent' => 'LC ShareCounts',
						) );

						if ( ! is_wp_error( $api_response ) && 200 == wp_remote_retrieve_response_code( $api_response ) ) {

							$body = json_decode( wp_remote_retrieve_body( $api_response ) );

							// Not sure why Facebook returns the data in different formats sometimes.
							if ( isset( $body->shares ) ) {
								$share_count['Facebook']['share_count'] = $body->shares;
							} elseif ( isset( $body->share->share_count ) ) {
								$share_count['Facebook']['share_count'] = $body->share->share_count;
							}
							if ( isset( $body->comments ) ) {
								$share_count['Facebook']['comment_count'] = $body->comments;
							} elseif ( isset( $body->share->comment_count ) ) {
								$share_count['Facebook']['comment_count'] = $body->share->comment_count;
							}

							$share_count['Facebook']['like_count']  = $share_count['Facebook']['share_count'];
							$share_count['Facebook']['total_count'] = $share_count['Facebook']['share_count'] + $share_count['Facebook']['comment_count'];
						}
						break;

					case 'pinterest':
						$args = array(
							'callback' => 'receiveCount',
							'url'      => $global_args['url'],
						);

						$api_query = add_query_arg( $query_args, 'https://api.pinterest.com/v1/urls/count.json' );

						$api_response = wp_remote_get( $api_query, array(
							'sslverify'  => false,
							'user-agent' => 'LC ShareCounts',
						) );

						if ( ! is_wp_error( $api_response ) && 200 == wp_remote_retrieve_response_code( $api_response ) ) {

							$raw_json = preg_replace( '/^receiveCount\((.*)\)$/', "\\1", wp_remote_retrieve_body( $api_response ) );
							$body     = json_decode( $raw_json );

							if ( isset( $body->count ) ) {
								$share_count['Pinterest'] = $body->count;
							}
						}
						break;

					case 'linkedin':
						$args = array(
							'url'    => $global_args['url'],
							'format' => 'json',
						);

						$api_query = add_query_arg( $query_args, 'https://www.linkedin.com/countserv/count/share' );

						$api_response = wp_remote_get( $api_query, array(
							'sslverify'  => false,
							'user-agent' => 'LC ShareCounts',
						) );

						if ( ! is_wp_error( $api_response ) && 200 == wp_remote_retrieve_response_code( $api_response ) ) {

							$body = json_decode( wp_remote_retrieve_body( $api_response ) );

							if ( isset( $body->count ) ) {
								$share_count['LinkedIn'] = $body->count;
							}
						}
						break;

					case 'google':
						// Google+ counts have been mostly discontinued and
						// support has been removed in version 2.0.
						break;

					case 'stumbleupon':
						$args = array(
							'url' => $global_args['url'],
						);

						$api_query = add_query_arg( $args, 'https://www.stumbleupon.com/services/1.01/badge.getinfo' );

						$api_response = wp_remote_get( $api_query, array(
							'sslverify'  => false,
							'user-agent' => 'LC ShareCounts',
						) );

						if ( ! is_wp_error( $api_response ) && 200 == wp_remote_retrieve_response_code( $api_response ) ) {

							$body = json_decode( wp_remote_retrieve_body( $api_response ) );

							if ( isset( $body->result->views ) ) {
								$share_count['StumbleUpon'] = $body->result->views;
							}
						}
						break;

					case 'twitter':
						$twitter_count          = $this->query_newsharecounts_api( $global_args['url'] );
						$share_count['Twitter'] = false !== $twitter_count ? $twitter_count : $share_count['Twitter'];
						break;
				}
			}
		}

		return $share_count;
	}

	/**
	 * Update Share Counts
	 *
	 * @since 1.0.0
	 */
	public function update_share_counts() {

		$count_source = ea_share()->admin->settings_value( 'count_source' );

		if ( 'none' === $count_source ) {
			return;
		}

		$queue = apply_filters( 'ea_share_count_update_queue', $this->update_queue );

		if ( ! empty( $queue ) ) {

			foreach ( $queue as $id => $post_url ) {

				$share_count = $this->query_api( $post_url, $id );

				if ( $share_count && ( 'site' === $id || 0 === strpos( $id, 'http' ) ) ) {

					$share_option                      = get_option( 'ea_share_count_urls', array() );
					$hash                              = md5( $post_url );
					$share_option[ $hash ]['count']    = $share_count;
					$share_option[ $hash ]['datetime'] = time();
					$share_option[ $hash ]['url']      = $post_url;

					$total = $this->total_count( $share_count );

					if ( $total ) {
						$share_option[ $hash ]['total'] = $share_count;
					}

					update_option( 'ea_share_count_urls', $share_option );

				} elseif ( $share_count ) {

					update_post_meta( $id, 'ea_share_count', $share_count );
					update_post_meta( $id, 'ea_share_count_datetime', time() );

					$total = $this->total_count( json_decode( $share_count, true ) );

					if ( $total ) {
						update_post_meta( $id, 'ea_share_count_total', $total );
					}
				}

				// After processing remove from queue.
				unset( $this->update_queue[ $id ] );
			}
		}
	}

	/**
	 * Prime the pump.
	 *
	 * Ensure we have share count data for at least 100 posts.
	 * Useful when querying based on share count data.
	 *
	 * @link https://gist.github.com/billerickson/0f316f75430f3fd3a87c
	 * @since 1.1.0
	 * @param int $count how many posts should have sharing data.
	 * @param int $interval how many should be updated at once.
	 * @param bool $messages whether to display messages during the update.
	 */
	public function prime_the_pump( $count = 100, $interval = 20, $messages = false ) {

		$current = new WP_Query( array(
			'fields'         => 'ids',
			'posts_per_page' => $count,
			'meta_query'     => array(
				array(
					'key'     => 'ea_share_count',
					'compare' => 'EXISTS',
				),
			),
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
					),
				),
			) );

			if ( $update->have_posts() ) {

				foreach ( $update->posts as $i => $post_id ) {
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
