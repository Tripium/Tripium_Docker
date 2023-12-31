<?php

namespace WilokeListingTools\Controllers;

use WeDevs\Dokan\Withdraw\Withdraw;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\NotificationsModel;
use WP_Query;
use WP_User;

class DokanController extends Controller
{
    private static $prefix = 'dokan_';

    public function __construct()
    {
        add_action('profile_update', [$this, 'toggleSelling'], 10, 1);
        add_filter('dokan_get_dashboard_nav', [$this, 'dokanDashboardMenu']);
        add_action('dokan_new_product_added', [$this, 'addASignIfProductIsNotPublished']);
        //		add_action('dokan_product_updated', array($this, 'addASignIfProductIsNotPublished'));
        add_action('post_updated', [$this, 'notifyToVendorProductIsPublished'], 10, 3);
        add_action('woocommerce_order_status_completed', [$this, 'notifySomeonePurchasedYourProduct']);
        add_action('dokan_after_withdraw_request', [$this, 'notifyRequestWithdrawalToAdmin'], 10, 3);
        add_action('dokan_withdraw_request_approved', [$this, 'notifyApprovedWithdrawal'], 10, 1);
        add_action('dokan_withdraw_request_cancelled', [$this, 'notifyCancelledWithdrawal'], 10, 2);

        add_action('wp_ajax_wilcity_fetch_dokan_products', [$this, 'ajaxSearchMyProducts']);
        add_action('wp_ajax_wilcity_fetch_product_cats', [$this, 'fetchProductCats']);
        add_filter('woocommerce_product_related_posts_query', [$this, 'filterRelateProductOnSingle'], 10, 3);

        add_action('wp_ajax_wilcity_fetch_my_products', [$this, 'showingDokanOnSingleListing']);
        add_action('wp_ajax_nopriv_wilcity_fetch_my_products', [$this, 'showingDokanOnSingleListing']);
        add_action('wp_ajax_wilcity_get_my_products', [$this, 'showingDokanOnSingleListing']);
        add_action('wp_ajax_nopriv_wilcity_get_my_products', [$this, 'showingDokanOnSingleListing']);

        add_action('dokan_product_edit_after_options', [$this, 'addSendQRCodeSetting']);
        add_action('dokan_product_updated', [$this, 'saveSendQRCodeSetting'], 10, 2);
        add_action('dokan_new_product_added', [$this, 'saveSendQRCodeSetting'], 10, 2);

        add_action('admin_init', [$this, 'setDokanPagesToPageBuilderTemplate']);
        add_action('woocommerce_no_products_found', [$this, 'addWrapperOpenToSearchForm'], 8);
        add_action('woocommerce_no_products_found', [$this, 'addShopWrapperOpen'], 10);
        add_action('woocommerce_no_products_found', [$this, 'addShopWrapperClose'], 999);
        add_action('wp_head', [$this, 'removeDokanFilter']);

//        add_action('dokan_settings_after_store_name', [$this, 'addProfileInfoToDokan']);
//        add_action('dokan_store_profile_saved', [$this, 'saveProfileInfo'], 10, 2);
    }

    public function saveProfileInfo($storeId, $aDokanSettings)
    {
        if (isset($aDokanSettings['shop_logo'])) {
            update_user_meta($storeId, 'wilcity_shop_logo', $aDokanSettings['shop_logo']);
        } else {
            delete_user_meta($storeId, 'wilcity_shop_logo');
        }
    }

    public function addProfileInfoToDokan()
    {
        $userId = isset($_GET['user_id']) && current_user_can('administrator') ? absint($_GET['user_id']) :
            get_current_user_id();
        $shopIconId = User::getField('shop_logo', $userId);
        ?>
        <div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label"
                   for="dokan_gravatar"><?php esc_html_e('Shop Logo', 'wiloke-listing-tools');
                ?></label>

            <div class="dokan-w5 dokan-gravatar">
                <div class="dokan-left gravatar-wrap<?php echo $shopIconId ? '' : ' dokan-hide'; ?>">
                    <?php $gravatar_url = $shopIconId ? wp_get_attachment_thumb_url($shopIconId) : ''; ?>
                    <input type="hidden" class="dokan-file-field" value="<?php echo esc_attr($shopIconId); ?>"
                           name="shop_logo">
                    <img class="dokan-gravatar-img" src="<?php echo esc_url($gravatar_url); ?>">
                    <a class="dokan-close dokan-remove-gravatar-image">&times;</a>
                </div>
                <div class="gravatar-button-area<?php echo esc_attr($shopIconId) ? ' dokan-hide' : ''; ?>">
                    <a href="#" class="dokan-pro-gravatar-drag dokan-btn dokan-btn-default">
                        <i class="fa fa-cloud-upload"></i> <?php esc_html_e('Upload Logo', 'wiloke-listing-tools'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    public function removeDokanFilter()
    {
        remove_action('get_avatar_url', 'dokan_get_avatar_url', 99);
    }

    public function addWrapperOpenToSearchForm()
    {
        echo '<div class="col-md-12">';
    }

    public function addShopWrapperOpen()
    {
        global $wiloke;
        $sidebarPosition = $wiloke->aThemeOptions['woocommerce_sidebar'];
        if ($sidebarPosition == 'left') {
            $mainClass = 'col-md-8 col-pull-4';
        } else if ($sidebarPosition == 'right') {
            $mainClass = 'col-md-8';
        } else {
            $mainClass = 'col-md-12';
        }

        echo '</div><div class="' . esc_attr($mainClass) . '">';
    }

    public function addShopWrapperClose()
    {
        echo '</div><!-- End Dokan Wrapper-->';
    }

    private function setToPagebBuilderTemplate($pageID)
    {
        $pageTemplate = get_page_template_slug($pageID);
        if (empty($pageTemplate) || $pageTemplate == 'default') {
            update_post_meta($pageID, '_wp_page_template', 'templates/page-builder.php');
        }
    }

    public function setDokanPagesToPageBuilderTemplate()
    {
        if (!current_user_can('edit_theme_options') || !isset($_GET['page']) || $_GET['page'] != 'dokan') {
            return false;
        }

        if (GetSettings::getOptions('shutdown_dokan_setup')) {
            return false;
        }

        $aDokanPages = GetSettings::getDokanPages(false);
        if (empty($aDokanPages)) {
            return false;
        }

        foreach ($aDokanPages as $dokanPageID) {
            if (empty($dokanPageID)) {
                continue;
            }

            $this->setToPagebBuilderTemplate($dokanPageID);
        }

        if (function_exists('wc_get_page_id')) {
            $this->setToPagebBuilderTemplate(wc_get_page_id('myaccount'));
            $this->setToPagebBuilderTemplate(wc_get_page_id('cart'));
            $this->setToPagebBuilderTemplate(wc_get_page_id('checkout'));
            $this->setToPagebBuilderTemplate(wc_get_page_id('shop'));
        }

        SetSettings::setOptions('shutdown_dokan_setup', 'yes');
    }

    public function saveSendQRCodeSetting($productID, $aData)
    {
        if (isset($aData['_wilcity_is_send_qrcode']) && !empty($aData['_wilcity_is_send_qrcode'])) {
            SetSettings::setPostMeta($productID, 'is_send_qrcode', $aData['_wilcity_is_send_qrcode']);
        } else {
            SetSettings::setPostMeta($productID, 'is_send_qrcode', 'no');
        }

        if (isset($aData['_wilcity_qrcode_description']) && !empty($aData['_wilcity_qrcode_description'])) {
            SetSettings::setPostMeta($productID, 'qrcode_description', $aData['_wilcity_qrcode_description']);
        } else {
            SetSettings::setPostMeta($productID, 'qrcode_description', '');
        }
    }

    public function addSendQRCodeSetting($postID)
    {
        $status = GetSettings::getPostMeta($postID, 'is_send_qrcode');
        $status = $status == 'yes' ? 'yes' : '';
        $description = GetSettings::getPostMeta($postID, 'qrcode_description');
        ?>
        <div class="dokan-other-options dokan-edit-row dokan-clearfix">
            <div class="dokan-section-heading" data-togglehandler="dokan_qrcode_options">
                <h2><i class="fa fa-qrcode" aria-hidden="true"></i> <?php esc_html_e('QRCode Settings',
                        'wiloke-listing-tools'); ?></h2>
                <a href="#" class="dokan-section-toggle">
                    <i class="fa fa-sort-desc fa-flip-vertical" aria-hidden="true"></i>
                </a>
                <div class="dokan-clearfix"></div>
            </div>

            <div class="dokan-section-content">
                <div class="dokan-form-group">
                    <label class="" for="_wilcity_is_send_qrcode">
                        <input name="_wilcity_is_send_qrcode" id="_wilcity_is_send_qrcode"
                               value="yes" <?php checked($status, 'yes'); ?> type="checkbox">
                        <?php esc_html_e('Sending QRCode after customer purchasing this product',
                            'wiloke-listing-tools'); ?> </label>
                </div>
                <div class="dokan-clearfix"></div>
                <div class="dokan-form-group">
                    <label class="form-label" for="_wilcity_qrcode_description"><?php esc_html_e('Email Content',
                            'wiloke-listing-tools'); ?> </label>
                    <textarea name="_wilcity_qrcode_description" id="_wilcity_qrcode_description"
                              class="dokan-form-control"><?php echo esc_html($description); ?></textarea>
                    <i><?php echo apply_filters('wilcity/dokan/qrcode-settings/description',
                            esc_html_e('Eg: %h2%Check for this Event%close_h2%%breakDown%Please show us QRCode below when visiting the Event.',
                                'wiloke-listing-tools')); ?></i>
                </div>
            </div>
        </div>
        <?php
    }

    public function showingDokanOnSingleListing()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aProducts = GetSettings::getListingProducts($_GET['parentID'], GetSettings::getProductMode($_GET['parentID']));

        $msg = esc_html__('Whoops! We found no products of this listing', 'wiloke-listing-tools');

        if (empty($aProducts) || !is_array($aProducts)) {
            $oRetrieve->error([
                'msg' => $msg
            ]);
        }

        $columns = apply_filters('wilcity/listing/my_products/columns', 3);

        $aProductIds = [];
        foreach ($aProducts as $aProduct) {
            $aProductIds[] = $aProduct['ID'];
        }

        $productsContent = do_shortcode('[products columns="' . $columns . '" ids="' . implode(',', $aProductIds) .
            '"]');

        if (empty($productsContent)) {
            $oRetrieve->error([
                'msg' => $msg
            ]);
        }

        if (function_exists('dokan')) {
            $oVendor = dokan()->vendor->get(get_post_field('post_author', $_GET['parentID']));
            $shopUrl = $oVendor->get_shop_url();
        } else {
            $shopUrl = "";
        }

        $oRetrieve->success([
            'content'      => $productsContent,
            'maxPages'     => 1,
            'shopUrl' => $shopUrl
        ]);
    }

    public function filterRelateProductOnSingle($aQuery, $productID, $aArgs)
    {
        if (!self::isDokanProduct($productID)) {
            return $aQuery;
        }

        global $wpdb;

        if (!isset($aQuery['join']) || strpos($aQuery['join'], $wpdb->postmeta) === false) {
            $aQuery['join'] .= " LEFT JOIN " . $wpdb->postmeta . " ON ($wpdb->postmeta.post_id = p.ID)";
        }

        $dokanField = WILOKE_LISTING_PREFIX . 'is_dokan';
        $aQuery['where'] .= " AND ($wpdb->postmeta.meta_key = '" . $dokanField .
            "' AND $wpdb->postmeta.meta_value='yes')";

        return $aQuery;
    }

    public static function getProductsByUserID($userID, $s = '', $aAtts = [])
    {
        $aArgs = [
            'post_type'      => 'product',
            's'              => $s,
            'posts_per_page' => 20,
            'post_status'    => ['publish', 'pending'],
            'author'         => $userID,
            'meta_query'     => [
                [
                    'key'     => WILOKE_LISTING_PREFIX . 'is_dokan',
                    'value'   => 'yes',
                    'compare' => '='
                ]
            ]
        ];

        if (isset($aAtts['product_types']) && !empty($aAtts['product_types'])) {
            $aArgs['tax_query'][] = [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => explode(',', $aAtts['product_types'])
            ];
        } else {
            $aArgs['tax_query'][] = [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => ['simple', 'variable', 'external']
            ];
        }

        if (empty($s)) {
            unset($aArgs['s']);
        }

        if (user_can($userID, 'administrator')) {
            unset($aArgs['author']);
            unset($aArgs['meta_query']);
        }

        $aArgs = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/DokanController/getProductsByUserID',
            $aArgs,
            $aAtts
        );

        $query = new WP_Query($aArgs);
        $aOptions = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $aOptions[] = General::buildSelect2OptionForm($query->post);
            }
        }

        return $aOptions;
    }

    public function fetchProductCats()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $s = '';
        if (isset($_GET['search'])) {
            $s = $_GET['search'];
        } else if (isset($_GET['q'])) {
            $s = $_GET['q'];
        }

        $aTerms = get_terms(
            [
                'name__like' => $s,
                'taxonomy'   => 'product_cat',
                'number'     => 10
            ]
        );

        if (!empty($aTerms) && !is_wp_error($aTerms)) {
            foreach ($aTerms as $oTerm) {
                $aOptions[] = [
                    'id'    => $oTerm->term_id,
                    'text'  => $oTerm->name,
                    'label' => $oTerm->name
                ];
            }
        }

        if (empty($aOptions)) {
            $oRetrieve->error(['msg' => esc_html__('We found no category', 'wiloke-listing-tools')]);
        }

        if (isset($_GET['mode']) && $_GET['mode'] == 'select') {
            $oRetrieve->success([
                'results' => $aOptions
            ]);
        };

        $oRetrieve->success(
            [
                'msg' => [
                    'results' => $aOptions
                ]
            ]
        );
    }

    public function ajaxSearchMyProducts()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $s = '';
        if (isset($_GET['search'])) {
            $s = $_GET['search'];
        } else if (isset($_GET['q'])) {
            $s = $_GET['q'];
        }

        if (isset($_GET['post_id']) && !empty($_GET['post_id'])) {
            $userID = get_post_field('post_author', $_GET['post_id']);
        }

        if (!isset($userID) || empty($userID)) {
            $userID = get_current_user_id();
        }

        $aOptions = self::getProductsByUserID($userID, $s, $_GET);
        if (empty($aOptions)) {
            $oRetrieve->error(['msg' => esc_html__('We found no products', 'wiloke-listing-tools')]);
        }

        if (isset($_GET['mode']) && $_GET['mode'] == 'select') {
            $oRetrieve->success([
                'results' => $aOptions
            ]);
        };

        $oRetrieve->success(
            [
                'msg' => [
                    'results' => $aOptions
                ]
            ]
        );
    }

    private function getWithDrawID(array $aInfo)
    {
        global $wpdb;
        $dbName = $wpdb->prefix . 'dokan_withdraw';
        $sql = "SELECT id FROM {$dbName} WHERE ";

        $concat = "";
        if (isset($aInfo['status'])) {
            $sql .= "status=" . absint($aInfo['status']);
            $concat = " AND";
        }

        if (isset($aInfo['amount'])) {
            $sql .= $concat . " cast(amount as decimal(18,6))=" . floatval($aInfo['amount']);
        }

        if (isset($aInfo['method'])) {
            $sql .= $concat . " method='" . sanitize_text_field($aInfo['method']) . "'";
        }

        if (isset($aInfo['user_id'])) {
            $sql .= $concat . " user_id=" . absint($aInfo['user_id']);
        }

        $sql .= " ORDER BY id DESC";

        $withDrawID = $wpdb->get_var($sql);

        return empty($withDrawID) ? false : $withDrawID;
    }

    /**
     * @param Withdraw $withdraw
     */
    public function notifyApprovedWithdrawal($withdraw)
    {
        $withDrawID = $this->getWithDrawID([
            'amount' => $withdraw->get_amount(),
            'method' => $withdraw->get_method(),
            'userID' => $withdraw->get_user_id(),
            'status' => 1
        ]);
        NotificationsModel::add($withdraw->get_user_id(), 'dokan_approved_withdrawal', $withDrawID);
    }

    public function notifyCancelledWithdrawal($userID, $response)
    {
        $withDrawID = $this->getWithDrawID([
            'amount' => $response->amount,
            'userID' => $userID,
            'method' => $response->method,
            'status' => 2
        ]);
        NotificationsModel::add($userID, 'dokan_cancelled_withdrawal', $withDrawID);
    }

    public function notifyRequestWithdrawalToAdmin($oCurrentUser, $amount, $method)
    {
        $withDrawID = $this->getWithDrawID([
            'amount' => $amount,
            'method' => $method,
            'userID' => $oCurrentUser->ID,
            'status' => ''
        ]);
        if (empty($withDrawID)) {
            return false;
        }

        $oUserAdmin = User::getFirstSuperAdmin();
        NotificationsModel::add($oUserAdmin->ID, 'dokan_requested_withdrawal', $withDrawID);
    }

    public static function isDokanProduct($productID)
    {
        return GetSettings::getPostMeta($productID, 'is_dokan') == 'yes';
    }

    public function notifySomeonePurchasedYourProduct($orderID)
    {
        $order = wc_get_order($orderID);
        $aItems = $order->get_items();

        foreach ($aItems as $oItem) {
            $productID = $oItem->get_product_id();
            if (self::isDokanProduct($productID)) {
                do_action('wilcity/dokan/order-completed', $orderID, $productID);

                NotificationsModel::add(get_post_field('post_author', $productID), 'dokan_order_completed', $orderID);
            }
        }
    }

    public function notifyToVendorProductIsPublished($productID, $oPostAfter, $oPostBefore)
    {
        if ($oPostAfter->post_type != 'product' || $oPostBefore->post_status == $oPostAfter->post_status ||
            $oPostAfter->post_status != 'publish' || !self::isDokanProduct($productID)) {
            return false;
        }

        NotificationsModel::add($oPostAfter->post_author, 'dokan_product_published', $productID);
    }

    public function addASignIfProductIsNotPublished($productID)
    {
        SetSettings::setPostMeta($productID, 'is_dokan', 'yes');
    }

    public function dokanDashboardMenu($aUrls)
    {
        $aUrls['main_dashboard'] = [
            'title' => __('Dashboard', 'wiloke-listing-tools'),
            'icon'  => '<i class="fa fa-dashboard"></i>',
            'url'   => GetWilokeSubmission::getField('dashboard_page', true)
        ];

        $aUrls['dashboard']['title'] = esc_html__('Statistic', 'wiloke-listing-tools');
        $aUrls['dashboard']['icon'] = '<i class="fa fa-line-chart"></i>';

        return $aUrls;
    }

    public function toggleSelling($userID)
    {
        if (current_user_can('edit_user', $userID)) {
            $oUser = new WP_User($userID);
            if (isset($_POST['role'])) {
                if ($_POST['role'] == 'seller') {
                    $oUser->add_role('contributor');
                }
            }
        }
    }
}
