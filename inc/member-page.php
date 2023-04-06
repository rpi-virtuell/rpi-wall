<?php

class MemberPage
{

    public $is_my_page = false;
    public \rpi\Wall\Member $member;
    public $is_member_page = false;
    public $posts_per_page = 6;
    public $matrix_home = 'matrix.rpi-virtuell.de';


    public function __construct()
    {

        $this->messages_per_page = get_option('options_rpi_wall_memberpage_posts_per_page', 6);
        add_action('wp', [$this, 'init']);
        add_action('blocksy:hero:custom_meta:after', [$this, 'the_matrixId']);
        add_action('blocksy:single:content:bottom', [$this, 'display']);

    }

    public function init()
    {


        if (is_singular('member')) {
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url() . $_SERVER['REQUEST_URI']));
            }


            $this->member = new \rpi\Wall\Member(get_post()->post_author);
            $this->is_member_page = true;
            $this->post = $this->member->post;
            $this->ID = $this->member->post->ID;
            if ($this->member->ID == get_current_user_id()) {
                $this->is_my_page = true;
            }
            //$this->display();


        }


    }

    public function is_member_page()
    {
        return $this->is_member_page;
    }

    public function is_my_page()
    {
        return $this->is_my_page;
    }

    function the_matrixId()
    {

        if (is_singular('member')) {

            $matrix_id = get_field('matrixid', 'user_' . $this->member->ID);
            if ($matrix_id) {
                $base_rpi = 'https://' . $this->matrix_home . '/#/user/';
                $base_app = 'https://matrix.to/#/';
                ?>
                <details class="user-matrixId" style="margin-left: 100px;margin-top: -10px;">
                    <summary style="cursor:pointer"><strong><?php echo \rpi\Wall\Shortcodes::$element_icon ?> Kontakt
                            via Matrix: <?php echo $matrix_id; ?></strong></summary>
                    <br>
                    <a class="button button-primary"
                       href="<?php echo KONTO_SERVER ?>?action=mredirect&url=<?php echo $base_rpi . $matrix_id; ?>"
                       target="_blank">im
                        Browser matrix.rpi-virtuell.de</a>
                    <a class="button button-secondary" href="<?php echo $base_app . $matrix_id; ?>" target="_blank">über
                        die Element App</a>

                    <br>
                    <br>
                    <em>Für sichere Kommunikation nutzen wir <b><a
                                    href="https://element.io/personal">Element</a></b>,<br/> den Messenger für die
                        Matrix mit vielen Features für professionelle Lerngemeinschaften.</em>

                </details>
                <?php

            }

        }


    }

    public function bio()
    {
        $tags = '<div class="member-tags">
            <div class="cats"> 
                <!--[my_tags content="badge"]-->
                [my_tags content="schooltype"]
                [my_tags content="profession"]
            </div>
            <div class="tags">
            [my_tags content="wall-tag"]
            [my_tags content="wall-cat"]
            </div>
         </div>';
        $user = get_userdata($_POST['user_ID']);

        $description = '<h4>Über Mich</h4>';
        if (!empty($user->user_description)) {
            $description .= $user->user_description;
        } else {
            $description .= get_option('options_rpi_label_member_profile_textfields_group_member_bio_default_text');
        }
        echo do_shortcode(nl2br($description) . $tags);
        die();
    }

    public function profile()
    {

//		$member = new \rpi\Wall\Member($_POST['user_ID']);
//		echo $this->get_profile($member->post->ID);
        die();

    }

    public function groups()
    {

        $out = '';
        $args = [
            'paged' => isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1,
            'posts_per_page' => $this->posts_per_page
        ];
        $member = new \rpi\Wall\Member($_POST['user_ID']);
        $query = $member->get_query_all_groups($args);
        if ($query && $query->have_posts()) {
            $out .= '<div class="group-posts">';

            while ($query->have_posts()) {
                ob_start();
                \rpi\Wall\Shortcodes::display_post($query->the_post());
                $out .= ob_get_clean();
            }
            $out .= '</div>';

        } else {
            $out .= '<div>';
            $out .= 'ist noch kein Mitglied einer Gruppe';
            $out .= '</div>';
        }
        if ($query->max_num_pages > 1) {
            $out .= paginate_links(array(
                'format' => '?paged=%#%',
                'current' => max(1, $_REQUEST['paged']),
                'total' => $query->max_num_pages
            ));

        }
        wp_reset_query();
        return $out;
    }

    public function created()
    {

        $out = '';

        $args = [
            'paged' => isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1,
            'posts_per_page' => $this->posts_per_page
        ];

        $member = new \rpi\Wall\Member($_POST['user_ID']);
        $query = $member->get_query_created_pins($args);

        if ($query && $query->have_posts()) {
            $out .= '<div class="group-posts">';

            while ($query->have_posts()) {
                ob_start();
                \rpi\Wall\Shortcodes::display_post($query->the_post());
                $out .= ob_get_clean();
            }
            $out .= '</div>';

        } else {
            $out .= '<div>';
            $out .= 'Du hast noch keine Gruppe erstellt';
            $out .= '</div>';
        }
        if ($query->max_num_pages > 1) {
            $out .= paginate_links(array(
                'format' => '?paged=%#%',
                'current' => max(1, $_REQUEST['paged']),
                'total' => $query->max_num_pages
            ));

        }
        wp_reset_query();
        return $out;
    }

    public function comments()
    {

        $out = '';

        $args = [
            'paged' => isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1,
            'posts_per_page' => $this->posts_per_page
        ];


        ob_start();

        $member = new \rpi\Wall\Member($_POST['user_ID']);
        $comments = $member->get_my_comments_query($args);
        if ($comments) {
            foreach ($comments as $comment) {
                ?>

                <div class="member-coment">
                    <?php echo $member->display(24); ?>
                    <div class="entry-title">
                        <?php echo $comment->comment; ?>
                    </div>
                    <div class="entry-content">
                        <?php echo $comment->comment_content; ?>
                    </div>
                    <div class="entry-post-permalink">
                        <div class="pin-icon"><?php echo \rpi\Wall\Shortcodes::$pin_icon; ?></div>
                        <a href="<?php echo get_comment_link($comment); ?>"><?php echo $comment->post->post_title; ?></a>
                    </div>

                </div>
                <?php

            }
        } else {
            ?>
            <div>
                hat noch keine Kommentare auf der Pinnwand geschrieben
            </div>
            <?php
        }
        $out .= ob_get_clean();

//            if ($comments->max_num_pages > 1) {
//                $out .= paginate_links(array(
//                    'format' => '?paged=%#%',
//                    'current' => max(1, $_REQUEST['paged']),
//                    'total' => $comments->max_num_pages
//                ));
//            }
        return $out;

    }

    public function display()
    {

        if (is_singular('member')) {
            $tabs = new \rpi\Wall\Tabs('tabset');

            $default_permission = '';
            $private = get_post_meta(get_the_ID(), 'hideme', true);
            if (!empty($private)) {
                $default_permission = 'self';
                if (get_current_user_id() != $this->member->ID) {
                    echo '<p>Dieses Profil ist Privat</p>';
                }
            }

            $tabs->addTab(['label' => 'Über mich', 'name' => 'bio', 'content' => '<div id ="rpi_tab_bio_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$user_icon, 'permission' => $default_permission, 'checked' => true]);
            $tabs->addTab(['label' => 'Beiträge', 'name' => 'created', 'content' => '<div id ="rpi_tab_created_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$pin_icon, 'permission' => $default_permission]);
            $tabs->addTab(['label' => 'Kommentare', 'name' => 'comments', 'content' => '<div id ="rpi_tab_comments_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$comment_icon, 'permission' => $default_permission]);
            $tabs->addTab(['label' => 'Gruppen', 'name' => 'groups', 'content' => '<div id ="rpi_tab_groups_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$group_icon, 'permission' => $default_permission]);
            $tabs->addTab(['label' => 'Benachrichtigungen', 'name' => 'messages', 'content' => '<div id="rpi_tab_messages_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$mail_icon, 'permission' => 'self']);
            $tabs->addTab(['label' => 'Einstellungen', 'name' => 'profile', 'content' => $this->get_profile(get_the_ID()) . '<div id="rpi_tab_profile_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$gear_icon, 'permission' => 'self']);
            $tabs->addTab(['label' => 'Abmelden', 'name' => 'logout', 'content' => '', 'icon' => \rpi\Wall\Shortcodes::$logout_icon, 'permission' => 'self']);

            echo '<script>var rpi_wall ={user_ID: "' . $this->member->ID . '"};</script>';
            echo '<script> rpi_wall.allowedtabs = ' . json_encode($tabs->get_allowed_tabs()) . ';</script>';

            $tabs->display();
        }

    }

    public function get_profile($post_id)
    {


        $_GET['member_post'] = $post_id;
        set_query_var('member_post', $post_id);


        $settings = '<div class="profile-panel">
                        <div>
                            <div class="image-upload">
                                [basic-user-avatars]
                            </div>
                            <div class="tags-selector">
                                <strong>Darstellung auf der Netzwerkübersicht</strong>
                                [acfe_form name="member-taxonomy"]
                            </div>
                        </div>
                        <div>
                            [acfe_form name="user-profile"]
                            <div><br>&nbsp;<!-- Empty Spacer --><hr><br></div>
                        </div>
                            <div><!-- Empty Spacer --></div>
                        <div>
                        <strong>Welche Emails sollen zu dir gesendet werden?</strong>
                            [acfe_form name="user_email_settings"]
                        </div>
                    </div>';

        return do_shortcode($settings);

    }

    public function messages()
    {
        $user = new \rpi\Wall\Member();

        $paged = $_POST['paged'];

        if ($read_messages = get_user_meta($user->ID, 'rpi_read_messages', true)) {
            $read_messages = unserialize($read_messages);
        } else {
            $read_messages = array();
        }

        $args = [
            'post_type' => 'message',
            'posts_per_page' => $this->messages_per_page,
            'paged' => $paged,
            'meta_query' => [
                [
                    'key' => 'rpi_wall_message_recipient',
                    'value' => $user->ID,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ]
            ]
        ];
        if ($_POST['filter'] == 'unread') {
            $args['post__not_in'] = array_keys($read_messages);
        }
        $wp_query = new \WP_Query($args);
        $messages = $wp_query->get_posts();

        ob_start();

        ?>
        <div class="member-message-button-bar">
            <a class="button <?php echo $_POST['filter'] != 'unread' ? 'message-button-active' : '' ?>"
               href="#messages">Alle</a>
            <a class="button <?php echo $_POST['filter'] == 'unread' ? 'message-button-active' : '' ?> "
               href="#messages_unread">Ungelesen</a>
            <div class="member-message-button-bar-right">
                <div id="member-delete-all-read-button" class="button"
                     title="Alle gelesenen Nachrichten löschen"><?php echo \rpi\Wall\Shortcodes::$bulk_delete_icon ?></div>
                <div id="member-mark-all-read-button" class="button"
                     title="Alle Nachrichten als gelesen markieren"> <?php echo \rpi\Wall\Shortcodes::$mail_read_icon ?></div>
            </div>
        </div>
        <?php
        if ($messages) {
            ?>

            <div id="member-message-button" class="button hidden" style="font-weight: bold">Zurück</div>
            <div class="member-message-grid message-list">
                <div class="member-message-list">
                    <?php
                    foreach ($messages as $post):
                        setup_postdata($post);

                        ?>
                        <div class="message-entry" id="message-<?php echo $post->ID ?>">
                            <div class="entry-title <?php echo $read_messages[$post->ID] ? '' : 'unread' ?>">
                                <?php echo date('d.n.Y', strtotime($post->post_date)); ?>
                                : <?php echo $post->post_title; ?>
                            </div>
                        </div>
                    <?php
                    endforeach;
                    ?>
                </div>
                <div class="member-message-detail">
                    <div id="member-message-detail-options">
                        <div id="member-message-detail-delete" title="Diese Nachricht löschen"
                             class="button"><?php echo \rpi\Wall\Shortcodes::$delete_icon ?> </div>
                    </div>
                    <div id="member-message-detail-title"></div>
                    <div id="member-message-detail-content"></div>
                </div>
            </div>
            <?php
        } else {
            ?>
            <br>
            <div>
                Du hast noch keine ungelesenen Benachrichtigungen erhalten
            </div>
            <?php
        }


        echo '<hr>';
        if ($wp_query->max_num_pages > 1) {
            echo paginate_links(array(
                'format' => '?paged=%#%',
                'current' => max(1, $_POST['paged']),
                'total' => $wp_query->max_num_pages
            ));

        }
        wp_reset_postdata();
        return ob_get_clean();
    }


}
