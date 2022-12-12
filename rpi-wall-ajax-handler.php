<?php

namespace rpi\Wall;

use MemberPage;
use rpi\Wall\Member;
use rpi\Wall\Group;

class RpiWallAjaxHandler
{

    public function __construct()
    {
        add_action('wp_ajax_rpi_wall_toggle_like', [$this, 'ajax_toggle_group_like']);
        add_action('wp_ajax_nopriv_rpi_wall_toggle_like', [$this, 'ajax_toggle_group_like']);

        add_action('wp_ajax_rpi_wall_toggle_watch', [$this, 'ajax_toggle_group_watch']);
        add_action('wp_ajax_nopriv_rpi_wall_toggle_watch', [$this, 'ajax_toggle_group_watch']);

        add_action('wp_ajax_rpi_ajax_mark_all_messages_read', [$this, 'ajax_mark_all_messages_read']);
        add_action('wp_ajax_nopriv_rpi_ajax_mark_all_messages_read', [$this, 'ajax_mark_all_messages_read']);

        add_action('wp_ajax_rpi_mark_and_display_message', [$this, 'ajax_mark_and_display_message']);
        add_action('wp_ajax_nopriv_rpi_mark_and_display_message', [$this, 'ajax_mark_and_display_message']);

        add_action('wp_ajax_rpi_tab_bio_content', [$this, 'ajax_rpi_tab_bio_content']);
        add_action('wp_ajax_nopriv_rpi_tab_bio_content', [$this, 'ajax_rpi_tab_bio_content']);

        add_action('wp_ajax_rpi_tab_profile_content', [$this, 'ajax_rpi_tab_profile_content']);
        add_action('wp_ajax_nopriv_rpi_tab_profile_content', [$this, 'ajax_rpi_tab_profile_content']);

        add_action('wp_ajax_rpi_tab_comments_content', [$this, 'ajax_tab_comments_content']);
        add_action('wp_ajax_nopriv_rpi_tab_comments_content', [$this, 'ajax_tab_comments_content']);

        add_action('wp_ajax_rpi_tab_groups_content', [$this, 'ajax_tab_groups_content']);
        add_action('wp_ajax_nopriv_rpi_tab_groups_content', [$this, 'ajax_tab_groups_content']);

        add_action('wp_ajax_rpi_tab_watch_content', [$this, 'ajax_tab_watches_content']);
        add_action('wp_ajax_nopriv_rpi_tab_watch_content', [$this, 'ajax_tab_watches_content']);

        add_action('wp_ajax_rpi_tab_messages_content', [$this, 'ajax_tab_messages_content']);
        add_action('wp_ajax_nopriv_rpi_tab_messages_content', [$this, 'ajax_tab_messages_content']);


        add_action('wp_ajax_rpi_tab_logout_content', [$this, 'ajax_tab_logout_content']);
        add_action('wp_ajax_nopriv_rpi_tab_logout_content', [$this, 'ajax_tab_logout_content']);

        add_action('wp_ajax_rpi_wall_close_pin_group', [$this, 'ajax_close_pin_group']);
        add_action('wp_ajax_nopriv_rpi_wall_close_pin_group', [$this, 'ajax_close_pin_group']);



    }

    public function ajax_toggle_group_like()
    {
        $response = ['success' => false];
        if (isset($_POST['group_id']) && is_user_logged_in()) {
            $group = new Group($_POST['group_id']);
            if ($group && $group->is_not_founded()) {

                $member = new Member();
                if ($member->is_in_group($group->ID)) {
                    echo json_encode($response);
                    die();
                }

                if ($group->is_pending()) {
                    $member->join_group($group->ID);
                    $is_member = $member->is_in_group($group->ID);
                    $amount = $group->get_members_amount();
                    $is_liker = true;
                } else {

                    $action = $member->toggle_like_group($group->ID);
                    if ($action == 'like') {
                        new Message($group, 'liked');
                    }
                    $is_liker = $member->is_liked_group($group->ID);
                    $amount = $group->get_likers_amount();
                    $is_member = false;
                }

                $response = [
                    'success' => true,
                    'is_liker' => $is_liker,
                    'is_member' => $is_member,
                    'amount' => $amount,
                    'likers' => $group->display_liker(),
                    'members' => $group->display_member()
                ];

            }

        }
        echo json_encode($response);
        die();

    }

    public function ajax_toggle_group_watch()
    {


        $response = ['success' => false];
        if (isset($_POST['group_id'])) {
            $group = new Group($_POST['group_id']);

            $member = new Member();
            $member->toggle_watch_group($group->ID);
            $amount = $group->get_watcher_amount();
            $amount = $amount > 0 ? $amount : '';
            $is_watcher = $member->is_watched_group($group->ID);

            $response = [
                'success' => true,
                'is_watcher' => $is_watcher,
                'amount' => $amount
            ];
        }
        echo json_encode($response);
        die();


    }

	public function ajax_tab_logout_content(){

		  wp_redirect(  str_replace('amp;','', wp_logout_url( ) ));

		  echo str_replace('amp;','', wp_logout_url( ) );
		  die();

	}

    public function ajax_mark_all_messages_read(){

		$member = new Member();
	    $args = [
		    'post_type' => 'message',
		    'numberposts' => -1,
		    'meta_query' => [
			    [
				    'key' => 'rpi_wall_message_recipient',
				    'value' => get_current_user_id(),
				    'compare' => '=',
				    'type' => 'NUMERIC'
			    ]
		    ]
	    ];
	    $wp_query = new \WP_Query($args);
		$messages = $wp_query->get_posts();

	    $readed = [];

		foreach ($messages as $message){
			$readed[$message->ID] = true;
		}
	    update_user_meta($member->ID, 'rpi_read_messages', $readed);

	    $response = ['success' => true];
		echo json_encode($response);

	    die();
    }
    public function ajax_mark_and_display_message()
    {
        $response = ['success' => false];
        if (isset($_POST['message_id'])) {
            $member = new Member();
            $message = get_post($_POST['message_id']);

            if ($read_messages = get_user_meta($member->ID, 'rpi_read_messages', true)) {
                $read_messages = unserialize($read_messages);
            } else {
                $read_messages = array();
            }
            if (!exists($read_messages, $_POST['message_id']))
            {
                Message::change_message_counter($member->ID, true);
                $member->set_message_read($_POST['message_id']);
            }
            $message_count = $member->get_unread_messages_count();
            $response = [
                'success' => true,
                'message_id' => $_POST['message_id'],
                'title' => $message->post_title,
                'content' => $message->post_content,
                'message_count' => $message_count
            ];
        }
        echo json_encode($response);
        die();

    }

    public function ajax_rpi_tab_bio_content()
    {
        $member_page = new MemberPage();
        echo $member_page->bio();
        die();
    }


    public function ajax_rpi_tab_profile_content()
    {
        $member_page = new MemberPage();
        echo $member_page->profile();
        die();
    }

    public function ajax_tab_comments_content()
    {

        $member_page = new MemberPage();
        echo $member_page->comments();
        die();
    }

    public function ajax_tab_groups_content()
    {
        $member_page = new MemberPage();
        echo $member_page->groups();
        die();
    }

    public function ajax_tab_watches_content()
    {

        $member_page = new MemberPage();
        echo $member_page->watches();
        die();
    }

    public function ajax_tab_messages_content()
    {
        $member_page = new MemberPage();
        echo $member_page->messages();
        die();
    }

    public function ajax_close_pin_group()
    {
//TODO : Write logic to close pin group

        /*
         * TODO:
         * Close Matrix chat
         * Disable Toolbar(No new protocols)
         * More Publications?
         * Option to reopen the group
         *
         */
    }

}
