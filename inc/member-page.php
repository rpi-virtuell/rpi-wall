<?php

class MemberPage {

	public $is_my_page = false;
	public \rpi\Wall\member $member;
	public $is_member_page = false;

	public function __construct() {


		add_action('blocksy:single:content:top', [$this,'init']);



	}

	public function init(){

		if('member' === get_post_type()){
			$this->member = new \rpi\Wall\member(get_post()->post_author);
			$this->is_member_page = true;
			$this->post = $this->member->post ;
			$this->ID = $this->member->post->ID;
			if($this->member->ID == get_current_user_id()){
				$this->is_my_page = true;
			}
            $this->display();
		}


	}

	public function is_member_page(){
		return $this->is_member_page;
	}
	public function is_my_page(){
		return $this->is_my_page;
	}



	public function display(){

        $tags = '<div class="member-tags"> 
            [my_tags content="wall-tag"]
            [my_tags content="badge"]
            [my_tags content="schooltype"]
            [my_tags content="profession"]
         </div>';

		$tags = do_shortcode( $this->member->user->user_description. $tags);

		$tabs = new \rpi\Wall\Tabs('tabset');


		$tabs->addTab(['label' =>'Über mich',   'name'  => 'bio', 'content' =>  $tags, 'checked'=>'checked' ]);
		$tabs->addTab(['label' =>'Gruppen',    'name'  =>'groups',  'content' =>  $this->groups()]);
		$tabs->addTab(['label' =>'Kommentare', 'name'  =>'comments','content' =>  $this->comments()]);
		$tabs->addTab(['label' =>'Abonnements','name'  =>'watch',   'content' =>  $this->watches()]);
		$tabs->addTab(['label' =>'Benachrichtigungen','name'  =>'messages', 'content' => $this->messages() ,'permission' =>  'self']);

		$tabs->display();

	}
	public function groups(){


		$out =  '<div class="group-posts">';


		$query = $this->member->get_query_all_groups();
		if($query && $query->have_posts()) {
			while ( $query->have_posts() ) {
                ob_start();
				\rpi\Wall\Shortcodes::display_post( $query->the_post()  );
				$out .= ob_get_clean();
			}
		}
		wp_reset_query();
		$out .= '</div>';
		return $out;
	}

	public function watches(){
		ob_start();

		echo '<div class="group-posts">';
		$query = $this->member->get_query_watched_groups();
		if($query && $query->have_posts()) {
			while ( $query->have_posts() ) {

				\rpi\Wall\Shortcodes::display_post( $query->the_post()  );
			}
		}
		wp_reset_query();
		echo '</div>';
		return ob_get_clean();
	}

	public function comments(){

		ob_start();

		foreach ($this->member->get_my_comments_query() as $comment){
			?>
			<div class="member-coment">
				<?php echo $this->member->display(24); ?>
				<div class="entry-title">
					<?php echo $comment->comment; ?>
				</div>
				<div class="entry-content">
					<?php echo  $comment->comment_content; ?>
				</div>
				<div class="entry-post-permalink">
					<div class="pin-icon"><?php echo \rpi\Wall\Shortcodes::$pin_icon;?></div>
					<a href="<?php echo  get_comment_link($comment);?>"><?php echo $comment->post->post_title; ?></a>
				</div>

			</div>
			<?php

		}
		return ob_get_clean();

	}
    public function messages(){
        $user = wp_ulike_pro_get_current_user();

        $paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

        $args = [
            'post_type' => 'message',
            'posts_per_page' => 10,
            'paged' => $paged,
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
        $wp_query = new \WP_Query($args);
        $messages = $wp_query->get_posts();

        ob_start();
        ?>
        <div class="member-message-grid">
            <?php
            foreach ( $messages as $post ):
                setup_postdata( $post );
                ?>
                <div class="message">
                    <details class="message-content">
                        <summary class="entry-title">
                            <?php echo date('d.n.Y',strtotime($post->post_date));?>: <?php echo $post->post_title;?>
                        </summary>
                        <?php echo $post->post_content;?>
                    </details>
                </div>
            <?php
            endforeach;
            ?>
        </div>
        <?php

        echo '<hr>';
        echo paginate_links( array(
            'format' => '?paged=%#%',
            'current' => max( 1, get_query_var('paged') ),
            'total' => $wp_query->max_num_pages
        ) );
        wp_reset_postdata();
        return ob_get_clean();
    }


}