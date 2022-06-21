<?php
namespace rpi\Wall;

class Shortcodes{
	public function __construct() {

		add_shortcode( 'user_pinned_posts', [$this,'get_users_pinwall_posts'] );
        add_shortcode('rpi-userprofile', array($this, 'get_user_profile_tags'));

	}

	/**
	 * [user_pinned_posts]
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function get_users_pinwall_posts($atts){
		global $post;

		$attributes = shortcode_atts( array(
			'post_type' => 'wall',
			'past_days' => '',
			'per_page'  => 10
		), $atts );

		if( strpos( $attributes['post_type'], ',' ) ){
			$attributes['post_type'] = explode(',', $attributes['post_type']);
		}

		$currentUser = is_user_logged_in() ? get_current_user_id() : wp_ulike_generate_user_id( wp_ulike_get_user_ip() );
		$getPosts    = NULL;

		if( empty( $attributes['past_days'] ) ){
			$pinnedItems = wp_ulike_get_meta_data( $currentUser, 'user', 'post_status', true );
			// Exclude like status
			$pinnedItems = ! empty( $pinnedItems ) ? array_filter($pinnedItems, function($v, $k) {
				return $v == 'like';
			}, ARRAY_FILTER_USE_BOTH) : NULL;

			if( ! empty( $pinnedItems ) ){
				$getPosts = get_posts( array(
					'post_type'      => $attributes['post_type'],
					'post_status'    => array( 'publish', 'inherit' ),
					'posts_per_page' => $attributes['per_page'],
					'post__in'       => array_reverse( array_keys( $pinnedItems ) ),
					'orderby'        => 'post__in'
				) );
			}

		} else {
			$getPosts = wp_ulike_get_most_liked_posts( $attributes['per_page'], $attributes['post_type'], 'post', array(
				'start' => wp_ulike_pro_get_past_time( $attributes['past_days'] ),
				'end'   => current_time( 'mysql' )
			), array( 'like' ), false, 1, $currentUser );
		}



		echo '<div class="wp-ulike-pro-items-container user_pinned_posts">';
		if( ! empty( $getPosts ) ){
			foreach ( $getPosts as $post ) :
				setup_postdata( $post );
				blocksy_render_archive_card();

			endforeach;
			wp_reset_postdata();

		}
		echo '</div>';
	}

    public function get_user_profile_tags($atts)
    {
        global $wp_ulike_pro_current_user;


        if (isset($atts['content']) && is_a($wp_ulike_pro_current_user, 'WP_User')) {
            echo '<ul>';
            $member = get_page_by_title($wp_ulike_pro_current_user->display_name, 'OBJECT', 'Member');
            if (post_type_exists($atts['content'])) {
                //TODO: Gruppen Link einfügen (Link auf Pinns mit gruppen)
            } elseif (taxonomy_exists($atts['content'])) {
                $terms = wp_get_post_terms($member->ID, $atts['content']);
                foreach ($terms as $term) {
                    if (is_a($term, 'WP_Term')) {
                        echo '<a href="' . site_url() . '/' . $atts['content'] . '/' . $term->slug . '">' . $term->name . '</a>';
                        echo '<br>';
                    }
                }
            }
            echo '</ul>';
        }
    }

}
