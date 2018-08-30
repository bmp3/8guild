<?php

/*
Plugin Name: WP Posts Shortcode
Author: 8guild
Version: 1.0.0
Text-domain: 8guild
*/

add_action( 'init', function() {
   wp_enqueue_style( 'pp-style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), false, 'all' );
});

add_action( 'vc_before_init', 'add_custom_shortcodes' );

function add_custom_shortcodes() {

    if (function_exists('vc_map')) {

        vc_map(array(
            'name' => __('Posts by query', '8guild'),
            'base' => 'get_posts_by_query',
            'description' => __('Allows to query posts by various params', '8guild'),
            'category' => __('8guild', '8guild'),
            'icon' => 'ss-vc-icon icon-arrows-h',
            'params' => array(

                array(
                    'type' => 'textfield',
                    'heading' => __('Posts IDs', '8guild'),
                    'param_name' => 'post_ids',
                    'description' => __('Type posts IDs to retrieve', '8guild')
                ),

                array(
                    "type" => "dropdown_multi",
                    "heading" => esc_html__("Post Catagoties", '8guild'),
                    "param_name" => "post_cats",
                    "value" => get_post_categories(),
                ),

                array(
                    "type" => "dropdown_multi",
                    "heading" => esc_html__("Post Tags", '8guild'),
                    "param_name" => "past_tags",
                    "value" => get_post_tags(),
                ),

                array(
                    'type' => 'textfield',
                    'heading' => __('Explude Posts IDs', '8guild'),
                    'param_name' => 'exlude_posts',
                    'description' => __('', '8guild'),
                    'value' => ''
                ),

                array(
                    'type' => 'textfield',
                    'heading' => __('Number of Posts', '8guild'),
                    'param_name' => 'posts_number',
                    'description' => __('', '8guild'),
                    'value' => ''
                ),

                array(
                    "type" => "dropdown",
                    "heading" => __("Order By", '8guild'),
                    "param_name" => "order_by",
                    "value" => array(
                        'Date' => 'date',
                        'ID' => 'id',
                        'Name' => 'name',
                        'Randomize' => 'rand'
                    ),
                ),

                array(
                    "type" => "dropdown",
                    "heading" => __("Sorting", '8guild'),
                    "param_name" => "sorting",
                    "value" => array(
                        'ASC' => 'ASC',
                        'SESC' => 'DESC',
                    ),
                ),

            )
        ));

        vc_add_shortcode_param('dropdown_multi', 'dropdown_multi_settings_field');
        function dropdown_multi_settings_field($param, $value)
        {
            $param_line = '';
            $param_line .= '<select multiple name="' . esc_attr($param['param_name']) . '" class="wpb_vc_param_value wpb-input wpb-select ' . esc_attr($param['param_name']) . ' ' . esc_attr($param['type']) . '">';
            foreach ($param['value'] as $text_val => $val) {
                if (is_numeric($text_val) && (is_string($val) || is_numeric($val))) {
                    $text_val = $val;
                }
                $text_val = __($text_val, "js_composer");
                $selected = '';

                if (!is_array($value)) {
                    $param_value_arr = explode(',', $value);
                } else {
                    $param_value_arr = $value;
                }

                if ($value !== '' && in_array($val, $param_value_arr)) {
                    $selected = ' selected="selected"';
                }
                $param_line .= '<option class="' . $val . '" value="' . $val . '"' . $selected . '>' . $text_val . '</option>';
            }
            $param_line .= '</select>';

            return $param_line;

        }

    }

}


function get_post_categories() {

    $cats = get_categories( );

    $out = array( );
    foreach( $cats as $cat ){

        $out[$cat->term_id] = $cat->name;

    }

    return $out;

}

function get_post_tags() {

    $tags = get_tags( );

    $out = array( );
    foreach( $tags as $tag ){

        $out[$tag->term_id] = $tag->name;

    }

    return $out;

}

function get_posts_by_query( $atts ) {

    $defaults = array( 'post_ids' => null,
        'posts_cats' => null,
        'posts_tags' => null,
        'exclude_posts' => null,
        'posts_number' => 5,
        'order_by' => 'date',
        'sorting' => 'ASC'
    );

    $atts = shortcode_atts( $defaults, $atts );


    if ( $atts['posts_number'] == 'all' )
        $atts['posts_number'] = -1;
    $args = array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => $atts['posts_number'] );
    if ( strlen( $atts['post_ids'] ) > 0 ) $args['include'] = $atts['post_ids'];
    if ( strlen( $atts['posts_cats'] ) > 0 ) $args['category'] = $atts['posts_cats'];
    if ( strlen( $atts['posts_tags'] ) > 0 ) $args['tag'] = $atts['posts_tags'];
    if ( strlen( $atts['exclude_posts'] ) > 0 ) $args['exclude'] = $atts['exclude_posts'];
    if ( strlen( $atts['order_by'] ) > 0 ) $args['orderby'] = $atts['order_by'];
    if ( strlen( $atts['sorting'] ) > 0 ) $args['order'] = $atts['sorting'];

    $posts = get_posts( $args );
    $out = '<div class="posts-box">';

    foreach( $posts as $p ) {

        $img = get_the_post_thumbnail( $p, 'medium' );
        $permalink = get_permalink( $p );

        $out .=
            '<div class="p-box">
                <div class="p-title"><a href="' . $permalink . '">' . get_the_title( $p ) . '</a></div>';
                if ( strlen( $img ) > 0 )
                    $out .= '<div class="p-img"><a href="' . $permalink . '">' . $img . '</a></div>';
                $out .=
                    '<div class="p-date"><a href="' . $permalink . '">' . get_the_date( 'd m Y, H:i:s', $p ) . '</a></div>';
                if ( has_excerpt( $p->ID ) )
                    $out .= '<div class="p-excerpt">' . get_the_excerpt( $p->ID ) . '</div>';
        $out .=
            '</div>';

    }

    $out .= '</div>';

    return $out;

}

add_shortcode( 'get_posts_by_query', 'get_posts_by_query');

?>