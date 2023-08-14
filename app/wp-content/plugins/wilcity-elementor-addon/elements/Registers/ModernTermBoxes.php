<?php

namespace WILCITY_ELEMENTOR\Registers;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use WILCITY_SC\SCHelpers;

class ModernTermBoxes extends Widget_Base
{
    use Helpers;

    public function get_name()
    {
        return apply_filters('wilcity/filter/id-prefix', 'wilcity-modern-term-boxes');
    }

    /**
     * Retrieve the widget title.
     *
     * @since  1.1.0
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return WILCITY_EL_PREFIX.'Modern Term Boxes';
    }

    /**
     * Retrieve the widget icon.
     *
     * @since  1.1.0
     *
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'fa fa-picture-o';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @since  1.1.0
     *
     * @access public
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['theme-elements'];
    }

    /**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.1.0
     *
     * @access protected
     */
    protected function register_controls()
    {
        /** @var \WilcityShortcodeRepository $wilcityScRepository */
        global $wilcityScRepository;
        //
        $this->convertKCToEl(
          $wilcityScRepository->get('wilcity_kc_modern_term_boxes:wilcity_kc_modern_term_boxes', true)->sub('params')
        )->registerShortcode()
        ;
    }

    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since  1.1.0
     *
     * @access protected
     */
    protected function render()
    {
        /** @var \WilcityShortcodeRepository $wilcityScAttsRepository */
        global $wilcityScAttsRepository;
        $aSettings = wp_parse_args(
          $this->get_settings(),
          $wilcityScAttsRepository->get($this->findScAttributesFileName(basename(__FILE__)))
        );

        wilcity_sc_render_modern_term_boxes($aSettings);
    }
}
