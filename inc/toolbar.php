<?php

namespace rpi\Wall;


use core_reportbuilder\local\filters\date;
use RpiWall;

class Toolbar
{

    public function __construct()
    {

    }

    static function add_toolbar_class_to_body($classes)
    {
        if (isset($_GET['widgetId'])) {
            $classes[] = 'toolbar';
        }

        return $classes;
    }

    static function display_toolbar(Group $group, bool $widget)
    {
            $next_meeting = get_post_meta($group->ID, 'date_of_meeting', true);
            ?>
            <div class="group-toolbar">
                <div class="toolbar-header">

                    <?php if ($widget) { ?>
                        <h4><a href="<?php echo get_permalink() ?>" target="_blank"
                               rel="noopener noreferrer"><?php echo $group->title ?></a></h4>
                    <?php } ?>
                    <?php if (!empty($next_meeting)) { ?>
                        Nächster Termin: <?php echo date('D d.n.Y', strtotime($next_meeting)) ?>
                        um <?php echo date('H:i', strtotime($next_meeting)) ?> Uhr

                    <?php } ?>
                </div>

                <div class="toolbar-content">

                    <div class="group-toolbar-grip">
                        <?php
                        $status = $group->get_toolbar_status();
                        switch ($status) {
                            case 'constituted':
                                RpiWall::modal('edit-planningForm', 'Planungsbogen', do_shortcode('[acfe_form name="edit-constitution"]'));
                                RpiWall::modal('protocolForm', 'Arbeitsstrukturbogen', do_shortcode('[acfe_form name="create-protocol"]'));
                                break;
                            case 'meeting_planned':
                                RpiWall::modal('planningForm', 'Planungsbogen', do_shortcode('[acfe_form name="constitution"]'));
                                break;
                            case 'closed':
                                RpiWall::modal('edit-planningForm', 'Planungsbogen', do_shortcode('[acfe_form name="edit-constitution"]'));

                                break;
                            default:
                                RpiWall::modal('planningDate', 'Planungstermin setzen ', do_shortcode('[acfe_form name="constitution_date"]'));
                                if (!$group->get_toolbar_buttons())
                                {
                                    $buttons = array(array('rpi_wall_group_toolbar_button_label' => 'Terminfindung','rpi_wall_group_toolbar_button_url' => 'https://nuudel.digitalcourage.de/'));
                                }
                                break;
                        }
                        //TODO: terminfindung button via set_toolbar_buttons hinzufügen
                        if (!isset($buttons))
                        {
                            $buttons = $group->get_toolbar_buttons();
                        }
                        foreach ($buttons as $button) {
                            if (!empty($button['rpi_wall_group_toolbar_button_url']) || !empty($button['rpi_wall_group_toolbar_button_label']))
                            {
                                ?>
                                <div class="ct-container">
                                    <a class="button toolbar-button"
                                       href="<?php echo $button['rpi_wall_group_toolbar_button_url'] ?>" target="_blank"
                                       rel="noopener noreferrer">
                                        <?php echo $button['rpi_wall_group_toolbar_button_label'] ?>
                                    </a>
                                </div>
                                <?php
                            }
                        }
                        ?>

                    </div>
                    <div class="toolbar-settings">
                        <div class="toolbar-setting-buttons">
                            <div title="Weitere Buttons hinzufügen">
                                <?php RpiWall::modal('edit-buttons', '<span class="dashicons dashicons-plus"></span>', do_shortcode(' [acfe_form name="rpi_wall_group_toolbar_button_form"] ')); ?>
                            </div>
                            <a id="btn-open-faq" title="FAQ" href="<?php echo home_url('/faqs') ?>"><span>?</span></a>
                            <div title="PLG Schließen">
                                <?php RpiWall::modal('close-plg', '<span class="dashicons dashicons-exit"></span>', do_shortcode(' [acfe_form name="review"]')); ?>
                            </div>
                        </div>
                    </div>
                    <div class="toolbar-details">
                        <?php $group_goal = get_post_meta($group->ID, "constitution_zielformulierung", true);
                        if (!empty($group_goal)) {
                            ?>
                            <div class="toolbar-group-goal">
                                <h3>
                                    Ziel:
                                </h3>
                                <?php echo $group_goal ?>
                            </div>
                        <?php } ?>
                        <?php
                        $protocols = protocol::get_protocols($group->ID);
                        if (sizeof($protocols) > 0) {
                            ?>

                            <div class="toolbar-protocols">
                                <h3>
                                    Arbeitsstrukturbogen:
                                </h3>
                                <?php

                                foreach ($protocols as $protocol) {
                                    ?> <a href="<?php echo $protocol->guid ?>" target="_blank"
                                          rel="noopener noreferrer"><?php echo date('d.m.Y', strtotime($protocol->post_date)) ?> </a> <?php
                                }
                                ?>
                            </div>
                            <?php
                        } ?>
                    </div>
                </div>
            </div>
            <?php
        if ($widget)
        {
            echo ob_get_clean();
            echo '</body></html>';
            wp_footer();
            die();
        }


    }

    static function update_toolbar_status($form, $post_id, $status)
    {
        $group = new Group($post_id);
        $group->set_toolbar_status($status);
    }


}
