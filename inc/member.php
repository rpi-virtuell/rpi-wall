<?php

namespace rpi\Wall;


class Member extends \stdClass
{

    public $ID;
    public $name;
    public string $url;
    public $post;  //CPT member WP_Post
    public $user;

    public function __construct($user_id = 0)
    {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }

        $this->user = get_userdata($user_id);

        if (is_a($this->user, 'WP_User')) {

            $posts = get_posts(array(
                'post_status' => 'any',
                'post_type' => 'Member',
                'author' => $user_id
            ));
            $this->post = reset($posts);

            $this->ID = $user_id;
            $this->name = $this->user->display_name;

        }

        $this->url = wp_ulike_pro_get_user_profile_permalink($user_id);


    }

    public function get_member_profile_permalink()
    {
        return wp_ulike_pro_get_user_profile_permalink($this->ID);
    }


    protected
    function join_group($groupId)
    {

        if ($this->is_in_group($groupId)) {
            return false;
        }

        add_post_meta($groupId, 'rpi_wall_member_id', $this->ID);
        add_user_meta($this->ID, 'rpi_wall_group_id', $groupId);


    }

    public
    function leave_group($groupId)
    {

        if (delete_post_meta($groupId, 'rpi_wall_member_id', $this->ID)
            && delete_user_meta($this->ID, 'rpi_wall_group_id', $groupId)) {

            do_action('rpi_wall_member_left_group', $this, $groupId);
            return true;

        } else {
            return false;
        }

    }

    public
    function watch_group($groupid)
    {

    }

    public
    function get_groups()
    {
        return get_user_meta($this->id, 'rpi_wall_group_id');
    }

    public
    function is_in_group($group_id): bool
    {
        $groups = (array)get_user_meta($this->ID, 'rpi_wall_group_id');

        return in_array($group_id, $groups);
    }

    public
    function set_message_read($post_id)
    {
    }

    public
    function get_messages()
    {
        return get_posts([
            'post_type' => 'message',
            'mumberposts' => -1,
            'meta_query' => [
                'key' => 'recipient',
                'value' => $this->ID,
                'compare' => '=',
                'type' => 'NUMERIC'
            ]
        ]);
    }

    public
    function current_user_is_member()
    {
        if (get_current_user_id() === $this->ID) {
            return true;
        } else {
            return false;
        }
    }

    public
    function current_member_can($capability)
    {
        return user_can($this->ID, $capability);
    }


    /**
     * init action
     *
     * @return bool|void
     */
    public function init_handle_request()
    {


        if (isset($_REQUEST['action']) && isset($_REQUEST['hash']) && isset($_REQUEST['new_group_member'])) {


            if ('plgjoin' == $_REQUEST['action']) {

                $member = new Member(intval($_REQUEST['new_group_member']));
                $member->validate_and_join($_REQUEST['hash']);

                wp_redirect($member->get_member_profile_permalink());
                die();


            }
            return false;
        }
    }

    /**
     * @param $joinhash
     *
     * @return bool
     */
    public function validate_and_join($joinhash)
    {

        $groups = unserialize(get_user_meta($this->ID, 'rpi_wall_group_hash', true),);


        foreach ($groups as $group_id => $hash) {

            if ($hash === $joinhash) {
                $this->join_group($group_id);
                do_action('rpi_wall_member_joined_group', $this, $group_id);
                return;
            }
        }
    }

    /**
     * @param $group_id  Group
     *
     * @return string hash
     */
    public function get_join_hash($group_id)
    {

        $groups = unserialize(get_user_meta($this->ID, 'rpi_wall_group_hash', true));

        $hash = wp_hash($this->name, 'nonce');

        $groups[$group_id] = $hash;

        update_user_meta($this->ID, 'rpi_wall_group_hash', serialize($groups));

        return $groups[$group_id];

    }


    /**
     * @param $group_id Group
     *
     * @return string html link
     */

    public function get_joinlink($group_id)
    {

        $hash = $this->get_join_hash($group_id);
        return '<a href="' . get_home_url() . '?action=plgjoin&hash=' . $hash . '&member=' . $this->ID . '">Gruppe beitreten</p>';

    }
}
