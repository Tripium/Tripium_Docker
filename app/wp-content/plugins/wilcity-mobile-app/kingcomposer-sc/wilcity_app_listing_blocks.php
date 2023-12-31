<?php

use \WILCITY_SC\SCHelpers;
use \WILCITY_APP\Helpers\AppHelpers;
use WilokeListingTools\Framework\Helpers\WPML;

$atts = shortcode_atts(
	[
		'TYPE'              => 'LISTING_BLOCKS',
		'heading'           => '',
		'post_type'         => 'listing',
		'items_per_column'  => 3,
		'number_of_blocks'  => 3,
		'orderby'           => 'post_date',
		'listing_cats'      => '',
		'listing_locations' => '',
		'listing_tags'      => '',
		'order'             => 'DESC',
		'bg_color'          => '#ffffff',
	],
	$atts
);

if (empty($atts['items_per_column']) || empty($atts['number_of_blocks'])) {
	echo '';

	return false;
}

$atts['items_per_column'] = abs($atts['items_per_column']);
$atts['number_of_blocks'] = abs($atts['number_of_blocks']);

$aArgs = SCHelpers::parseArgs($atts);
$aArgs['posts_per_page'] = $atts['items_per_column'] * $atts['number_of_blocks'];

$aResponse = apply_filters(
	'wilcity-mobile-app/filter/kingcomposer-sc/wilcity_app_listing_blocks/response',
	[],
	$aArgs,
	$atts
);

if (!has_filter('wilcity-mobile-app/filter/kingcomposer-sc/wilcity_app_listing_blocks/response')) {
	$aArgs = WPML::addFilterLanguagePostArgs($aArgs);
	$query = new WP_Query($aArgs);
	if (!$query->have_posts()) {
		wp_reset_postdata();

		return '';
	}
	$aResponse = [];
	while ($query->have_posts()) {
		$query->the_post();
		$aListing = apply_filters('wilcity/wilcity-mobile-app/filter/wilcity_app_listing_blocks', $atts, $query->post);
		$aResponse[] = $aListing;
	}
	wp_reset_postdata();
}

if (empty($aResponse)) {
	return '';
}

echo '%SC%' . base64_encode(json_encode(
		[
			'oSettings' => AppHelpers::removeUnnecessaryParamOnApp($atts),
			'TYPE'      => $atts['TYPE'],
			'oResults'  => array_chunk($aResponse, $atts['items_per_column']),
			'oViewMore' => [
				'endpoint' => 'list/listings',
				'params'   => AppHelpers::getViewMoreArgs($atts),
				'btnName'  => esc_html__('More', 'wilcity-mobile-app')
			]
		]
	)) . '%SC%';
return '';
