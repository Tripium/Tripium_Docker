<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Framework\Helpers\WPML;

trait ListingToolsGeneralConfig
{
    public    $parentSlug = 'wiloke-listing-tools';
    protected $aUsedHeroSearchFields;
    protected $aAvailableHeroSearchFields;
    protected $aListingTypes;

    public function dequeueScripts()
    {
        if (!is_admin()) {
            return false;
        }

        $oScreen = get_current_screen();

        if (empty($oScreen)) {
            return false;
        }

        $screen = $oScreen->base;
        if (strpos($screen, 'wiloke-tools') !== false || strpos($screen, 'wiloke-submission') !== false) {
            wp_dequeue_script('epic-admin');
            wp_dequeue_script('bootstrap');
            wp_dequeue_script('selectize');
        }
    }

    private function mergeNewFieldToUsedSection($postType)
    {
        $updatedKey = 'is_merge_' . $postType . '_field';
        $latestUpdate = GetSettings::getOptions($updatedKey);

        if ($latestUpdate == WILOKE_LISTING_TOOL_VERSION) {
            return false;
        }

        if (empty($this->aUsedSections)) {
            return false;
        }

        foreach ($this->aUsedSections as $key => $aBlock) {
            if ($aBlock['type'] == 'group' || isset($aBlock['isCustomSection'])) {
                continue;
            }

            if (isset($this->aAllSections[$aBlock['key']])) {
                foreach ($this->aAllSections[$aBlock['key']]['fields'] as $aField) {
                    if (!isset($aBlock['fields'][$aField['key']])) {
                        $aNewField = [];
                        $aNewField['type'] = $aField['type'];
                        $aNewField['value'] = $aField['value'] ?? '';

                        foreach ($aField['fields'] as $subField => $aSubVal) {
                            $aNewField[$aSubVal['key']] = $aSubVal[$aSubVal['key']];
                        }

                        $this->aUsedSections[$key]['fields'][$aField['key']] = $aNewField;
                    }
                }
            }
        }

        SetSettings::setOptions(General::getUsedSectionKey($postType), $this->aUsedSections, true);
        SetSettings::setOptions($updatedKey, WILOKE_LISTING_TOOL_VERSION);

        SetSettings::setOptions(General::getUsedSectionKey($postType), $this->aUsedSections, false, true);
    }

    protected static function unSlashDeep($aVal)
    {
        if (!is_array($aVal)) {
            return stripslashes($aVal);
        }

        return array_map([__CLASS__, 'unSlashDeep'], $aVal);
    }

    protected function getHeroSearchFields($postType = '')
    {
        if ($postType) {
            $postType = General::detectPostTypeSubmission();
            $postType = $this->getPostType($postType);
        }

        $this->aUsedHeroSearchFields = GetSettings::getOptions(General::getHeroSearchFieldsKey($postType), false, true);
        if (empty($this->aUsedHeroSearchFields) || !is_array($this->aUsedHeroSearchFields)) {
            $this->setDefaultHeroSearchFields();
        }
    }

    public function getAllHeroSearchFields()
    {
        return wilokeListingToolsRepository()->get('listing-settings:heroSearchFields');
        //        $aFields    = array_filter($aRawFields, function ($aField) {
        //            $aBlackListings = ['listing_tag', 'open_now', 'best_rated', 'post_type', 'price_range'];
        //            return !in_array($aField['key'], $aBlackListings);
        //        });

        //        return $aFields;
    }

    protected function setDefaultHeroSearchFields($postType = '')
    {
        if (wp_doing_ajax()) {
            $postType = $_POST['postType'];
        } else {
            $postType = empty($postType) ? General::detectPostTypeSubmission() : $postType;
            $postType = $this->getPostType($postType);
        }

        $aDefaults = $this->getAllHeroSearchFields();
        $aUsedFields = [];
        foreach ($aDefaults as $key => $aField) {
            if (in_array($aField['key'], ['listing_cat', 'google_place'])) {
                $aUsedFields[] = $aField;
            }
        }

        SetSettings::setOptions(General::getHeroSearchFieldsKey($postType), $aUsedFields, true);

        do_action('wilcity/saved-hero-search-form/' . $postType, $aUsedFields);
        do_action('wilcity/saved-hero-search-form', $postType, $aUsedFields);
    }

    public function resetHeroSearchFields()
    {
        $this->validateAjaxPermission();
        $this->setDefaultHeroSearchFields();
        wp_send_json_success(
            [
                'msg' => $this->congratulationMsg()
            ]
        );
    }

    protected function setDefaultSchemaMarkup($postType)
    {
        if ($postType == 'event') {
            $default = wilokeListingToolsRepository()->get('schema-markup:event');
        } else {
            $default = wilokeListingToolsRepository()->get('schema-markup:listing');
        }
        SetSettings::setOptions(General::getSchemaMarkupKey($postType), json_encode($default), false);

        return json_encode($default);
    }

    protected function getSchemaMarkup($postType)
    {
        $settings = GetSettings::getOptions(General::getSchemaMarkupKey($postType), true, false);
        if (!$settings) {
            $this->setDefaultSchemaMarkup($postType);
        }

        return $settings;
    }

    public function saveHeroSearchFields()
    {
        $this->validateAjaxPermission();
        if (!isset($_POST['data'])) {
            SetSettings::deleteOption(General::getHeroSearchFieldsKey($_POST['postType']), true);
        } else {
            SetSettings::setOptions(General::getHeroSearchFieldsKey($_POST['postType']),
                self::unSlashDeep($_POST['data']), true);
        }

        SetSettings::setOptions(General::heroSearchFormSavedAt($_POST['postType']), current_time('timestamp', 1), true);
        SetSettings::setOptions('get_taxonomy_saved_at', current_time('timestamp', 1), true);

        wp_send_json_success(
            [
                'msg' => 'Congratulations! Your search form has been designed successfully'
            ]
        );
    }

    protected function getAvailableHeroSearchFields()
    {
        $postType = General::detectPostTypeSubmission();

        $postType = $this->getPostType($postType);
        $this->getHeroSearchFields($postType);
        $aDefault = $this->getAllHeroSearchFields();

        foreach ($aDefault as $key => $aDefaultField) {
            if ($this->isRemoveField($aDefaultField, $postType)) {
                unset($aDefault[$key]);
            }
        }

        if (empty($this->aUsedHeroSearchFields)) {
            $this->aAvailableHeroSearchFields = $aDefault;
        } else {
            $aUsedSearchFieldKeys = array_map(function ($aField) {
                return $aField['key'];
            }, $this->aUsedHeroSearchFields);

            foreach ($aDefault as $aField) {
                if (isset($aField['isCustomField']) || (!in_array($aField['key'], $aUsedSearchFieldKeys))) {
                    $this->aAvailableHeroSearchFields[] = $aField;
                }
            }
        }

        $this->aAvailableHeroSearchFields
            = empty($this->aAvailableHeroSearchFields) ? [] : $this->aAvailableHeroSearchFields;
    }

    public function matchedSlug($hook)
    {
        if (strpos($hook, self::$slug) !== false) {
            return true;
        }

        $this->parseCustomPostTypes();

        if (empty($this->aCustomPostTypesKey)) {
            return false;
        }

        foreach ($this->aCustomPostTypesKey as $menuSlug) {
            if (strpos($hook, $menuSlug) !== false) {
                return true;
            }
        }

        return false;
    }

    public function parseCustomPostTypes()
    {
        if (!empty($this->aCustomPostTypes)) {
            return $this->aCustomPostTypes;
        }

        $aCustomPostTypes
            = GetSettings::getOptions(wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'));
        if (!empty($aCustomPostTypes)) {
            foreach ($aCustomPostTypes as $aCustomPostType) {
                if (!in_array($aCustomPostType['key'], ['event', 'listing'])) {
                    $this->aCustomPostTypes[$aCustomPostType['key'] . '_settings'] = $aCustomPostType['name'] .
                        ' Settings';
                }
            }
        }

        if (!empty($this->aCustomPostTypes)) {
            $this->aCustomPostTypesKey = array_keys($this->aCustomPostTypes);
        }
    }

    private function getListingTypes()
    {
        if (!empty($this->aListingTypes)) {
            return $this->aListingTypes;
        }

        $aListingTypes = General::getPostTypes(false, false);

        foreach ($aListingTypes as $listingType => $aSettings) {
            $this->aListingTypes[$listingType]['menuSlug'] = $listingType . '_settings';
            $this->aListingTypes[$listingType]['menuName'] = $aSettings['name'] . ' Settings';
        }

        return $this->aListingTypes;
    }

    public function isRemoveField($aField, $postType)
    {
        return (isset($aField['inPostType']) && !in_array($postType, $aField['inPostType'])) ||
            (isset($aField['notInPostTypes']) && in_array($postType, $aField['notInPostTypes']));
    }

    protected function validateAjaxPermission()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error([
                'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
            ]);
        }

        if (empty($_POST['postType'])) {
            wp_send_json_error([
                'msg' => esc_html__('The Post Type is required', 'wiloke-listing-tools')
            ]);
        }
    }

    public function getPostType($hook)
    {
        if ($hook == '') {
            return 'listing';
        } else {
            return str_replace(['wiloke-tools_page_', '_settings'], ['', ''], $hook);
        }
    }

    public function generalScripts()
    {
        //        wp_enqueue_script('general-design-tool', WILOKE_LISTING_TOOL_URL . 'admin/source/js/general.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
        wp_localize_script('jquery', 'WILCITY_GENERAL', [
            'restURL' => rest_url('wiloke/v2/')
        ]);
    }

    public function designHighlightBoxes()
    {
        wp_enqueue_style('spectrum');
        wp_enqueue_script('spectrum');

        // wp_enqueue_script('design-highlight-boxes', WILOKE_LISTING_TOOL_URL . 'admin/source/js/design-highlight-boxes.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function designSidebar()
    {
        // wp_enqueue_script('design-sidebar', WILOKE_LISTING_TOOL_URL . 'admin/source/js/design-sidebar.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function designFieldsScript()
    {
        // wp_enqueue_script('design-fields', WILOKE_LISTING_TOOL_URL . 'admin/source/js/design-fields.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function designSingleNav()
    {
        // wp_enqueue_script('single-nav', WILOKE_LISTING_TOOL_URL . 'admin/source/js/design-single-nav.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function designSearchForm()
    {
        // wp_enqueue_script('design-search-form', WILOKE_LISTING_TOOL_URL . 'admin/source/js/design-search-form.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function enqueueListingCard()
    {
        // wp_enqueue_script('listing-card', WILOKE_LISTING_TOOL_URL . 'admin/source/js/listing-card.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function designHeroSearchForm()
    {
        // wp_enqueue_script('design-hero-search-form', WILOKE_LISTING_TOOL_URL . 'admin/source/js/design-hero-search-form.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function schemaMarkup()
    {
        // wp_enqueue_script('schema-markup', WILOKE_LISTING_TOOL_URL . 'admin/source/js/schema-markup.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function draggable()
    {
        // wp_enqueue_script('vue-sortablejs', WILOKE_LISTING_TOOL_URL . 'admin/assets/vue/Sortable.min.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
        // wp_enqueue_script('vue-draggable', WILOKE_LISTING_TOOL_URL . 'admin/assets/vue/vuedraggable.min.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function designListingSettings()
    {
        wp_enqueue_script('listing-settings', WILOKE_LISTING_TOOL_URL .
            'admin/source/js/listing-settings.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function requiredScripts()
    {
        wp_register_script('spectrum', WILOKE_LISTING_TOOL_URL . 'admin/assets/spectrum/spectrum.min.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        wp_register_style('spectrum', WILOKE_LISTING_TOOL_URL . 'admin/assets/spectrum/spectrum.min.css');
        wp_enqueue_style('spectrum');
        wp_enqueue_script('spectrum');
        wp_enqueue_script('underscore');
        // wp_enqueue_script('vuejs', WILOKE_LISTING_TOOL_URL . 'admin/assets/vue/vue.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);
        // wp_enqueue_script('vee-validate', WILOKE_LISTING_TOOL_URL . 'admin/assets/vue/vee-validate.min.js', array('jquery'), WILOKE_LISTING_TOOL_VERSION, true);

        // $this->draggable();

        wp_enqueue_style('line-awesome', WILOKE_LISTING_TOOL_URL . 'admin/source/css/line-awesome.min.css', [],
            WILOKE_LISTING_TOOL_VERSION);
        wp_enqueue_script('semantic-ui', WILOKE_LISTING_TOOL_URL .
            'admin/assets/semantic-ui/semantic.min.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        wp_enqueue_style('semantic-ui', WILOKE_LISTING_TOOL_URL . 'admin/assets/semantic-ui/form.min.css', [],
            WILOKE_LISTING_TOOL_VERSION, false);
        wp_enqueue_style('wiloke-design-fields', WILOKE_LISTING_TOOL_URL . 'admin/source/css/style.css', [],
            WILOKE_LISTING_TOOL_VERSION);
    }
}
