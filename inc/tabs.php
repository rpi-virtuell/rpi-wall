<?php

namespace rpi\Wall;

class Tabs
{

    protected $tabgroup = [];
    protected $contentgroup = [];
    protected $tabset;
    protected $allowedtabs = [];

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
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16zM16 17H5V7h11l3.55 5L16 17z"/></svg>',
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

        $this->tabgroup[] = $this->addLabel($props['icon'], $props['label'], sanitize_title($props['name']), $checked);
        $this->contentgroup[] = $this->addContent($props['label'], sanitize_title($props['name']), $props['content']);
        $this->allowedtabs[] = $props['name'];

    }


    protected function addLabel($icon, $label, $name, $checked)
    {

        if ($_GET['tab'] == $name) {
            $checked = 'checked';
        }
        ob_start();

        ?>
        <input type="radio" name="<?php echo $this->tabset; ?>" id="tab-<?php echo $name; ?>" aria-controls="<?php echo $name; ?>" <?php echo $checked; ?>>
        <label for="tab-<?php echo $name; ?>">
            <a href="#<?php echo $name; ?>" class="tab-link">
                <div class="tab-icon"><?php echo $icon; ?></div>
                <div class="tab-label"><?php echo $label; ?></div>
            </a>
        </label>

        <?php
        return ob_get_clean();

    }

    public function get_allowed_tabs(){
        return $this->allowedtabs;
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
