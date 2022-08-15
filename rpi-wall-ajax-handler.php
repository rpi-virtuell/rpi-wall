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

        add_action('wp_ajax_rpi_wall_close_pin_group', [$this, 'ajax_close_pin_group']);
        add_action('wp_ajax_nopriv_rpi_wall_close_pin_group', [$this, 'ajax_close_pin_group']);

        // Pin Tabs

        add_action('wp_ajax_rpi_tab_group_content', [$this, 'ajax_tab_group_content']);
        add_action('wp_ajax_nopriv_rpi_tab_group_content', [$this, 'ajax_tab_group_content']);

        add_action('wp_ajax_rpi_tab_pin_content', [$this, 'ajax_tab_pin_content']);
        add_action('wp_ajax_nopriv_rpi_tab_pin_content', [$this, 'ajax_tab_pin_content']);

    }

    public function ajax_toggle_group_like()
    {
        $response = ['success' => false];
        if (isset($_POST['group_id'])) {
            $group = new Wall\Group($_POST['group_id']);
            if ($group && $group->is_not_founded()) {

                $member = new Wall\Member();
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

    public function ajax_mark_and_display_message()
    {
        $response = ['success' => false];
        if (isset($_POST['message_id'])) {
            $member = new Member();
            $message = get_post($_POST['message_id']);
            $member->set_message_read($_POST['message_id']);
            $response = [
                'success' => true,
                'message_id' => $_POST['message_id'],
                'title' => $message->post_title,
                'content' => $message->post_content];
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

    public function ajax_tab_group_content()
    {
        $this->display_constituted_group_title();
        die();
    }


    function display_constituted_group_title()
    {

    }

    public function ajax_tab_pin_content()
    {
        //TODO WIP
        die();
    }

    public function ajax_close_pin_group()
    {

    }

}