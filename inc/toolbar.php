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
        if (!empty($_GET['widgetId']) && get_post_type() == "wall") {
            $group = new Group(get_the_ID());
            $split = explode('_', $_GET['widgetId']);
            var_dump($split);
            if ($group->get_matrix_room_id() === $split[0]) {
                ob_start();
                if (!empty($_GET['template'])) {
                    switch ($_GET['template']) {
                        case 'planning':

                            // TODO: ADD filter ?export into protocol?
                            add_filter('the_content', ['rpi\Wall\Toolbar', 'edit_content']);
                            RpiWall::modal('planningForm', 'Planungsbogen ', do_shortcode('[acfe_form name="constitution"]'));
                            echo ob_get_clean();
                            echo '</body></html>';
                            wp_footer();
                            die();

                        case 'protocol':
                            RpiWall::modal('protocolForm', 'Arbeits-Struktur-Bogen', do_shortcode('[acfe_form name="protocol"]'));
                            echo ob_get_clean();
                            echo '</body></html>';
                            wp_footer();
                            die();
                    }
                }
                ?>
                <div class="group-toolbar">
                    <div class="group-toolbar-grip">
                        <?php
                        $status = "";
                        $status = $group->get_toolbar_status();
                        var_dump($status);
                        switch ($status) {
                            case 'constituted':
                                ?>
                                <a class="button toolbar-button"
                                   href="<?php echo get_permalink() . '?widgetId=' . $_GET['widgetId'] . '&template=' . 'protocol' ?>">
                                    <?php echo 'Arbeits-Struktur-Bogen' ?>
                                </a>
                                <?php
                                break;
                            default:
                                ?>
                                <a class="button toolbar-button"
                                   href="<?php echo get_permalink() . '?widgetId=' . $_GET['widgetId'] . '&template=' . 'planning' ?>">
                                    <?php echo 'Planungsformular' ?>
                                </a>
                                <?php
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
                        <a class="button toolbar-button" href="<?php echo $button['link'] ?>">
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

    static function edit_content($content){
        if (get_post_type() == "protokoll")
        {

        }
    }

    static function display_button($group, $label, $link, $attach_hash = false)
    {

        if ($attach_hash) {
            ?>   <a class="button toolbar-button" href="<?php echo $link ?>">   <?php
        } else {
            ?>   <a class="button toolbar-button" href="<?php echo $link ?>"> <?php
        }

        ?>
        <?php echo $label ?>
        </a>
        <?php
    }

}