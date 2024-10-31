<?php
function generate_xml(){
    $options = get_option('productlisters');
    $output = '';
    $args = array(
                'post_type' => 'wpsc-product',
                'post_status' => 'publish',
                'order' => 'ASC'
                );

    $products = get_posts($args);
    foreach ($products as $product){

        $output .= '<item>';
        $output .= '<title><![CDATA['.$product->post_title.']]></title>';
        $output .= '<link><![CDATA['.get_permalink($product->ID).']]></link>';

        $categories_output = '';
        $categories = get_the_terms( $product->ID, 'wpsc_product_category' );

        if(!empty($categories)){
            $i = 0;
            foreach($categories as $category){
                if($i != 0){
                    $categories_output .= ', ';
                }
                $categories_output .= get_category_breadcrumb($category);
                $i++;
            }
            $output .= '<cat_breadcumb><![CDATA['.$categories_output.']]></cat_breadcumb>';
        }

        $output .= '<description><![CDATA['.$product->post_content.']]></description>';
        $price = get_post_meta($product->ID,'_wpsc_price',true);
        if(!empty($price)){
            $output .= '<price><![CDATA['.get_post_meta($product->ID,'_wpsc_price',true).']]></price>';
        }

        $main_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->ID ), 'single-post-thumbnail' );
        if(!empty($main_image[0])){
            $output .= '<main_img><![CDATA['.$main_image[0].']]></main_img>';
        }

        $images = get_children('post_type=attachment&post_mime_type=image&output=ARRAY_N&orderby=menu_order&order=ASC&post_parent='.$product->ID);
        if(!empty($images)){
            $i = 0;
            foreach($images as $image){
                $output .= '<sub_img'.($i+1).'><![CDATA['.$image->guid.']]></sub_img'.($i+1).'>';
                $i++;
            }
        }
        $output .= '</item>';
    }
    $output = '<meta><date>'.date('Y-m-d H-i-s').'</date></meta>'.$output;
    file_put_contents(ABSPATH.'wp-content/productlisters.xml','<?xml version="1.0" encoding="utf-8" ?><items>'.$output.'</items>');
}
if(!defined('DOING_CRON'))
    add_action('wp_loaded','generate_xml');
else
    generate_xml ();

function get_category_breadcrumb($category){
    if($category->parent == 0){
        return $category->name;
    } else {
        $breadcrumb = $category->name;
        while($category->parent != 0){
            $category = get_term($category->parent, 'wpsc_product_category', 'OBJECT');
            $breadcrumb = $category->name.' | '.$breadcrumb;
        }
        return $breadcrumb;
    }
}
