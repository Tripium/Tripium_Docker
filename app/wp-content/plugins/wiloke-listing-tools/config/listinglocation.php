<?php
$prefix = 'wilcity_';

return [
    'listing_location_settings' => [
        'id'           => 'listing_location_settings',
        'title'        => esc_html__('Settings', 'wiloke-listing-tools'),
        'object_types' => ['term'],
        'taxonomies'   => ['listing_location'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type' => 'text',
                'id'   => $prefix.'tagline',
                'name' => 'Tagline',
            ],
            [
                'type'            => 'wiloke_multiselect2_ajax',
                'sanitization_cb' => false,
                'taxonomy'        => 'listing_tag',
                'id'              => $prefix.'tags_children',
                'action'          => 'wilcity_get_tags_options',
                'name'            => 'Set Tags belong to this location',
                'desc'            => 'Leave empty means belongs to all tags'
            ],
            [
                'type'     => 'text',
                'taxonomy' => 'icon',
                'id'       => $prefix.'icon',
                'name'     => 'Icon',
                'desc'     => 'Warning: You have to use <a href="https://fontawesome.com/v4.7.0/" target="_blank">FontAwesome</a> or <a target="_blank" href="https://documentation.wilcity.com/knowledgebase/line-icon/">Line Awesome</a>. If you use another one, it will broken your App'
            ],
            [
                'type' => 'colorpicker',
                'id'   => $prefix.'icon_color',
                'name' => 'Icon Color',
            ],
            [
                'type'     => 'file',
                'taxonomy' => 'icon_img',
                'id'       => $prefix.'icon_img',
                'name'     => 'Icon Image',
                'desc'     => 'This setting will override Term Icon setting'
            ],
            [
                'type'     => 'file',
                'taxonomy' => 'slide_icon',
                'id'       => $prefix.'slide_icon',
                'name'     => 'Slider Icon',
                'desc'     => 'This setting will be used on Slider shortcode'
            ],
            [
                'type' => 'file',
                'id'   => $prefix.'featured_image',
                'name' => 'Featured Image'
            ],
            [
                'type'     => 'file_list',
                'taxonomy' => 'gallery',
                'id'       => $prefix.'gallery',
                'name'     => 'Gallery',
                'desc'     => 'If the gallery is not empty, it be used on this category page'
            ],
            [
                'type' => 'colorpicker',
                'id'   => $prefix.'left_gradient_bg',
                'name' => 'Left Gradient Background',
                'desc' => 'This setting is for Term Boxes shortcode'
            ],
            [
                'type' => 'colorpicker',
                'id'   => $prefix.'right_gradient_bg',
                'name' => 'Right Gradient Background',
                'desc' => 'This setting is for Term Boxes shortcode'
            ],
            [
                'type'    => 'text',
                'id'      => $prefix.'gradient_tilted_degrees',
                'name'    => 'Gradient tilted degrees',
                'desc'    => 'Eg: A gradient tilted 45 degrees, starting Left Background and finishing Right Background',
                'default' => -10
            ],
            [
                'type'        => 'multicheck_inline',
                'id'          => $prefix.'belongs_to',
                'name'        => esc_html__('Belongs To Listing', 'wiloke-listing-tools'),
                'desc'        => 'Enter in your icon name you want to use. You can find the icon at <a href="https://documentation.wilcity.com/knowledgebase/line-icon/" target="_blank">Line Icon</a>',
                'description' => 'Select Listing Types that this term should belong to. Leave empty to set the category for all',
                'options_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'setListingTypesOptions']
            ],
            [
                'type'        => 'select',
                'id'          => $prefix.'default_belongs_to',
                'name'        => esc_html__('Default Listing Type', 'wiloke-listing-tools'),
                'description' => 'When clicking on a category box, this listing type is assigned as the default.',
                'options_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'setListingTypesOptions']
            ],
            [
                'type'        => 'select',
                'id'          => $prefix.'default_belongs_to',
                'name'        => esc_html__('Default Listing Type', 'wiloke-listing-tools'),
                'description' => 'When clicking on a location box, this listing type is assigned as the default.',
                'options_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'setListingTypesOptions']
            ],
            [
                'type' => 'text',
                'id'   => $prefix.'location_code',
                'name' => esc_html__('Mapbox Location Code', 'wiloke-listing-tools')
            ]
        ]
    ]
];
