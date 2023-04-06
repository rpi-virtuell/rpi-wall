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

        add_action('wp_ajax_rpi_ajax_delete_all_messages_read', [$this, 'ajax_delete_all_messages_read']);
        add_action('wp_ajax_nopriv_rpi_ajax_delete_all_messages_read', [$this, 'ajax_delete_all_messages_read']);

        add_action('wp_ajax_rpi_delete_message', [$this, 'ajax_delete_message']);
        add_action('wp_ajax_nopriv_rpi_delete_message', [$this, 'ajax_delete_message']);

        add_action('wp_ajax_rpi_ajax_termin_log_participant_and_redirect', [$this, 'ajax_termin_log_participant_and_redirect']);
        add_action('wp_ajax_nopriv_rpi_ajax_termin_log_participant_and_redirect', [$this, 'ajax_termin_log_participant_and_redirect']);

        add_action('wp_ajax_rpi_tab_bio_content', [$this, 'ajax_rpi_tab_bio_content']);
        add_action('wp_ajax_nopriv_rpi_tab_bio_content', [$this, 'ajax_rpi_tab_bio_content']);

        add_action('wp_ajax_rpi_tab_profile_content', [$this, 'ajax_rpi_tab_profile_content']);
        add_action('wp_ajax_nopriv_rpi_tab_profile_content', [$this, 'ajax_rpi_tab_profile_content']);

        add_action('wp_ajax_rpi_tab_comments_content', [$this, 'ajax_tab_comments_content']);
        add_action('wp_ajax_nopriv_rpi_tab_comments_content', [$this, 'ajax_tab_comments_content']);

        add_action('wp_ajax_rpi_tab_groups_content', [$this, 'ajax_tab_groups_content']);
        add_action('wp_ajax_nopriv_rpi_tab_groups_content', [$this, 'ajax_tab_groups_content']);

        add_action('wp_ajax_rpi_tab_created_content', [$this, 'ajax_tab_created_content']);
        add_action('wp_ajax_nopriv_rpi_tab_created_content', [$this, 'ajax_tab_created_content']);

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

                $is_member = false;

                if ($group->is_pending()) {

                    if ($is_member = $member->is_in_group($group->ID)) {
                        $member->leave_group($group->ID);
                        $is_liker = false;
                        $is_member = false;
                    } else {
                        $member->join_group($group->ID);
                        $is_liker = true;
                        $is_member = true;
                    }
                    $amount = $group->get_members_amount();

                } else {

                    if ($member->is_in_group($group->ID)) {
                        echo json_encode($response);
                        die();
                    }


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

    public function ajax_tab_logout_content()
    {

        wp_redirect(str_replace('amp;', '', wp_logout_url()));

        echo str_replace('amp;', '', wp_logout_url());
        die();

    }

    public function ajax_mark_all_messages_read()
    {

        $member = new Member();
        $args = [
            'post_type' => 'message',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'rpi_wall_message_recipient',
                    'value' => $member->ID,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ]
            ]
        ];
        $wp_query = new \WP_Query($args);
        $messages = $wp_query->get_posts();

        $readed = [];

        foreach ($messages as $message) {
            $readed[$message->ID] = true;
        }
        update_user_meta($member->ID, 'rpi_read_messages', serialize($readed));
        update_user_meta($member->ID, 'rpi_wall_unread_messages_count', 0);
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
            if (!exists($read_messages, $_POST['message_id'])) {
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

    public function ajax_delete_all_messages_read()
    {
        $response = ['success' => false];
        if (isset($_POST['user_id'])) {
            $member = new Member();
            $message = get_post($_POST['message_id']);


            if ($read_messages = get_user_meta($member->ID, 'rpi_read_messages', true)) {
                $read_messages = unserialize($read_messages);
            } else {
                $read_messages = array();
            }
            if (!empty($read_messages)) {
                foreach ($read_messages as $key => $read_message) {
                    delete_post_meta($key, 'rpi_wall_message_recipient', $member->ID);
                }
            }

            $response = [
                'success' => true,
            ];
        }
        echo json_encode($response);
        die();
    }


    public function ajax_delete_message()
    {
        $response = ['success' => false];
        if (isset($_POST['user_id'], $_POST['message_id'])) {
            $member = new Member();

            delete_post_meta($_POST['message_id'], 'rpi_wall_message_recipient', $member->ID);

            $response = [
                'success' => true,
            ];
        }
        echo json_encode($response);
        die();

    }

    public function ajax_termin_log_participant_and_redirect()
    {

        $response = ['success' => false];
        $response['redirect_link'] = get_option("options_online_meeting_link");

        if (isset($_REQUEST['post_id'])) {

            $next_termin = get_post($_REQUEST['post_id']);
        } else {
            $args = [
                'post_type' => 'termin',
                'meta_key' => 'termin_date',
                'numberposts' => 1,
                'orderby' => 'meta_value',
                'order' => 'ASC',
                'meta_query' =>
                    [
                        'key' => 'termin_date',
                        'compare' => '>=',
                        'value' => date('Y-m-d h:i:s', time() - 7200),
                    ]
            ];

            $termine = get_posts($args);
            $next_termin = reset($termine);

        }

        $termin_id = 0;

        if (is_a($next_termin, 'WP_Post')) {
            $termin_id = $next_termin->ID;
            $member = array();
            $guests = array();

            $participants = get_post_meta($termin_id, 'rpi_wall_termin_participant');
            foreach ($participants as $participant) {
                $member[] = intval($participant);
            }
            $participants = get_post_meta($termin_id, 'rpi_wall_termin_guest');
            foreach ($participants as $participant) {
                $guests[] = $participant;
            }


            if (is_user_logged_in()) {
                if (!in_array(get_current_user_id(), $member)) {
                    add_post_meta($termin_id, 'rpi_wall_termin_participant', get_current_user_id(), false);
                    $msg = new \stdClass();
                    $msg->subject = '[' . $next_termin->post_title . ']' . ' An Termin teilgenommen';
                    $msg->body = 'Du hast an dem Termin (<a href="' . get_permalink($termin_id) . '">' . $next_termin->post_title . '</a>) teilgenommen.';
                    Message::send_messages(get_current_user_id(), $msg);
                }
            } else {

                $ip = $_SERVER['REMOTE_ADDR'];
                if (!in_array($ip, $guests)) {
                    add_post_meta($termin_id, 'rpi_wall_termin_guest', $ip, false);
                }

            }


        }


        /*
        if (is_user_logged_in()) {
            $content = time() . ' ' . $_SERVER['REMOTE_ADDR'] . ' ' . 'ID:'. $termin_id . '  ' .wp_get_current_user()->ID . "\n";
        } else {
            $content = time() . ' ' . $_SERVER['REMOTE_ADDR'] . ' ' . 'ID:'. $termin_id . '  ' . 'UNKNOWN_USER' . "\n";
        }

        if (!file_exists(WP_CONTENT_DIR . '/uploads/meetings/')) {
            mkdir(WP_CONTENT_DIR . '/uploads/meetings/');
        }
        file_put_contents(WP_CONTENT_DIR . '/uploads/meetings/' . date('Y_m_d') . '_meeting_attendance.log', $content, FILE_APPEND);
		*/
        $response['success'] = true;

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

    public function ajax_tab_created_content()
    {

        $member_page = new MemberPage();
        echo $member_page->created();
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
