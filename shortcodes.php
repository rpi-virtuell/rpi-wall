<?php
namespace rpi\Wall;

class Shortcodes{
	public function __construct() {

		add_shortcode( 'user_pinned_posts', [$this,'get_users_pinwall_posts'] );
        add_shortcode('rpi-userprofile', array($this, 'get_user_profile_tags'));
        add_shortcode('my_messages', array($this, 'get_user_messages'));
        add_shortcode('my_groups', array($this, 'get_user_groups'));
        add_shortcode('my_likes', array($this, 'get_user_likes'));

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

		$currentUser = wp_ulike_pro_get_current_user();
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

	public function get_user_messages($atts){

		$user = wp_ulike_pro_get_current_user();

		//var_dump($user->ID);

		$args = [
			'post_type' => 'message',
			'numberposts' => -1,
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => 'message_recipient',
					'value' => $user->ID,
					'compare' => '>=',
					'type' => 'NUMERIC'
				],
				[
					'key' => 'message_read',
					'compare' => 'NOT EXISTS'
				]
			]
		];
		$messages = get_posts($args);

		ob_start();
		foreach ( $messages as $message ):
			setup_postdata( $message );
			?>
				<div class="message">
					<details class="message-content">
						<summary class="entry-title">
							<?php echo date('d.n.Y',strtotime($message->post_date));?>: <?php echo $message->post_title;?>
						</summary>
						<?php echo $message->post_content;?>
					</details>
				</div>
			<?php
		endforeach;
		wp_reset_postdata();
		return ob_get_clean();
	}


	public function get_user_groups($atts){

		$user = wp_ulike_pro_get_current_user();

		//var_dump($user->ID);

		$args = [
			'post_type' => 'wall',
			'numberposts' => -1,
			'meta_query' => [
				[
					'key' => 'rpi_wall_member_id',
					'value' => $user->ID,
					'compare' => '>=',
					'type' => 'NUMERIC'
				]
			]
		];
		$groups = get_posts($args);
        if(!$groups){
            return 'Noch keine Mitgliedschaft in professionellen Lerngruppen';
        }

		ob_start();
		foreach ( $groups as $group ):
			setup_postdata( $group );
            $plg  = new Group($group->ID);
			?>
			<div class="mygroup">
                <div class="mygroup-wrapper">
                    <div class="entry-title"><h3><?php echo $plg->title;?></h3></div>
                    <div class="content">
                        <?php echo wp_trim_words(get_the_content(),50,'...');?>
                    </div>
                    <div><?php echo $plg->get_members_amount();?> Mitglied(er) <?php echo $plg->get_status()==='pending'?', Status: Gründungsphase':'';?></div>
                    <div>
                        <a href="<?php the_permalink()?>">Pinwandeintrag</a>
                        <?php if('pending' !== $plg->get_status()):
                            ?>| Matrix Raum: <?php echo $plg->get_matrix_link();?>
                        <?php endif;?>
                    </div>
			    </div>
			</div>
		<?php
		endforeach;
		wp_reset_postdata();
		return ob_get_clean();
	}

    public function get_user_likes($atts){

	    $args   = shortcode_atts( array(
		    "type"           => 'post',
		    "rel_type"       => 'wall',
		    "user_id"        => '',
		    "anonymize_user" => false,
		    "status"         => 'like',
		    "is_popular"     => true,
		    "period"         => 'all',
		    "style"          => 'default',
		    "has_pagination" => false,
		    "limit"          => 50,
		    "empty_text"     => 'Bisher für keine Gruppe Interesse'
	    ), $atts );

	    // Set global var

	    global $wp_ulike_query_args;
	    $wp_ulike_query_args =  $args;

	    $user = wp_ulike_pro_get_current_user();

	    // Load template
	    return wp_ulike_pro_get_public_template( 'content', $user->ID );
    }

}
