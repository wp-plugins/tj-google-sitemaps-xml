<?php
/*
Plugin Name: TJ Google Sitemaps XML
Plugin URI:  http://techjunkie.com
Description: Plugin for easily publishing sitemap.xml for Google
Author: Evan Gower
Version: 1.00
Author URI: http://techjunkie.com/
License: GPL
*/

class TJSitemap {
    //flush rewrite rules on activation
    function activate() {
        global $wp_rewrite;
	   $wp_rewrite->flush_rules();
    }
    
    //merge in the rewrite rule for sitemap.xml to existing rewrite rules
    function create_rewrite_rules($rules) {
        $newRule = array('sitemap.xml' => 'index.php?TJSitemap=sitemap');
        $newRules = array_merge($newRule, $rules);
        return $newRules;
    }

    //allow for the GET string TJSitemap to come through
    function add_query_vars($qvars) {
        $qvars[] = 'TJSitemap';
        return $qvars;
    }

    //intercept a template call to call this plugin's function instead
    function template_redirect_intercept() {
        global $wp_query;
        if ($wp_query->get('TJSitemap')) {
            $this->output();
            exit;
        }
    }

    //output the xml
    function output( ) {
	 $tj_sitemap_types = get_option('tj_sitemap_types');

	 $args = array(
	   'post_type' => $tj_sitemap_types,
	   'posts_per_page' => -1 
	 );

	 $sitemap_posts = new WP_Query($args);

	 echo '
<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="wp-content/plugins/google-sitemap-plugin/sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';

	 foreach($sitemap_posts->posts as $post){
	   $link = get_permalink($post->ID);
	   $xml_date = strtotime($post->post_date);
	   $xml_date = date('Y-m-d',$xml_date);
	   echo "\t<url>\n";
		echo "\t\t<loc>".$link."</loc>\n";
		echo "\t\t<lastmod>".$xml_date."</lastmod>\n";
          echo "\t\t<changefreq>monthly</changefreq>\n";
          echo "\t\t<priority>.5</priority>\n";
	   echo "\t</url>\n";
	 }
    echo '</urlset>';
    }

    //add an options page under the tools menu
    function options_menu(){
	 add_management_page( 'TJ Sitemaps', 'TJ Sitemaps', 'manage_options', 'tj-sitemap', array('TJSitemap', 'display_options' ) );
    }

    //show the options page
    function display_options(){
      global $wpdb;

      if ( !current_user_can( 'manage_options' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	 if( isset( $_POST['tj-sitemap'] ) ) {
	   $tj_sitemap_types = array();

	   foreach( $_POST as $key => $checks ) {
		if( substr($key,0,3) != "tj_" ) continue;
		$post_type = substr( $key,3 );
		$tj_sitemap_types[] = $post_type;
	   }

	   update_option('tj_sitemap_types', $tj_sitemap_types);
	 } 

	 $args = array(
	   'public' => 'true'
	 );

	 $post_types=get_post_types($args,'names'); 

	 $tj_sitemap_types = "";
	 $tj_sitemap_types = get_option('tj_sitemap_types');
	 ?>
      <form name="tj_sitemap_form" method="post" action="<?php menu_page_url('tj-sitemap'); ?>">
	 <input type="hidden" name="tj-sitemap" value="1">
	 <?php
	 $sitemap_url = site_url('/sitemap.xml');
	 echo '<h1>TJ Google Sitemaps XML</h1>';
	 echo '<p>The sitemap generated is generated according to Google\'s specifications.  Please select the post types that you would like to appear within the sitemap file below.  The sitemap will not function until post types have been selected.  <a href="'.$sitemap_url.'" target="_NEW">View Sitemap</a></p>';
	 echo '<table>';

	 foreach ($post_types as $post_type ) {
	   if( in_array( $post_type, $tj_sitemap_types ) ) $checked = "i CHECKED";
	   else $checked = "";
	   echo '<tr><td><input type="checkbox" value="1" name="tj_'.$post_type.'"'.$checked.'> '. $post_type. '</td></tr>';
	 }

	 echo '<tr><td colspan="3"><input type="submit" value="Save Changes"></td></tr>';

	 echo '</table></form>';
	 echo 'Brought to you by: <a href="http://www.techjunkie.com">Evan Gower</a>';
    }

}

$TJS = new TJSitemap();
register_activation_hook( __file__, array($TJS, 'activate') );
register_deactivation_hook( __file__, array($TJS, 'activate') );

add_filter( 'rewrite_rules_array', array( $TJS, 'create_rewrite_rules' ) );
add_filter( 'query_vars', array( $TJS, 'add_query_vars' ) );

add_action( 'admin_menu', array ($TJS, 'options_menu' ) );
add_action( 'template_redirect', array($TJS, 'template_redirect_intercept') );?>
