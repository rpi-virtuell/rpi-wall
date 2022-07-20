<?php

namespace rpi\Wall;


use RpiWall;

class Toolbar
{

    public function __construct()
    {
    }

    static function display_toolbar()
    {
        if (isset($_GET['widgetId']) && get_post_type() == "wall") {
            $group = new Group(get_the_ID());
            $split = explode('_', $_GET['widgetId']);
            if ($group->get_matrix_room_id() === $split[0]) {
                ?>
                <div class="group-toolbar">
                    <div class="group-toolbar-grip">
                        <?php
                        $status = $group->get_toolbar_status();
                        var_dump($status);
                        switch ($status) {
                            case 'constituted':
                                RpiWall::modal('edit-planningForm', 'Planungsbogen ', do_shortcode('[acfe_form name="edit-constitution"]'));
                                RpiWall::modal('protocolForm', 'Arbeits-Struktur-Bogen', do_shortcode('[acfe_form name="protocol"]'));
                                break;
                            default:
                                RpiWall::modal('planningForm', 'Planungsbogen ', do_shortcode('[acfe_form name="constitution"]'));
                                break;
                        }
                        $buttons = $group->get_toolbar_buttons();
                        foreach ($buttons as $button) {
                            ?>
                            <a class="button toolbar-button" href="<?php echo $button['link'] ?>">
                                <?php echo $button['label'] ?>
                            </a>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="group-toolbar-control-panel">
                        <a class="button toolbar-button" href=" ">
                            <?php echo $button['label'] ?>
                            <span class="dashicons dashicons-admin-tools"></span>
                        </a>
                        <div class="button-primary toolbar-edit"></div>
                        <div class="button-primary toolbar-confirm"><span class="dashicons dashicons-saved"></span>
                        </div>
                        <div class="button-primary toolbar-undo"><span class="dashicons dashicons-undo"></span></div>
                    </div>
                </div>
                <?php
                echo ob_get_clean();
                echo '</body></html>';
                wp_footer();
                die();
            }
        }
    }

    static function update_toolbar_status($form, $post_id)
    {
        $group = new Group($post_id);
        $group->set_toolbar_status('constituted');
    }

    static function edit_content($content)
    {
        if (get_post_type() == "protokoll") {

        }
    }

}