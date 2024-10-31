<?php
function generate_xml(){
    global $wpdb;
    $options = get_option('productlisters');
    $metatable=$wpdb->prefix.'postmeta';
	$poststable=$wpdb->prefix.'posts';
    $output = '';
    $myrowres=$wpdb->get_results("
		SELECT DISTINCT meta.post_id
		FROM $metatable as meta, $poststable as posts
		WHERE meta.meta_key = '_eshop_product'
		AND posts.ID = meta.post_id
		AND posts.post_status != 'trash' AND posts.post_status != 'revision'
		ORDER BY meta.post_id");

    //add in post id( doh! )
    foreach($myrowres as $row){
        $grabit[$x]=maybe_unserialize(get_post_meta( $row->post_id, '_eshop_product',true ));//get_post_custom($row->post_id);
        $grabit[$x]['_eshop_stock']=get_post_meta( $row->post_id, '_eshop_stock',true);//get_post_custom($row->post_id);
        $grabit[$x]['id']=$row->post_id;
        $grabit[$x]['_featured']='1';
        $grabit[$x]['_stock']='1';

        if(strtolower($grabit[$x]['featured'])=='yes') $grabit[$x]['_featured']='0';
        if(strtolower($grabit[$x]['_eshop_stock'])=='1') $grabit[$x]['_stock']='0';
        $x++;
    }

    $array=$grabit;
    $products=subval_sort($array,$sortby);
    foreach ($products as $eshop_product){
        $product = get_post($eshop_product['id']);

        $output .= '<item>';
        $output .= '<title><![CDATA['.$product->post_title.']]></title>';
        $output .= '<link><![CDATA['.get_permalink($product->ID).']]></link>';

        $categories_output = '';
        $categories = wp_get_post_categories( $product->ID,array('fields' => 'all'));
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

        $eshop_product = maybe_unserialize(get_post_meta( $product->ID, '_eshop_product',true ));
        if(!empty($eshop_product['products'][1]['price'])){
            $output .= '<price><![CDATA['.$eshop_product['products'][1]['price'].']]></price>';        //!!!!!!!!!!!!!!!!!!!!!!!!
        }

        $main_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->ID ), 'single-post-thumbnail' );
        if(!empty($main_image[0])){
            $output .= '<main_img><![CDATA['.$main_image[0].']]></main_img>';
        }

        $images = get_children( 'post_type=attachment&post_mime_type=image&output=ARRAY_N&orderby=menu_order&order=ASC&post_parent='.$product->ID);
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
            $category = get_term($category->parent, 'category', 'OBJECT');
            $breadcrumb = $category->name.' | '.$breadcrumb;
        }
        return $breadcrumb;
    }
}
