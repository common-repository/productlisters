<?php
$options = get_option('productlisters');
global $woocommerce;
$woocommerce->product_factory = new WC_Product_Factory();
$output = '';
$args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'order' => 'ASC'
            );

$products = get_posts($args);
foreach ($products as $product){
	$product->object = $woocommerce->product_factory->get_product($product->ID);

    $output .= '<item>';
    $output .= '<title><![CDATA['.$product->post_title.']]></title>';
    $output .= '<link><![CDATA['.get_permalink($product->ID).']]></link>';

    $categories_output = '';
    $categories = get_the_terms( $product->ID, 'product_cat' );
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
    $output .= '<price><![CDATA['.$product->object->get_price().']]></price>';

    $main_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->ID ), 'single-post-thumbnail' );
    if(!empty($main_image[0])){
         $output .= '<main_img><![CDATA['.$main_image[0].']]></main_img>';
    }

    $images = $product->object->get_gallery_attachment_ids();
    if(!empty($images)){
        $i = 0;
        foreach($images as $id){
            $image = get_post($id);
            $output .= '<sub_img'.($i+1).'><![CDATA['.$image->guid.']]></sub_img'.($i+1).'>';
            $i++;
        }
    }
    $output .= '</item>';
}
$output = '<meta><date>'.date('Y-m-d H-i-s').'</date></meta>'.$output;
file_put_contents(ABSPATH.'wp-content/productlisters.xml','<?xml version="1.0" encoding="utf-8" ?><items>'.$output.'</items>');

function get_category_breadcrumb($category){
    if($category->parent == 0){
        return $category->name;
    } else {
        $breadcrumb = $category->name;
        while($category->parent != 0){
            $category = get_term($category->parent, 'product_cat', 'OBJECT');
            $breadcrumb = $category->name.' | '.$breadcrumb;
        }
        return $breadcrumb;
    }
}