<?php

/*
Plugin Name: WP Ajax Pagination
Author: 8guild
Version: 1.0.0
Text-domain: 8guild
*/

function ajax_pagination( $atts = array( ) ) {

    global $wp_query;

    $defaults = array( 'position' => 'left', 'echo' => false );

    $atts = shortcode_atts ( $defaults, $atts );
    $align = get_option( 'ajax_pagination_align' );

    $out =
        '<div class="pagination-box ' . $align . '">
             <button class="pb">' . __( 'load more', '8guild' ) . '</button>' .
             wp_nonce_field( 'pb_nonce', 'pb_nonce', true, false) .
        '</div>';

    if ( $atts['echo'] )
        echo $out;
    else
        return $out;

}

add_action( 'wp_footer', 'add_ajax_paginaton_data' );

function add_ajax_paginaton_data() {

    echo
    '<script type="text/javascript">
         jQuery(document).ready( function( $ ) {
             
             var current_page = ajaxdata.paged;
             
             function get_posts_to_show( paged = null ) {
                 
                 var pts, page;
                 
                 if ( !paged ) page = ajaxdata.paged;
                 else page = paged;
                 
                 pts = ajaxdata.fp - paged * ajaxdata.ppp;
                 
                 if ( pts > ajaxdata.ppp )
                     pts = ajaxdata.ppp;
                 
                 return pts;
                 
             }
             
             pts = get_posts_to_show();
             $("button.pb").text( ajaxdata.load_more_txt + " (" + pts + ")" );             
             $("button.pb").on( "click", function ( e ) {
                 
                 var data, pts;
                 
                 $(this).addClass("loading");
                 data = { "action" : "get_ajax_pagination", "current_page" : current_page, "nonce" : $("#pb_nonce").val() }
                 
                 $.ajax({
			         url : ajaxdata.url, 
			         data : data, 
			         type : "POST", 
			         success : function ( data ) {
				         if( data ) {
                             data = JSON.parse( data );
                             if ( data.page >= 0 ) {
                                 $("button.pb").removeClass("loading");
                                 $( data.content ).insertBefore(".pagination-box");
                                 current_page = data.page;
                                 pts = get_posts_to_show( current_page );
                                 if ( pts > 0 )
                                     $("button.pb").text( ajaxdata.load_more_txt + " (" + pts + ")" );
                                 else if ( pts <= 0 )
                                     $("button.pb").text( ajaxdata.no_more_posts_txt );
                             }
				              else {
                                  $("button.pb").text( data.content );
				              }
				         }
			         }
		         });
             });
         });
     </script>';

    echo
    '<style>
         .pb-left article { text-align : left; }
         .pb-right article { text-align : right; }
         .pb-center article { text-align : center; }
         .pagination-box { text-align : center; }
         .pagination-box.left { text-align : left; }
         .pagination-box.right { text-align : right; }
         button.pb { width : 160px; padding-left : 0; padding-right : 0; position : relative; display : inline-block; }
         button.pb:after { display : none; position : absolute; content : ""; width : 1em; height : 1em; background-image : url(' . plugin_dir_url( __FILE__ ) . 'imgs/loading.gif); top : 50%; right : -1em; transform : translateY( -50% );  }
         button.loading:after { display : block; }
         
     </style>';

}


add_action( 'wp_enqueue_scripts', 'add_ajax_data', 99 );

function add_ajax_data() {

    global $wp_query;

    wp_localize_script( 'jquery', 'ajaxdata',
        array(
            'url' => admin_url('admin-ajax.php'),
            'paged' => $wp_query->query_vars['paged'],
            'ppp' => $wp_query->query_vars['posts_per_page'],
            'fp' => $wp_query->found_posts,
            'load_more_txt' => __( 'load more', '8guild' ),
            'no_more_posts_txt' => __( 'no more items', '8guild' )
        )
    );
}


add_action( 'wp_ajax_get_ajax_pagination', 'get_ajax_pagination' );
add_action( 'wp_ajax_nopriv_get_ajax_pagination', 'get_ajax_pagination' );


function get_ajax_pagination() {

    if( wp_verify_nonce( $_POST['nonce'], 'pb_nonce' ) ) {

        $args['paged'] = $_POST['current_page'] + 1;
        $args['post_status'] = 'publish';

        query_posts($args);

        $content = '';
        ob_start();
        if (have_posts()) :

            while (have_posts()): the_post();

                get_template_part('template-parts/post/content', get_post_format());

            endwhile;

        endif;
        $content = ob_get_contents();
        ob_end_clean();

        echo json_encode(array('page' => $_POST['current_page'] + 1, 'content' => $content));

    }
    else
        echo json_encode(array('page' => -1, 'content' => __( 'type mismatch', '8guild' ) ));

    die();

}


add_action( 'customize_register', 'customizer_init' );

function customizer_init( $wp_customize ) {

    $transport = 'postMessage';

    $wp_customize->add_section(
        'ajax_pagination_align_box',
        array(
            'title'     => __( 'Pagination Align', '8guild' ),
            'priority'  => 200
        )
    );

    $wp_customize->add_setting(
        'ajax_pagination_align',
        array(
            'default'   => 'center',
            'type'      => 'option',
            'transport' => $transport
        )
    );

    $wp_customize->add_control(
        'ajax_pagination_align',
        array(
            'section'  => 'ajax_pagination_align_box', // секция
            'label'    => __( 'Pagination Align', '8guild' ),
            'type'     => 'select',
            'choices'  => array(
                'center' => 'center',
                'left'   => 'left',
                'right'  => 'right'
            )
        )
    );

}
add_filter( 'body_class', 'add_pagination_align' );

function add_pagination_align( $classes ) {

    //if ( is_archive() ) {

        $align = get_option( 'ajax_pagination_align' );
        $classes[] = 'pb-' . $align;

    //}

    return $classes;

}




?>