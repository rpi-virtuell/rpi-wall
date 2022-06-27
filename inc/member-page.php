<?php

class MemberPage {

	public $is_my_page = false;
	public $member;
	public $is_member_page = false;

	public function __construct() {

		add_action('wp_head',array($this, 'init'));

		add_action('blocksy:single:content:top', $this->init());



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

		$tabs = new \rpi\Wall\Tabs('tabset');

		$tabs->addTab('Gruppen',    'groups',   $this->groups());
		$tabs->addTab('Kommentare', 'comments', $this->comments());
		$tabs->addTab('Abonnements','watches',    $this->watches());

		$tabs->display();

	}
	public function groups(){
		ob_start();

		echo '<div class="group-posts">';


		$query = $this->member->get_query_all_groups();
		if($query->have_posts()) {
			while ( $query->have_posts() ) {
				\rpi\Wall\Shortcodes::display_post( $query->the_post()  );
			}
		}
		wp_reset_query();
		echo '</div>';
		return ob_get_clean();
	}

	public function watches(){
		ob_start();

		echo '<div class="group-posts">';
		$query = $this->member->get_query_watched_groups();
		if($query->have_posts()) {
			while ( $query->have_posts() ) {
				\rpi\Wall\Shortcodes::display_post( $query->the_post()  );
			}
		}
		wp_reset_query();
		echo '</div>';
		return ob_get_clean();
	}

	public function comments($atts){

		ob_start();
		$member  = new member($this->user->ID);
		foreach ($member->get_my_comments_query() as $comment){
			?>
			<div class="member-coment">
				<?php echo $member->display(24); ?>
				<div class="entry-title">
					<?php echo $comment->comment; ?>
				</div>
				<div class="entry-content">
					<?php echo  $comment->comment_content; ?>
				</div>
				<div class="entry-post-permalink">
					<div class="pin-icon"><?php echo self::$pin_icon;?></div>
					<a href="<?php echo  get_comment_link($comment);?>"><?php echo $comment->post->post_title; ?></a>
				</div>

			</div>
			<?php

		}
		return ob_get_clean();

	}


}
