<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Models\BusinessHourMeta;

class SetSettings
{
    public static function setTransient($key, $val, $expiration)
    {
        set_transient($key, maybe_serialize($val), $expiration);
    }

    public static function deleteTransient($key)
    {
        delete_transient($key);
    }

    public static function setPrefix($metaKey, $prefix = '')
    {
        $prefix = empty($prefix) ? wilokeListingToolsRepository()->get('general:metaboxPrefix') : $prefix;
        $metaKey = strpos($metaKey, $prefix) === false ? $prefix . $metaKey : $metaKey;

        return $metaKey;
    }

    public static function getMetaPrefix()
    {
        return wilokeListingToolsRepository()->get('general:metaboxPrefix');
    }

    /**
     * We will use cache in the feature
     *
     * @param number $postID
     * @param string $metaKey
     *
     * @return mixed
     */
    public static function setPostMeta($postID, $metaKey, $val, $prefix = null, $isFocusAdd = false)
    {
        $metaKey = self::setPrefix($metaKey, $prefix);

        if ($metaKey == 'wilcity_hourMode' || $metaKey == 'hourMode') {
            return BusinessHourMeta::update($postID, $metaKey, $val);
        } else {
            if ($isFocusAdd) {
                delete_post_meta($postID, $metaKey);
                return add_post_meta($postID, $metaKey, $val);
            }

            return update_post_meta($postID, $metaKey, $val);
        }
    }

    public static function addPostMeta($postID, $metaKey, $val, $prefix = null)
    {
        $metaKey = self::setPrefix($metaKey, $prefix);

        return add_post_meta($postID, $metaKey, $val);
    }

    public static function deletePostMeta($postID, $metaKey, $prefix = null)
    {
        $metaKey = self::setPrefix($metaKey, $prefix);
        if ($metaKey == 'wilcity_hourMode' || $metaKey == 'hourMode') {
            return BusinessHourMeta::delete($postID, $metaKey);
        } else {
            return delete_post_meta($postID, $metaKey);
        }
    }

    /**
     * Get User Meta
     *
     * @param number $userID
     * @param string $metaKey
     *
     * @return mixed
     */
    public static function setUserMeta($userID, $metaKey, $value, $prefix = '')
    {
        $metaKey = self::setPrefix($metaKey, $prefix);
        update_user_meta($userID, $metaKey, $value);
    }

    public static function setCommentMeta($commentID, $metaKey, $value, $prefix = '')
    {
        $metaKey = self::setPrefix($metaKey, $prefix);
        update_comment_meta($commentID, $metaKey, $value);
    }

    public static function setPlanSettings($planID, $metaValue)
    {
        $postType = get_post_type($planID);
        $metaKey = 'add_' . $postType;

        return self::setPostMeta($planID, $metaKey, $metaValue);
    }

    public static function deleteUserPlan($userID)
    {
        $metaKey = wilokeListingToolsRepository()->get('user:userPlans');
        delete_user_meta($userID, $metaKey);
    }

    public static function setUserPlans($userID, $value, $prefix = '')
    {
        $metaKey = wilokeListingToolsRepository()->get('user:userPlans');
        self::setUserMeta($userID, $metaKey, $value, $prefix);
    }

    public static function deleteUserMeta($userID, $metaKey, $prefix = '')
    {
        $metaKey = self::setPrefix($metaKey, $prefix);
        delete_user_meta($userID, $metaKey);

        return true;
    }

    public static function deleteUserPlans($userID)
    {
        $metaKey = wilokeListingToolsRepository()->get('user:userPlans');
        self::deleteUserMeta($userID, $metaKey);
    }

    /**
     * @param $key
     * @param $value
     * @param bool $checkWPML
     */
    public static function setOptions($key, $value, $checkWPML = "")
    {
        $originalKey = $key;

        if (WPML::isActive()) {
            if ($checkWPML === 'AppMobile') {
                $lang = WPML::getCurrentLanguageApp();
            } else {
                $lang = WPML::getCurrentLanguage();
            }
            if (!empty($lang)) {
                $key = $key . '_' . $lang;
            }
        }

        update_option($key, maybe_serialize($value));
        if (WPML::isActive()) {
            if (isset($lang) &&  $lang == WPML::getDefaultLanguage()) {
                update_option($originalKey, maybe_serialize($value));
            }
        }
    }

    /**
     * @param $key
     * @param bool $checkWPML
     */
    public static function deleteOption($key, $checkWPML = false)
    {
        if ($checkWPML && WPML::isActive() && $lang = WPML::getCurrentLanguage()) {
            $key = $key . '_' . $lang;
        }

        delete_option($key);
    }

    public static function setTermsBelongsToPostType($postType, $taxonomy, $aTermIDs)
    {
        $key = $taxonomy . '_belongs_to_' . $postType;
        self::setOptions($key, $aTermIDs);
    }

    public static function updateTermsBelongsToPostType($postType, $taxonomy, $termID)
    {
        $aTermChildren = GetSettings::getTermsBelongsToPostType($postType, $taxonomy);
        $aTermChildren = empty($aTermChildren) ? [] : $aTermChildren;
        if (!in_array($termID, $aTermChildren)) {
            $aTermChildren[] = $termID;
        }

        self::setTermsBelongsToPostType($postType, $taxonomy, $aTermChildren);
    }

    public static function removeTermsFromDirectoryType($postType, $taxonomy, $termID)
    {
        $aTermChildren = GetSettings::getTermsBelongsToPostType($postType, $taxonomy, false, false, false);

        if (!empty($aTermChildren)) {
            $findKey = array_search($termID, $aTermChildren);
            unset($aTermChildren[$findKey]);
        }

        self::setTermsBelongsToPostType($postType, $taxonomy, $aTermChildren);
    }
}
