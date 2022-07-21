<?php

namespace rpi\Wall;


use RpiWall;

class Toolbar
{

    public function __construct()
    {

    }

    static function add_toolbar_class_to_body($classes){
	    if (isset($_GET['widgetId'])) {
		    $classes[] = 'toolbar';
	    }

        return $classes;
    }
    static function display_toolbar()
    {
        if (isset($_GET['widgetId']) && get_post_type() == "wall") {
            $group = new Group(get_the_ID());
            $split = explode('_', $_GET['widgetId']);
            if ($group->get_matrix_room_id() === $split[0]) {
                $next_meeting = get_post_meta($group->ID, 'date_of_meeting', true);
                ?>
                <div class="group-toolbar">
                    <?php if (!empty($next_meeting)) { ?>
                        <div class="toolbar-header">
                            <h4><a href="<?php echo get_permalink() ?>"><?php echo $group->title ?></a></h4>
                            Nächster Termin: <?php echo date('D d.n.Y', strtotime($next_meeting)) ?>
                            um <?php echo date('H:i', strtotime($next_meeting)) ?> Uhr
                        </div>
                    <?php } ?>
                    <div class="toolbar-content">
                        <div class="group-toolbar-grip">
                            <?php
                            $status = $group->get_toolbar_status();
                            switch ($status) {

                                case 'constituted':
                                    RpiWall::modal('edit-planningForm', 'Planungsbogen', do_shortcode('[acfe_form name="edit-constitution"]'));
                                    RpiWall::modal('protocolForm', 'Arbeits-Struktur-Bogen', do_shortcode('[acfe_form name="create-protocol"]'));
                                    break;
                                case 'meeting_planned':
                                    RpiWall::modal('planningForm', 'Planungsbogen', do_shortcode('[acfe_form name="constitution"]'));
                                    break;
                                default:
                                    RpiWall::modal('planningDate', 'Planungstermin setzen ', do_shortcode('[acfe_form name="constitution_date"]'));
                                    break;
                            }
                            $buttons = $group->get_toolbar_buttons();
                            foreach ($buttons as $button) {
                                ?>
                                <a class="button toolbar-button"
                                   href="<?php echo $button['rpi_wall_group_toolbar_button_url'] ?>" target="_blank"
                                   rel="noopener noreferrer">
                                    <?php echo $button['rpi_wall_group_toolbar_button_label'] ?>
                                </a>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="toolbar-edit-button">
                            <?php RpiWall::modal('edit-buttons', '<span class="dashicons dashicons-admin-tools"></span>', do_shortcode(' [acfe_form name="rpi_wall_group_toolbar_button_form"] ')); ?>
                        </div>
                        <div class="toolbar-details">
                            <div class="toolbar-protocols">
                                <h3>
                                    Protokolle:
                                </h3>
                                <?php

                                $protocols = protocol::get_protocols($group->ID);
                                foreach ($protocols as $protocol) {
                                    ?> <a href="<?php echo $protocol->guid ?>" target="_blank"
                                          rel="noopener noreferrer"><?php echo $protocol->post_date ?> </a> <?php
                                }
                                ?>
                            </div>
                            <div class="toolbar-group-goal">
                                <h3>
                                    Ziel:
                                </h3>
                                <?php echo get_post_meta($group->ID, "constitution_zielformulierung", true); ?>
                            </div>
                        </div>
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

    static function update_toolbar_status($form, $post_id, $status)
    {
        $group = new Group($post_id);
        $group->set_toolbar_status($status);
    }


}
