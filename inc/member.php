<?php

namespace rpi\Wall;


class Member extends stdClass
{

	public $ID;
    public $name;
    public string $url;
    public $post;  //CPT member WP_Post
    public $user;

    public function __construct($user_id)
    {
	    $this->user = get_userdata($user_id);

		if(is_a($this->user,'WP_User')){

			$this->post = get_posts(array(
				'post_status' => 'any',
				'post_type' => 'Member',
				'author' => $user_id
			));

			$this->ID = $user_id;
			$this->name = $this->user->display_name;

		}

        $this->url = wp_ulike_pro_get_user_profile_permalink($user_id);

    }

    public function get_member_profile_permalink()
    {
        wp_ulike_pro_get_user_profile_permalink($this->ID);
    }


    public
    function join_group($groupId)
    {
        if (add_post_meta($groupId, 'rpi_wall_member_id', $this->ID)
            && add_post_meta($this->ID, 'rpi_wall_group_id', $groupId)) {
            return true;
        } else {
            return false;
        }


    }

    public
    function leave_group($groupId)
    {

        if (delete_post_meta($groupId, 'rpi_wall_member_id', $this->ID)
            && delete_post_meta($this->ID, 'rpi_wall_group_id', $groupId)) {
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
        return get_post_meta($this->ID, 'rpi_wall_group_id');
    }

    public
    function is_in_group($group_id): bool
    {
        $groups = get_post_meta($this->ID, 'rpi_wall_group_id');
        return in_array($group_id, $groups);
    }

    public
    function set_message_read($post_id)
    {
    }

    public
    function get_messages()
    {
    }

    public
    function current_user_is_member()
    {
        if (get_current_user() === $this->name) {
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

}
