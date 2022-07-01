<?php

namespace rpi\Wall;

class Tabs
{

    protected $tabgroup = [];
    protected $contentgroup = [];
    protected $tabset;

    public function __construct($tabset = 'tabset')
    {
        $this->tabset = $tabset;
    }

    /**
     * @param string $label
     * @param string $name
     * @param string $content
     * @param string $checked 'checked'
     * @param string $permission '','loggedin', 'self'
     *
     * @return void
     */
    function addTab($args)
    {

        $props = wp_parse_args($args, [
            'label' => 'Titel',
            'name' => 'tab1',
            'content' => '',
            'checked' => false,
            'permission' => ''
        ]);
        $checked = $props['checked'] ? 'checked' : '';

        if (!empty($props['permission'])) {
            if ('loggedin' === $props['permission'] && !is_user_logged_in()) {
                return;
            } elseif ('self' === $props['permission'] && get_post()->post_author != get_current_user_id()) {
                return;
            } else {
                new \WP_Error('invalidParam', 'Permission Params: empty|self|loggedin');
            }
        }

        $this->tabgroup[] = $this->addLabel($props['label'], sanitize_title($props['name']), $checked);
        $this->contentgroup[] = $this->addContent($props['label'], sanitize_title($props['name']), $props['content']);

    }


    protected function addLabel($label, $name, $checked)
    {

        if ($_GET['tab'] == $name) {
            $checked = 'checked';
        }
        ob_start();

        ?>
        <!-- Tab 1 -->
        <input type="radio" name="<?php echo $this->tabset; ?>" id="tab-<?php echo $name; ?>"
               aria-controls="<?php echo $name; ?>" <?php echo $checked; ?>>
        <label for="tab-<?php echo $name; ?>">
            <a href="?tab=<?php echo $name; ?>">
                <?php echo $label; ?>
            </a>
        </label>

        <?php
        return ob_get_clean();

    }

    protected function addContent($label, $name, $content)
    {
        ob_start();
        ?>
        <section id="<?php echo $name; ?>" class="tab-panel">
            <h2><?php echo $label; ?></h2>
            <?php echo $content; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    public function display()
    {
        ob_start();
        ?>
        <div class="tabset">
            <?php
            foreach ($this->tabgroup as $tab) {
                echo $tab;
            }
            ?>
            <div class="tab-panels">
                <?php
                foreach ($this->contentgroup as $content) {
                    echo $content;
                }
                ?>
            </div>

        </div>
        <?php
        echo ob_get_clean();

    }
}
