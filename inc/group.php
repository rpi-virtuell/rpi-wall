<?php

namespace rpi\Wall;

use rpi\Wall\Matrix\Helper;

class Group extends \stdClass
{

    public $ID;
    public $slug;
    public string $url;
    public $post;
    public $group_status = null;
    public $pending_days;
    public $matrix_server_home = 'matrix.rpi-virtuell.de';
    public $matrix_server_base = 'rpi-virtuell.de';


    /**
     * Todo
     * stati: pending, acreated
     * erstellen des channels nach der mindestzahl von PLG
     * nach pending phase Channel schließen
     */

    /**
     * @param int|WP_Post|null $post
     *
     * @return Group
     */

    public function __construct($post_id)
    {

        $this->post = get_post($post_id, ARRAY_A);
        $this->ID = $post_id;
        $this->group_status = $this->get('pl_group_status');
        $this->slug = 'dibes_plg_' . $this->ID;
        $this->title = 'PLG ' . substr(preg_replace('/[^\w\s-]/i', '', $this->post->post_title), 0, 40);
        $this->channel_url = "https://{$this->matrix_server_home}/#/room/#{$this->slug}:rpi-virtuell.de";
        $this->pending_days = get_option('options_rpi_wall_pl_group_pending_days', 7);

        $this->start_PLG_link = $this->get_starlink();


    }

    static function init_cronjob()
    {

        //var_dump('init_cronjob');die();

        // check alle Gruppen, die keinen status, aber likers haben
        // wenn minimum likers erreicht: Gründungsphase zu starten

        $args = [
            'post_type' => 'wall',
            'numberposts' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'like_amount',
                    'value' => get_option('options_pl_group_min_required_members', 3),
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ],
                [
                    'key' => 'pl_group_status',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];

        $posts = get_posts($args);


        foreach ($posts as $post) {
            $group = new Group($post->ID);
            $group->set_status('ready');
            new Message($group, 'ready', ['orga', 'group']);
            do_action('rpi_wall_pl_group_ready', $group);
        }


        // check alle Gruppen, die den status pending haben und die pending time abgelaufen ist
        // wenn genug Mitglieder gejoined: create matrix room
        // wenn nicht genug Mitglieder : reset
        $daySeconds = 86400;
        $pending_add = $daySeconds * get_option('options_rpi_wall_pl_group_pending_days', 7);

        $args = [
            'post_type' => 'wall',
            'mumberposts' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'pl_group_status',
                    'value' => 'pending',
                    'compare' => '='
                ],
                [
                    'key' => 'pl_group_status_timestamp',
                    'value' => time() - $pending_add,
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                ]
            ]
        ];

        $posts = get_posts($args);
        foreach ($posts as $post) {
            $group = new Group($post->ID);
            if ($group->get_members_amount() < get_option('options_pl_group_min_required_members', 3)) {
                $group->reset_status();
                new Message($group, 'reset');
                do_action('rpi_wall_pl_group_reset', $group);

            } else {
                $group->create_room();
                $group->set_status('founded');
                new Message($group, 'founded');
                do_action('rpi_wall_pl_group_founded', $group);
            }

        }


    }

    static function init_handle_requests()
    {
        if (isset($_REQUEST['action']) && isset($_REQUEST['hash']) && isset($_REQUEST['new_plg_group'])) {
            $group = new Group($_REQUEST['new_plg_group']);
            if ('plgstart' == $_REQUEST['action'] && $_REQUEST['hash'] == $group->get_hash('start')) {

                $group->start_pending();
            }

        }
    }


    /**
     * @return string
     */
    public function get_status()
    {
        return $this->get('pl_group_status');
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return !empty($this->get_status());
    }

    /**
     * @return bool
     */
    public function is_ready()
    {
        return $this->get_status() === 'ready';
    }

    /**
     * @return bool
     */
    public function is_pending()
    {
        return $this->get_status() === 'pending';
    }

    /**
     * @return bool
     */
    public function is_founded()
    {
        return $this->get_status() === 'founded';
    }

    /**
     * @return bool
     */
    public function is_closed()
    {
        return $this->get_status() === 'closed';
    }

    /**
     * @param string $status ready|pending|founded|closed or null
     *
     * @return void
     */
    public function set_status(string $status)
    {
        if (empty($status)) {
            delete_post_meta($this->ID, 'pl_group_status');
        } else {
            update_post_meta($this->ID, 'pl_group_status', $status);
            if ($status == 'pending')
                $this->set_status_time();
        }

    }

    /**
     * @return void
     */
    protected function set_status_time()
    {
        update_post_meta($this->ID, 'pl_group_status_timestamp', time());
    }

    /**
     * remove members and status
     * if pending time out and noch enouph user du found a PLG
     *
     * @return void
     */
    public function reset_status()
    {

        $this->remove_members();

        delete_post_meta($this->ID, 'pl_group_status_timestamp');

        $this->set_status('');
    }

    /**
     * @return Date
     */
    public function get_status_date()
    {

        return date('d.n.Y', $this->get('pl_group_status_timestamp'));
    }

    public function get_pending_time()
    {

        if ($this->group_status = 'pending') {
            $daySeconds = 86400;

            $end_time = $this->get('pl_group_status_timestamp') + ($this->pending_days * $daySeconds);
            $pendingtime = $end_time - time();

            $days = floor($pendingtime / 86400);
            $hours = floor(($pendingtime - $days * 86400) / 3600);
            $minutes = floor(($pendingtime / 60) % 60);

            $format = '%d Tage, %d Stunden und %d Sekunden';
            $timeformated = sprintf($format, $days, $hours, $minutes);

            return apply_filters('rpi_wall_pendingtime', $timeformated, $days, $hours, $minutes);
        }
        return '';

    }


    /**
     * @param string $context email|html|matrix
     *
     * @return string
     */
    public function get_matrix_link(string $context = 'html')
    {
        switch ($context) {
            case 'html':
                return '<a href="' . $this->channel_url . '">#' . $this->slug . ':rpi-virtuell.de</a>';
                break;
            case 'matrix':
                return "#{$this->slug}:rpi-virtuell.de";
                break;
            case 'email':
                return $this->channel_url;
                break;
        }
    }

    /**
     * @return array WP_User[]
     */
    public function get_likers()
    {

        return get_users(array(
            "include" => wp_ulike_get_likers_list_per_post('ulike', 'likers_list', $this->ID, 100)
        ));
    }

    /**
     * @return int
     */
    public function get_likers_amount()
    {

        return get_post_meta($this->ID, 'like_amount', true);

    }

    /**
     * @return array user Ids
     */
    public function get_likers_Ids()
    {

        return wp_ulike_get_likers_list_per_post('ulike', 'likers_list', $this->ID, 100);
    }

    /**
     * @return array WP_User[]
     */
    public function all_get_comments_liker()
    {

        $likers = get_users(['include', $this->get_comment_liker_Ids()]);
        return $likers;
    }

    /**
     * @return array $user_id[]
     */
    public function all_get_comments_likerIds()
    {

        $comments = get_comments(['post_id' => $this->id]);

        $likers = [];
        foreach ($comments as $comment) {
            $ids = $this->get_comment_liker_Ids($comment->comment_ID);
            foreach ($ids as $user_id) {
                $likers[] = $user_id;
            }
        }
        return array_unique($likers);

    }

    /**
     * @return array $user_id[]
     */
    public function get_comment_liker_Ids($comment_id)
    {
        return wp_ulike_get_likers_list_per_post('ulike_comments', 'likers_list', $comment_id, 100);
    }

    /**
     * @return array $user_id[]
     */
    public function get_comment_likescount()
    {
        $likes = 0;
        foreach (get_comments(['post_id' => $this->ID]) as $comment) {
            $likes += intval(wp_ulike_get_comment_likes($comment->comment_ID));
        }
        return $likes;
    }

    /**
     * @return string comma separated matrix user_ids
     */
    public function get_members_matrix_ids()
    {
        $ids = $this->get_members_matrix_ids();
        $return = [];
        foreach ($ids as $id) {
            if ($matrixId = get_user_meta($id, 'matrixId', true)) {
                $return[] = $matrixId;
            } else {
                $return[] = '@' . get_user_by('ID', $id)->user_login . ':' . $this->matrix_server_base . '(?)';

            }

        }
    }

    /**
     * @return array Member[]
     *
     */
    public function get_members()
    {
        $members = [];
        foreach ($this->get_memberIds() as $member_id) {
            $members[] = new Member($member_id);
        }
        return $members;
    }

    /**
     * @param \WP_User $user_id
     *
     * @return Member
     */
    public function get_member($user_id)
    {
        return new Member($user_id);
    }

    /**
     * @return int
     */
    public function get_members_amount()
    {
//		if(!$this->get_memberIds()){
//			return 0;
//		}
        return count($this->get_memberIds());
    }

    /**
     * @return array
     */
    public function get_memberIds()
    {

        return (array)get_post_meta($this->ID, 'rpi_wall_member_id');

    }

    /**
     * Remove UserIds from Wall Post Meta (Group) und PostIds from User Meta
     * @return void
     */
    protected function remove_members()
    {
        foreach ($this->get_memberIds() as $user_id) {
            delete_post_meta($this->ID, 'rpi_wall_member_id', $user_id);
            delete_user_meta($user_id, 'rpi_wall_group_id', $this->ID);
        };
    }

    /**
     * @return array $user_id[]
     */
    public function get_watcherIds()
    {
        return get_post_meta($this->ID, 'rpi_wall_watcher_id');
    }

    /**
     * @return array WP_User[]
     */
    public function get_watcher()
    {
        return get_users(['include' => $this->get_watcherIds()]);
    }

    /**
     * @return int
     */
    public function get_watcher_amount()
    {
        return count($this->get_watcherIds());
    }

    /**
     * get metakey value
     *
     * @param $key
     * @return mixed
     */
    protected function get($key)
    {
        return get_post_meta($this->ID, $key, true);
    }

    /**
     * @return string <embed>widgetcontent</embed>
     *
     * Todo
     */
    protected function get_toolbar()
    {
        return '';
    }

    /**
     * @param string $room_id
     * @return void
     */
    protected function set_room_id(string $room_id)
    {
        update_post_meta($this->ID, 'pl_group_matrix_room_id');
    }

    protected function get_matrix_channel_id()
    {
        return $this->get('pl_group_matrix_room_id');
    }

    protected function get_joined_member_matrixId($user_login)
    {
        return Matrix\Helper::getUser($user_login);
    }


    protected function start_pending()
    {
        $this->set_status('pending');
        new Message($this, 'pending');
        do_action('rpi_wall_pl_group_pending', $this);

    }

    protected function create_room()
    {

        $room_id = Matrix\Helper::create_room($this);
        /**
         * Message to orga channel
         * E-Mails to likers
         */
        do_action('rpi_wall_pl_group_room_created', $room_id);
    }


    public function get_starlink($label = 'Gruppe gründen')
    {

        return '<a class="button" href="' . get_home_url() . '?action=plgstart&hash=' . $this->get_hash('start') . '&new_plg_group=' . $this->ID . '">' . $label . '</a>';
    }


    public function get_current_users_joinlink($label = 'Gruppe beitreten')
    {
        $member = new Member(get_current_user_id());

        if (!$this->has_member($member)) {
            $hash = $member->get_join_hash($this->ID);
            return '<a class="button" href="' . get_home_url() . '?action=plgjoin&hash=' . $hash . '&new_group_member=' . $member->ID . '">' . $label . '</a>';
        }

        return 'Du bist Mitglied';
    }

    public function has_member($member)
    {
        if (is_a($member, 'rpi\Wall\Member')) {
            $user_id = $member->ID;
        } else {
            $user_id = $member;
        }
        return in_array($user_id, $this->get_memberIds());
    }

    /**
     * @param string $type start|join
     *
     * @return array|string|string[]
     */
    protected function get_hash($type = 'start')
    {

        $hash = md5($this->slug . '_start_founding_plg');

        if ($type === 'join') {
            $hash = md5($this->slug . '_join_plg');
        }
        return $hash;

    }


    //outputs
    public function display()
    {


        switch ($this->get_status()) {
            case'ready':
                $headline = get_option('options_rpi_wall_not_founded_header', 'Professionellen Lerngruppe (PLG)');
                $notice = get_option('options_rpi_wall_ready_notice', 'Mit Klick auf "Gruppe Gründen" werden alle interessierten angeschrieben und haben eine Woche Zeit, der PLG beizutreten.');
                $button = $this->get_starlink();
                $stats = $this->get_likers_amount() . ' Interessierte.';
                break;
            case'pending':
                $headline = get_option('options_rpi_wall_not_founded_header', 'Wir suchen noch Leute für eine Professionellen Lerngruppe (PLG) zu diesem Kontext');
                if (!$this->has_member(get_current_user_id())) {
                    $notice = get_option('options_rpi_wall_pending_notice', 'Die Gruppe befindet sich in der Gründungsphase. Möchtest du dabei sein?');
                }

                $button = $this->get_current_users_joinlink();
                $stats = $this->get_members_amount() . ' von ' . $this->get_likers_amount() . ' der Interessierten haben sich bereits angemeldet.';
                break;
            case'founded':
                $headline = get_option('options_rpi_wall_founded_header', 'Professionelle Lerngruppe (PLG) zu diesem Kontext');
                $notice = get_option('options_rpi_wall_founded_header', 'Zu diesem Pinwandeintrag hat sich eine PLG gegründet.');
                $button = $this->get_current_users_joinlink('Beitritt anfragen');
                $stats = $this->get_members_amount() . ' Mitglieder.';
                break;
            case'closed':
                $headline = get_option('options_rpi_wall_founded_header', 'Professionelle Lerngruppe (PLG) zu diesem Kontext');
                $notice = get_option('options_rpi_wall_founded_header', '');
                $stats = 'Gruppe geschlossen';
                break;
            default:
                $headline = get_option('options_rpi_wall_not_founded_header', 'Interessiert an einer Professionellen Lerngruppe (PLG) zu diesem Kontext?');
                $notice = get_option('options_rpi_wall_not_founded_notice', 'Wenn du zu den Interessierten gehörst, wirst du automatisch benachrichtigt, sobald sich genügend Interessenten gefunden haben.');
                $stats = $this->get_likers_amount() . ' von mindestens ' . $this->group_member_min . ' sind interessiert';

        }


        echo '<div class="gruppe">';
        echo '<div class="gruppe-wrapper">';

        echo '<div class="gruppe-header">' . $headline . '</div>';

        echo '<div class="gruppe-liker">';
        echo do_shortcode('[wp_ulike  style="wpulike-heart"]');
        echo '</div>';

        echo '<div class="gruppe-footer">';
        echo '<div class="notice">' . $notice . ' ' . $stats . '</div>';

        if ($button) {
            echo '<div class="gruppe-button">' . $button . '</div>';
        }
        echo '</div>'; //end footer

        echo '</div>'; //end wrapper
        echo '</div>'; //end gruppe
    }

    public function display_member()
    {
        foreach ($this->get_memberIds() as $user_id) {
            echo get_avatar($user_id, 48);
        }
    }

    public function display_short_info()
    {

        $min_required = get_option('options_pl_group_min_required_members', 3);
        $max_likes = get_option('options_rpi_wall_max_stars_per_comment', 3);

        switch ($status = $this->get_status()) {
            case'ready':
                $notice = get_option('options_rpi_wall_ready_card_notice', 'PLG gründen: ');
                $stats = $this->get_likers_amount() . ' Interessierte';
                break;
            case'pending':
                $notice = get_option('options_rpi_wall_ready_card_notice', 'Beitrittsphase zu einer PLG läuft.');
                $stats = $this->get_members_amount() . ' / ' . $this->get_likers_amount() . ' beigetreten';
                break;
            case'founded':
                $notice = '';
                $stats = $this->display_member();

                break;
            case'closed':
                $notice = get_option('options_rpi_wall_ready_card_notice', 'PLG beendet');
                break;
            default:
                $notice = '';
                $stats = ($i = $this->get_likers_amount()) > 0 ? $i . ' / ' . $min_required . ' für PLG' : '';
        }


        $likes = 0;
        foreach (get_comments(['post_id' => $this->ID]) as $comment) {
            $likes += intval(wp_ulike_get_comment_likes($comment->comment_ID));
        }
        if ($likes > 0) {
            $n = $likes;
            if ($likes > $max_likes) {
                $n = $max_likes;
                $addlikes = $likes - $max_likes;
                echo '<style>#more-likes-' . get_the_ID() . '::after{ content: "+' . $addlikes . '";}</style>';
            }
        }

        echo '<div class="card_plg_info">';
        echo '<div class="plg-wrapper">';
        echo '<div class="plg plg-' . $status . '">
						<a href="' . get_the_permalink() . '">' . $notice . '</a>
						<span plg-stats>' . $stats . '</span>';
        echo '</div>';
        echo '<div class="hot-comments">';
        for ($i = 0; $i < $n; $i++) {
            echo '<i id="more-likes-' . get_the_ID() . '" class="wp_ulike_star_icon ulp-icon-star"></i>';

        }
        echo '</div>';
        echo '</div>';
        echo '</div>';


    }
}


