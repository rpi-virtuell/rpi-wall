<?php

namespace rpi\Wall;

class Shortcodes
{

    static $user_icon = '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M7.35,18.5C8.66,17.56,10.26,17,12,17 s3.34,0.56,4.65,1.5C15.34,19.44,13.74,20,12,20S8.66,19.44,7.35,18.5z M18.14,17.12L18.14,17.12C16.45,15.8,14.32,15,12,15 s-4.45,0.8-6.14,2.12l0,0C4.7,15.73,4,13.95,4,12c0-4.42,3.58-8,8-8s8,3.58,8,8C20,13.95,19.3,15.73,18.14,17.12z"/><path d="M12,6c-1.93,0-3.5,1.57-3.5,3.5S10.07,13,12,13s3.5-1.57,3.5-3.5S13.93,6,12,6z M12,11c-0.83,0-1.5-0.67-1.5-1.5 S11.17,8,12,8s1.5,0.67,1.5,1.5S12.83,11,12,11z"/></g></g></svg>';
    static $date_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zm-7 5h5v5h-5z"/></svg>';
    static $group_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M6.32,13.01c0.96,0.02,1.85,0.5,2.45,1.34C9.5,15.38,10.71,16,12,16c1.29,0,2.5-0.62,3.23-1.66 c0.6-0.84,1.49-1.32,2.45-1.34C16.96,11.78,14.08,11,12,11C9.93,11,7.04,11.78,6.32,13.01z"/><path d="M4,13L4,13c1.66,0,3-1.34,3-3c0-1.66-1.34-3-3-3s-3,1.34-3,3C1,11.66,2.34,13,4,13z"/><path d="M20,13L20,13c1.66,0,3-1.34,3-3c0-1.66-1.34-3-3-3s-3,1.34-3,3C17,11.66,18.34,13,20,13z"/><path d="M12,10c1.66,0,3-1.34,3-3c0-1.66-1.34-3-3-3S9,5.34,9,7C9,8.66,10.34,10,12,10z"/><path d="M21,14h-3.27c-0.77,0-1.35,0.45-1.68,0.92C16.01,14.98,14.69,17,12,17c-1.43,0-3.03-0.64-4.05-2.08 C7.56,14.37,6.95,14,6.27,14H3c-1.1,0-2,0.9-2,2v4h7v-2.26c1.15,0.8,2.54,1.26,4,1.26s2.85-0.46,4-1.26V20h7v-4 C23,14.9,22.1,14,21,14z"/></g></g></svg>';
    static $group_add_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/><rect fill="none" height="24" width="24"/></g><g><g><polygon points="22,9 22,7 20,7 20,9 18,9 18,11 20,11 20,13 22,13 22,11 24,11 24,9"/><path d="M8,12c2.21,0,4-1.79,4-4s-1.79-4-4-4S4,5.79,4,8S5.79,12,8,12z M8,6c1.1,0,2,0.9,2,2s-0.9,2-2,2S6,9.1,6,8S6.9,6,8,6z"/><path d="M8,13c-2.67,0-8,1.34-8,4v3h16v-3C16,14.34,10.67,13,8,13z M14,18H2v-0.99C2.2,16.29,5.3,15,8,15s5.8,1.29,6,2V18z"/><path d="M12.51,4.05C13.43,5.11,14,6.49,14,8s-0.57,2.89-1.49,3.95C14.47,11.7,16,10.04,16,8S14.47,4.3,12.51,4.05z"/><path d="M16.53,13.83C17.42,14.66,18,15.7,18,17v3h2v-3C20,15.55,18.41,14.49,16.53,13.83z"/></g></g></svg>';
    static $group_sub_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><rect fill="none" height="24" width="24"/><path d="M24,9v2h-6V9H24z M8,4C5.79,4,4,5.79,4,8s1.79,4,4,4s4-1.79,4-4S10.21,4,8,4z M8,10c-1.1,0-2-0.9-2-2s0.9-2,2-2s2,0.9,2,2 S9.1,10,8,10z M8,13c-2.67,0-8,1.34-8,4v3h16v-3C16,14.34,10.67,13,8,13z M14,18H2v-0.99C2.2,16.29,5.3,15,8,15s5.8,1.29,6,2V18z M12.51,4.05C13.43,5.11,14,6.49,14,8s-0.57,2.89-1.49,3.95C14.47,11.7,16,10.04,16,8S14.47,4.3,12.51,4.05z M16.53,13.83 C17.42,14.66,18,15.7,18,17v3h2v-3C20,15.55,18.41,14.49,16.53,13.83z"/></svg>';
    static $tag_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM13 20.01L4 11V4h7v-.01l9 9-7 7.02z"/><circle cx="6.5" cy="6.5" r="1.5"/></svg>';
    static $tag2_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16zM16 17H5V7h11l3.55 5L16 17z"/></svg>';
    static $taxonomy_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5v-3h3.56c.69 1.19 1.97 2 3.45 2s2.75-.81 3.45-2H19v3zm0-5h-4.99c0 1.1-.9 2-2 2s-2-.9-2-2H5V5h14v9z"/></svg>';
    static $folder_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M9.17 6l2 2H20v10H4V6h5.17M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
    static $like_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/><rect fill="none" height="24" width="24"/></g><g><g><polygon points="22,9 22,7 20,7 20,9 18,9 18,11 20,11 20,13 22,13 22,11 24,11 24,9"/><path d="M8,12c2.21,0,4-1.79,4-4s-1.79-4-4-4S4,5.79,4,8S5.79,12,8,12z M8,6c1.1,0,2,0.9,2,2s-0.9,2-2,2S6,9.1,6,8S6.9,6,8,6z"/><path d="M8,13c-2.67,0-8,1.34-8,4v3h16v-3C16,14.34,10.67,13,8,13z M14,18H2v-0.99C2.2,16.29,5.3,15,8,15s5.8,1.29,6,2V18z"/><path d="M12.51,4.05C13.43,5.11,14,6.49,14,8s-0.57,2.89-1.49,3.95C14.47,11.7,16,10.04,16,8S14.47,4.3,12.51,4.05z"/><path d="M16.53,13.83C17.42,14.66,18,15.7,18,17v3h2v-3C20,15.55,18.41,14.49,16.53,13.83z"/></g></g></svg>';
    static $watch_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M12 6c3.79 0 7.17 2.13 8.82 5.5C19.17 14.87 15.79 17 12 17s-7.17-2.13-8.82-5.5C4.83 8.13 8.21 6 12 6m0-2C7 4 2.73 7.11 1 11.5 2.73 15.89 7 19 12 19s9.27-3.11 11-7.5C21.27 7.11 17 4 12 4zm0 5c1.38 0 2.5 1.12 2.5 2.5S13.38 14 12 14s-2.5-1.12-2.5-2.5S10.62 9 12 9m0-2c-2.48 0-4.5 2.02-4.5 4.5S9.52 16 12 16s4.5-2.02 4.5-4.5S14.48 7 12 7z"/></svg>';
    static $unwatch_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0zm0 0h24v24H0V0zm0 0h24v24H0V0zm0 0h24v24H0V0z" fill="none"/><path d="M12 6c3.79 0 7.17 2.13 8.82 5.5-.59 1.22-1.42 2.27-2.41 3.12l1.41 1.41c1.39-1.23 2.49-2.77 3.18-4.53C21.27 7.11 17 4 12 4c-1.27 0-2.49.2-3.64.57l1.65 1.65C10.66 6.09 11.32 6 12 6zm-1.07 1.14L13 9.21c.57.25 1.03.71 1.28 1.28l2.07 2.07c.08-.34.14-.7.14-1.07C16.5 9.01 14.48 7 12 7c-.37 0-.72.05-1.07.14zM2.01 3.87l2.68 2.68C3.06 7.83 1.77 9.53 1 11.5 2.73 15.89 7 19 12 19c1.52 0 2.98-.29 4.32-.82l3.42 3.42 1.41-1.41L3.42 2.45 2.01 3.87zm7.5 7.5l2.61 2.61c-.04.01-.08.02-.12.02-1.38 0-2.5-1.12-2.5-2.5 0-.05.01-.08.01-.13zm-3.4-3.4l1.75 1.75c-.23.55-.36 1.15-.36 1.78 0 2.48 2.02 4.5 4.5 4.5.63 0 1.23-.13 1.77-.36l.98.98c-.88.24-1.8.38-2.75.38-3.79 0-7.17-2.13-8.82-5.5.7-1.43 1.72-2.61 2.93-3.53z"/></svg>';
    static $mail_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"/></svg>';
    static $pin_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><path d="M14,4v5c0,1.12,0.37,2.16,1,3H9c0.65-0.86,1-1.9,1-3V4H14 M17,2H7C6.45,2,6,2.45,6,3c0,0.55,0.45,1,1,1c0,0,0,0,0,0l1,0v5 c0,1.66-1.34,3-3,3v2h5.97v7l1,1l1-1v-7H19v-2c0,0,0,0,0,0c-1.66,0-3-1.34-3-3V4l1,0c0,0,0,0,0,0c0.55,0,1-0.45,1-1 C18,2.45,17.55,2,17,2L17,2z"/></g></svg>';
    static $gear_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19.43 12.98c.04-.32.07-.64.07-.98 0-.34-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.09-.16-.26-.25-.44-.25-.06 0-.12.01-.17.03l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.06-.02-.12-.03-.18-.03-.17 0-.34.09-.43.25l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98 0 .33.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.09.16.26.25.44.25.06 0 .12-.01.17-.03l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.06.02.12.03.18.03.17 0 .34-.09.43-.25l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zm-1.98-1.71c.04.31.05.52.05.73 0 .21-.02.43-.05.73l-.14 1.13.89.7 1.08.84-.7 1.21-1.27-.51-1.04-.42-.9.68c-.43.32-.84.56-1.25.73l-1.06.43-.16 1.13-.2 1.35h-1.4l-.19-1.35-.16-1.13-1.06-.43c-.43-.18-.83-.41-1.23-.71l-.91-.7-1.06.43-1.27.51-.7-1.21 1.08-.84.89-.7-.14-1.13c-.03-.31-.05-.54-.05-.74s.02-.43.05-.73l.14-1.13-.89-.7-1.08-.84.7-1.21 1.27.51 1.04.42.9-.68c.43-.32.84-.56 1.25-.73l1.06-.43.16-1.13.2-1.35h1.39l.19 1.35.16 1.13 1.06.43c.43.18.83.41 1.23.71l.91.7 1.06-.43 1.27-.51.7 1.21-1.07.85-.89.7.14 1.13zM12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>';
    static $comment_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM20 4v13.17L18.83 16H4V4h16zM6 12h12v2H6zm0-3h12v2H6zm0-3h12v2H6z"/></svg>';


    public $user;
    public $is_member_page = false;

    public function __construct()
    {

        //add_shortcode( 'user_pinned_posts', [$this,'get_users_pinwall_posts'] );
        add_shortcode('my_tags', array($this, 'get_user_profile_tags'));
        add_shortcode('my_messages', array($this, 'get_user_messages'));
        add_shortcode('my_groups', array($this, 'get_user_groups'));
        add_shortcode('my_likes', array($this, 'get_user_likes'));
        add_shortcode('my_posts', [$this, 'get_user_posts']);
        add_shortcode('my_comments', array($this, 'get_user_comments'));

        add_action('wp_head', array($this, 'init'));

    }

    public function init()
    {

        if ('member' === get_post_type()) {
            $this->user = get_userdata(get_post()->post_author);
            $this->is_member_page = true;
        }
        if (!$this->user->ID && is_user_logged_in()) {
            $this->user = wp_get_current_user();
        }


        ?>
        <script>
            const wallIcons = {
                group: <?php echo json_encode(self::$group_icon);?>,
                group_add: <?php echo json_encode(self::$group_add_icon);?>,
                group_sub: <?php echo json_encode(self::$group_sub_icon);?>,
                user: <?php echo json_encode(self::$user_icon);?>,
                pin: <?php echo json_encode(self::$pin_icon);?>,
                tag2: <?php echo json_encode(self::$tag2_icon);?>,
                tag: <?php echo json_encode(self::$tag_icon);?>,
                tax: <?php echo json_encode(self::$taxonomy_icon);?>,
                like: <?php echo json_encode(self::$like_icon);?>,
                folder: <?php echo json_encode(self::$folder_icon);?>,
                watch: <?php echo json_encode(self::$watch_icon);?>,
                unwatch: <?php echo json_encode(self::$unwatch_icon);?>,
                mail: <?php echo json_encode(self::$mail_icon);?>,
                gear: <?php echo json_encode(self::$gear_icon);?>,
                comment: <?php echo json_encode(self::$comment_icon);?>,
            }
        </script>
        <?php

    }

    public function is_member_page()
    {
        return $this->is_member_page;
    }


    /**
     * [my_comments]
     * echo self::get_user_comments();
     *
     * @param $atts
     *
     * @return false|string
     */
    public function get_user_comments($atts)
    {

        ob_start();
        $member = new Member($this->user->ID);
        foreach ($member->get_my_comments_query() as $comment) {
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
                    <div class="pin-icon"><?php echo self::$pin_icon; ?></div>
                    <a href="<?php echo get_comment_link($comment); ?>"><?php echo $comment->post->post_title; ?></a>
                </div>

            </div>
            <?php

        }
        return ob_get_clean();

    }

    public function get_user_profile_tags($atts)
    {
        $out = '';

        if (isset($atts['content'])) {
            $member = new Member($_POST['user_ID']);


            if (taxonomy_exists(trim($atts['content']))) {


                $terms = wp_get_post_terms($member->post->ID, $atts['content']);
                if($terms && count($terms)>0){
                    $tax = get_taxonomy($atts['content']);
                    $out .= '<h4>'.$tax->label.'</h4>';
                }
                $out .= '<ul class="rpi-wall-term-' . $atts['content'] . '">';
                foreach ($terms as $term) {
                    if (is_a($term, 'WP_Term')) {
                        $out .= '<li><a href="' . site_url() . '/' . $atts['content'] . '/' . $term->slug . '">' . $term->name . '</a></li>';

                    }
                }
                $out .= '</ul>';


            }

        }

        return $out;
    }

    public function get_user_messages($atts)
    {

        $user = new Member();

        $paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;

        $args = [
            'post_type' => 'message',
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'rpi_wall_message_recipient',
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
            foreach ($messages as $post):
                setup_postdata($post);
                ?>
                <div class="message">
                    <details class="message-content">
                        <summary class="entry-title">
                            <?php echo date('d.n.Y', strtotime($post->post_date)); ?>: <?php echo $post->post_title; ?>
                        </summary>
                        <?php echo $post->post_content; ?>
                    </details>
                </div>
            <?php
            endforeach;
            ?>
        </div>
        <?php

        echo '<hr>';
        echo paginate_links(array(
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $wp_query->max_num_pages
        ));
        wp_reset_postdata();
        return ob_get_clean();
    }


    public function get_user_groups($atts)
    {
        ob_start();

        echo '<div class="group-posts">';
        $member = new Member($this->user);


        $query = $member->get_query_all_groups();
        if ($query && $query->have_posts()) {
            while ($query->have_posts()) {
                self::display_post($query->the_post());
            }
        }
        wp_reset_query();
        echo '</div>';
        return ob_get_clean();
    }

    public function get_user_likes($atts)
    {
        ob_start();
        echo '<div class="group-posts">';
        $member = new Member($this->user);

        $query = $member->get_query_pending_groups();
        if ($query && $query->have_posts()) {
            while ($query->have_posts()) {
                self::display_post($query->the_post());
            }
        }
        wp_reset_query();
        echo '</div>';
        return ob_get_clean();
    }

    public function get_user_posts($atts)
    {

        ob_start();
        echo '<div class="group-posts">';
        $member = new Member($this->user);

        $query = $member->get_query_my_posts();
        if ($query && $query->have_posts()) {
            while ($query->have_posts()) {
                self::display_post($query->the_post());
            }
        }
        wp_reset_query();
        echo '</div>';
        return ob_get_clean();
    }


    static function display_user($user_id, $size)
    {
        $member = new Member($user_id);

        ?>
        <div class="user-grid">
            <div class="user-avatar"><?php echo get_avatar($member->ID, $size); ?></div>
            <div class="user-name">
                <a href="<?php echo $member->get_member_profile_permalink() ?>"><?php echo $member->name; ?></a>
            </div>
        </div>
        <?php
    }

    static function display_user_name($user_id)
    {
        $member = new Member($user_id);
        ?>
        <span class="user-name"><a
                    <a href="<?php echo $member->get_member_profile_permalink() ?>"><?php echo $member->name; ?></a>
        <?php
    }

    static function display_assignd_user(Group $group, $size = 24)
    {
        $u = $group->get_liker_and_member_Ids();
        ?>
        <div class="user-assignd">
            <div class="user-members">
                <?php
                foreach ($u->members as $user_id) {
                    self::display_user($user_id, $size);
                }
                ?>
            </div>
            <div class="user-likers">
                <?php
                foreach ($u->likers as $user_id) {
                    self::display_user($user_id, $size);
                }
                ?>
            </div>

        </div>
        <?php
    }

    static function display_members(Group $group)
    {
        if ($group->get_members_amount() > 0) {
            foreach ($group->get_memberIds() as $user_id) {
                self::display_user($user_id);
            }
        }
    }

    static function display_post($post)
    {
        global $post;

        $plg = new Group($post->ID);
        $plg->get_comment_likes_amount();

        ?>
        <div class="group-post">
            <div class="group-post-wrapper">
                <div class="entry-title">
                    <h3><?php echo $post->post_title; ?></h3>
                </div>
                <div class="entry-meta"><?php echo self::$user_icon; ?>
                    <?php echo self::display_user_name($post->post_author); ?>
                    <?php echo self::$date_icon; ?><?php echo date('d.m.Y', strtotime($post->post_date)); ?>
                </div>
                <div class="content">
                    <?php echo wp_trim_words($post->post_content, 50, '...'); ?>
                </div>
                <div class="ghost"></div>
                <div>
                    <a href="<?php the_permalink() ?>">Pinwandeintrag</a>
                    <?php if (is_user_logged_in() && 'pending' !== $plg->get_status() && $plg->has_member(get_current_user_id())):
                        ?>| Matrix Raum: <?php echo $plg->get_matrix_link(); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <?php $mn = $plg->get_members_amount(); ?><?php if ($mn > 0) echo $mn . ' Mitglied'; ?><?php if ($mn > 1) echo 'er'; ?>
                    <?php if ($plg->is_not_founded()): ?>
                        <?php
                        $in = $plg->get_likers_amount();
                        if ($in > 0) {
                            if ($in < 2) {
                                echo '1 Person interessiert';
                            } else {
                                echo $in . ' Personen interessiert';
                            }
                        }
                        echo $plg->is_pending() ? ', Status: Gr??ndungsphase' : ''; ?>
                    <?php endif; ?>
                </div>
                <?php self::display_assignd_user($plg, 24); ?>

            </div>
        </div>
        <?php
    }

}
