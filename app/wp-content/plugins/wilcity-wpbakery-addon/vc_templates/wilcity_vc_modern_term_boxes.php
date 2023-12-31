<?php
function wilcityVCModernTermBoxes($atts)
{
    $atts = shortcode_atts(
      [
        'TYPE'                       => 'MODERN_TERM_BOXES',
        'parseCategories'            => ['term'],
        'group'                      => 'term',
        'isRequiredPostType'         => 'yes',
        'heading'                    => '',
        '_id'                        => '',
        'heading_color'              => '',
        'desc'                       => '',
        'description'                => '',
        'description_color'          => '',
        'desc_color'                 => '',
        'header_desc_text_align'     => '',
        'term_redirect'              => 'search_page',
        'post_type'                  => '',
        'taxonomy'                   => 'listing_cat',
        'listing_cats'               => '',
        'listing_cat'                => '',
        'listing_locations'          => '',
        'listing_location'           => '',
        'maximum_posts_on_lg_screen' => 'col-lg-3',
        'maximum_posts_on_md_screen' => 'col-md-4',
        'maximum_posts_on_sm_screen' => 'col-sm-6',
        'maximum_posts_on_xs_screen' => 'col-xs-6',
        'col_gap'                    => 20,
        'number'                     => 6,
        'image_size'                 => 'wilcity_560x300',
        'listing_tags'               => '',
        'is_hide_empty'              => 'no',
        'is_show_parent_only'        => 'no',
        'orderby'                    => 'count',
        'order'                      => 'DESC',
        'extra_class'                => '',
        'css_custom'                 => ''
      ],
      $atts
    );
    
    $atts = apply_filters('wilcity/vc/parse_sc_atts', $atts);
    
    ob_start();
    wilcity_sc_render_modern_term_boxes($atts);
    $content = ob_get_contents();
    ob_end_clean();
    
    return $content;
}

add_shortcode('wilcity_vc_modern_term_boxes', 'wilcityVCModernTermBoxes');
