<?php
/**
 * @package   ldd_directory_lite
 * @author    LDD Web Design <info@lddwebdesign.com>
 * @license   GPL-2.0+
 * @link      http://lddwebdesign.com
 * @copyright 2014 LDD Consulting, Inc
 * @wordpress-plugin
 * Plugin Name:       LDD Directory Lite
 * Plugin URI:        https://plugins.lddwebdesign.com
 * Description:       Powerful and simple to use, add a directory of business or other organizations to your web site. 
 * Version:           3.3
 * Author:            LDD Web Design
 * Author URI:        http://www.lddwebdesign.com
 * Author:            LDD Web Design
 * Author URI:        http://www.lddwebdesign.com
 * Text Domain:       ldd-directory-lite
 * Domain Path:       /languages/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('WPINC'))
    die;


/**
 * Define constants
 */
define('LDDLITE_VERSION', '3.3');

define('LDDLITE_PATH', dirname(__FILE__));
define('LDDLITE_URL', rtrim(plugin_dir_url(__FILE__), '/'));

define('LDDLITE_POST_TYPE', 'directory_listings');
define('LDDLITE_TAX_CAT', 'listing_category');
define('LDDLITE_TAX_TAG', 'listing_tag');

define('LDDLITE_PFX', 'lddlite');
define('LDDLITE_NOLOGO', plugin_dir_url(__FILE__) . 'public/images/noimage.png');

define('LDDLITE_INSTALL_DATE', 'lddlite-install-date');
define('LDDLITE_HIDE_NOTICE_KEY', 'lddlite-hide-notice');
define('LDDLITE_DELAY_NOTICE_KEY', 'lddlite-dalay-notice');
/*
 * Google Map Api Key Global
 * */


if(!(function_exists('get_user_to_edit'))){
	require_once(ABSPATH.'/wp-admin/includes/user.php');
}

if(!(function_exists('_wp_get_user_contactmethods'))){
	require_once(ABSPATH.'/wp-includes/registration.php');
}

/**
 * Flush the rewrites for custom post types
 */
register_activation_hook(__FILE__, 'install_ldd_directory_lite');
register_deactivation_hook(__FILE__, 'deactivate_ldd_directory_lite');


function install_ldd_directory_lite() {
	global $wp_rewrite;

    $ldl_settings = get_option('lddlite_settings', array());

    if (!isset($ldl_settings['directory_front_page'])) {
        $directory = wp_insert_post(array(
                                        'post_title'     => __('Directory', 'ldd-directory-lite'),
                                        'post_name'      => 'directory',
                                        'post_content'   => '[directory]',
                                        'post_status'    => 'publish',
                                        'post_type'      => 'page',
                                        'comment_status' => 'closed',
                                    ));

        $submit = wp_insert_post(array(
                                     'post_title'     => __('Submit a Listing', 'ldd-directory-lite'),
                                     'post_name'      => 'submit-listing',
                                     'post_content'   => '[directory_submit]',
                                     'post_status'    => 'publish',
                                     'post_type'      => 'page',
                                     'post_parent'    => $directory,
                                     'comment_status' => 'closed',
                                 ));

        $manage = wp_insert_post(array(
                                     'post_title'     => __('Manage Listings', 'ldd-directory-lite'),
                                     'post_name'      => 'manage-listings',
                                     'post_content'   => '[directory_manage]',
                                     'post_status'    => 'publish',
                                     'post_type'      => 'page',

                                     'post_parent'    => $directory,
                                     'comment_status' => 'closed',
                                 ));

        $ldl_settings['directory_front_page']  = $directory;
        $ldl_settings['directory_submit_page'] = $submit;
        $ldl_settings['directory_manage_page'] = $manage;
    }

    foreach (ldl_get_registered_settings() as $tab => $settings) {
        foreach ($settings as $option) {

            if ('checkbox' == $option['type'] && !empty($option['std'])) {
                $ldl_settings[ $option['id'] ] = '1';
            }

        }
    }

    update_option('lddlite_settings', $ldl_settings);

	/* Insert install date */
	$nag = new LDD_Nag();
	$nag->insert_install_date();

	// register post type before flushing
	ldl_register_post_type();
	$wp_rewrite->flush_rules( false );
}

/**
 * Fire functions after plugin deactivated
 */
function deactivate_ldd_directory_lite() {
	flush_rewrite_rules();
	/** @var  $ldl_settings */
	$ldl_settings = get_option('lddlite_settings', array());
	/**
	 * Force delete all listings pages created while plugin activation.
	 */
	wp_delete_post( $ldl_settings['directory_front_page'], true );
	wp_delete_post( $ldl_settings['directory_submit_page'], true );
	wp_delete_post( $ldl_settings['directory_manage_page'], true );
}


/**
 * Primary controller class, this handles set up for the entire plugin.
 *
 * @since the_beginning
 */
class ldd_directory_lite {

    private static $_instance = null;
    private $settings = array();


    /**
     * Return a single instance of the class responsible for setting up the plugin (also include functions.php here
     * so that we know some functionality is available prior to full init).
     *
     * @since 0.5.0
     * @return ldd_directory_lite An instance of the ldd_directory_lite class
     */
    public static function get_instance() {
        if (null === self::$_instance) {
            self::$_instance = new self;
            self::$_instance->load_plugin_textdomain();
            self::$_instance->include_files();
            self::$_instance->init();
           // self::$_instance->change_directory_user();
        }

        return self::$_instance;
    }


    /**
     * Handles all pre-ignition, including checking for any necessary upgrades and populating the settings property.
     *
     * @todo  Anonymous usage tracking back in before stable
     * @since 0.5.0
     */
    public function init() {

        add_action('init', array($this, 'load_plugin_textdomain'));
        add_action('init', array($this, 'change_directory_user'));

        // ldd business directory import
        $plugin = 'ldd-business-directory/lddbd_core.php';
		
		$dir = dirname(__FILE__);
        $plugin_path = WP_PLUGIN_DIR  . '/' . $plugin;

        if (file_exists($plugin_path) && false == get_option('lddlite_imported_from_original'))
            require_once(LDDLITE_PATH . '/import-lddbd.php');

        $this->settings = get_option('lddlite_settings');

        $version = get_option('lddlite_version');

        if (!$version) {
            update_option('lddlite_version', LDDLITE_VERSION);
        }
        else if ($version && LDDLITE_VERSION != $version) {
            global $upgrades;

            $upgrades = array(
                '0.6.0-beta' => false,
            );

            foreach ($upgrades as $upgrade => $trigger) {
                if (version_compare($version, $upgrade, '<')) {
                    $upgrade_available = true;
                    $upgrades[ $upgrade ] = true;
                }
            }

            if (isset($upgrade_available))
                require_once(LDDLITE_PATH . '/upgrade.php');

            update_option('lddlite_version', LDDLITE_VERSION);

        }

        if (ldl()->get_option('allow_tracking')) {
            add_action('init', array('ldd_directory_lite_tracking', 'get_instance'));
        }
         
$editor =wp_roles()->is_role( 'directory_contributor' );
if(!$editor){ 
    add_role( 'directory_contributor', 'Directory Contributor', array(
        'read'         => true,  // true allows this capability
        'edit_posts'   => true,
        'delete_posts' => true, // Use false to explicitly deny
    ));} 
    $role = get_role( 'directory_contributor' );
    $role->remove_cap( 'edit_published_posts' );
   
   

    }


    /**
     * Include all the files we'll need to function.
     *
     * @since 0.5.0
     */
    public function include_files() {

        require(LDDLITE_PATH . '/includes/admin/register-settings.php');
        require(LDDLITE_PATH . '/includes/admin/review.php');

        require(LDDLITE_PATH . '/includes/functions.php');
        require(LDDLITE_PATH . '/includes/setup.php');

        require(LDDLITE_PATH . '/includes/listings.php');
        require(LDDLITE_PATH . '/includes/ajax.php');
        require(LDDLITE_PATH . '/includes/template-functions.php');
        require(LDDLITE_PATH . '/includes/template-hooks.php');
        require(LDDLITE_PATH . '/includes/shortcodes/directory.php');
        require(LDDLITE_PATH . '/includes/shortcodes/_submit.php');
        require(LDDLITE_PATH . '/includes/shortcodes/_manage.php');
        require(LDDLITE_PATH . '/includes/shortcodes/categories.php');
        

        if (is_admin()) {
	        /**
	         * Make sure another plugin hasn't already done this, then initialize the CMB library.
	         */
	        if (  !defined( 'CMB2_LOADED') ){
		        require(LDDLITE_PATH . '/includes/cmb/init.php');
	        }
	        require(LDDLITE_PATH . '/includes/admin/setup.php');
            require(LDDLITE_PATH . '/includes/admin/metaboxes.php');
            require(LDDLITE_PATH . '/includes/admin/help.php');
            require(LDDLITE_PATH . '/includes/admin/display.php');
        }

    }


    /**
     * Loads the related i18n files into the appropriate domain.
     *
     * @since 0.5.0
     */
    public function load_plugin_textdomain() {
        $lang_dir = apply_filters('lddlite_languages_path', dirname(plugin_basename(__FILE__)) . '/languages/');
        load_plugin_textdomain('ldd-directory-lite', false, $lang_dir);
    }


    public function has_option($key) {
        return isset($this->settings[ $key ]);
    }


    /**
     * Gets a setting from the private $settings array and returns it. An empty string is returned if the setting
     * is not found in order to avoid triggering a false negative. Settings that may have a true|false value should
     * be explicitly tested.
     *
     * @since 0.5.3
     * @param string $key     Identify what setting is being requested
     * @param mixed  $default Provide a default if the setting is not found
     * @return mixed The value of the setting being requested
     */
    public function get_option($key, $default = '') {
        $value = !empty($this->settings[ $key ]) ? $this->settings[ $key ] : $default;
        $value = apply_filters('lddlite_get_option', $value, $key, $default);

        return apply_filters('lddlite_get_option_' . $key, $value, $key, $default);
    }


    /**
     * Update a setting and save it.
     *
     * @since 0.5.3
     * @param string $key   Identifies the setting being updated
     * @param mixed  $value The new value
     */
    public function update_option($key, $value = '') {

        if (empty($key))
            return;

        $old_value = !empty($this->settings[ $key ]) ? $this->settings[ $key ] : '';
        $value = apply_filters('lddlite_update_option', $value, $key, $old_value);
        $this->settings[ $value ] = apply_filters('lddlite_update_option_' . $key, $value, $key, $old_value);

        return update_option('lddlite_settings', $this->settings);

    }
   public  function change_directory_user(){
        global $wpdb;

            $sql = "SELECT `post_type`, `post_author`".
                " FROM {$wpdb->posts}".
                " WHERE `post_type` ='directory_listings'".
                " AND `post_status` IN ('publish', 'pending')";
                
                

            $posts = $wpdb->get_results( $sql );
        

        foreach( $posts as $post ) {
            $user_id = $post->post_author;
            $user_meta=get_userdata($user_id);

            $user_roles=$user_meta->roles[0];
            if($user_roles=='subscriber'){
                $u = new WP_User( $user_id );
                $u->remove_role( 'subscriber' );
                $u->add_role( 'directory_contributor' );
             }
            
        }

}

}

	/**
	 * An alias for the ldd_directory_lite get_instance() method.
	 *
	 * @return ldd_directory_lite The controller singleton
	 */
	function ldl() {
	    return ldd_directory_lite::get_instance();
	}


/*
 * =====================================
 * Custom Pagination for inner listings
 * =====================================
 * */

function ldd_pagination($pages = '', $range = 4) {
	global $paged;

	$showitems = ($range * 2)+1;

	if(empty($paged)) $paged = 1;

	if($pages == '')  {
		global $wp_query;
		$pages = $wp_query->max_num_pages;
		if(!$pages)  {
			$pages = 1;
		}
	}

	if(1 != $pages)  {
		echo "<div class=\" ldd_listing_pagination clearfix \"><span>Page ".esc_html($paged)." of ".esc_html($pages)."</span>";
		if($paged > 2 && $paged > $range+1 && $showitems < $pages) echo wp_kses_post("<a href='".get_pagenum_link(1)."'>&laquo; First</a>");
		if($paged > 1 && $showitems < $pages) echo wp_kses_post("<a href='".get_pagenum_link($paged - 1)."'>&lsaquo; Previous</a>");

		for ($i=1; $i <= $pages; $i++) {
			if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems )) {
				echo ($paged == $i)? wp_kses_post("<span class=\"current\">".$i."</span>"):wp_kses_post("<a href='".get_pagenum_link($i)."' class=\"inactive\">".$i."</a>");
			}
		}

		if ($paged < $pages && $showitems < $pages) echo wp_kses_post("<a href=\"".get_pagenum_link($paged + 1)."\">Next &rsaquo;</a>");
		if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) echo wp_kses_post("<a href='".get_pagenum_link($pages)."'>Last &raquo;</a>");
		echo  "</div>\n";
	}
}

function ldd_validate_dyn_slugs() {
	
	 $taxonomy_slug = ldl()->get_option('directory_taxonomy_slug', 'listings');
   	 $post_type_slug = ldl()->get_option('directory_post_type_slug', 'listing');
	 if(strtolower($taxonomy_slug) == strtolower($post_type_slug)):
		add_action( 'admin_notices', 'ldd_slugs_error_notice' ); 
	 endif;
}
function ldd_slugs_error_notice() {
	$class = "error";
	$message = "Error: Taxonomy and Post Type Slugs cannot be same. Please go to <a href='".admin_url()."edit.php?post_type=directory_listings&page=lddlite-settings'>settings</a> and update the slugs.";
        echo "<div class='".esc_attr($class)."'> <p>".esc_html($message)."</p></div>"; 
}
function ldd_validate_google_api_key() {
	$google_api_key = ldl()->get_option('googlemap_api_key');
	if(isset($google_api_key) and empty($google_api_key)):
		add_action( 'admin_notices', 'google_error_notice' );
	endif;
}
function google_error_notice() {
	if ( !ldl_use_google_maps() ) return false;
	$class = "error";
	$message = "Error: Google Map API is missing. Please go to <a href='".admin_url()."edit.php?post_type=directory_listings&page=lddlite-settings#lddlite_settings[googlemap_api_key]'>settings</a> and provide the Google Map API Key.";
	  echo "<div class='".esc_attr($class)."'> <p>".esc_html($message)."</p></div>"; 
}
add_action( 'admin_init', 'ldd_admin_hooks' );
function ldd_admin_hooks() {
	// setup nag
	$nag = new LDD_Nag();
	$nag->setup();
}

/** Das boot */
if (!defined('WP_UNINSTALL_PLUGIN'))
    ldl();
	//$fep = new USER_EDIT_FONT_PROFILE;
ldd_validate_dyn_slugs();
ldd_validate_google_api_key();

/*
 *====================================================
 * Update defualt search query for adding meta search
 *====================================================
 */

 
function ldd_meta_search_join ($join){
    global $wpdb;
	
		if( is_search() and $_REQUEST["post_type"] == "directory_listings") {
        	$join .=' LEFT JOIN '.$wpdb->postmeta.' wm ON '.$wpdb->posts.'.ID = wm.post_id ';
		}
	return $join;
}


function ldd_taxonomy_search_join ($join){
    global $wpdb;
	
		if( is_search() and $_REQUEST["post_type"] == "directory_listings") {
        	$join .= "LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id";
		}
	return $join;
}

function ldd_meta_search_where( $where ){
    global $wpdb;
		if( is_search() and $_REQUEST["post_type"] == "directory_listings") {
		  $where = preg_replace( "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
		   						 "(".$wpdb->posts.".post_title LIKE $1) OR (wm.meta_value LIKE $1)", $where );
		}
	return $where;
}

function ldd_atom_search_where($where){
  global $wpdb;
  if (is_search() and $_REQUEST["post_type"] == "directory_listings")
    $where .= "OR (t.name LIKE '%".get_search_query()."%' AND {$wpdb->posts}.post_type= 'directory_listings'  AND  {$wpdb->posts}.post_status = 'publish')";
  return $where;
}

function ldd_meta_search_groupby($groupby) {
  global $wpdb;

  if( !is_search() or $_REQUEST["post_type"] != "directory_listings") { return $groupby; }

  $customgroupby = "{$wpdb->posts}.ID";

  if( preg_match( "/$customgroupby/", $groupby )) { return $groupby; }

  if( !strlen(trim($groupby))) {
    return $customgroupby;
  }

  return $groupby . ", " . $customgroupby;
}
function myprefix_search_posts_per_page($query) {
    if ( $query->is_search and $_REQUEST["post_type"] == "directory_listings") {
        $posts_per_page  = ldl()->get_option( 'listings_search_number', 10 );
        $query->set( 'posts_per_page', $posts_per_page );
    }
    return $query;
}
function add_blog_post_to_query( $query ) {
    if (  $query->is_main_query() && is_tax('listing_category') ) {
        $query->set( 'post_type', array('directory_listings') );
        if(ldl()->get_option( 'listings_display_number') >0){
			
            $posts_per_page  = ldl()->get_option( 'listings_display_number', 10 );
		}
		else {
		$posts_per_page = 100;
		}
        $query->set( 'posts_per_page', $posts_per_page );
    }
}
function ldd_sort_custom( $orderby){
     if (!is_search() or $_REQUEST["post_type"] != "directory_listings"){
         return  $orderby;
     }
        
     
    global $wpdb;
    $sort_order = ldl()->get_option( 'search_listings_sort_order', 'asc' );
    $sort_by    = ldl()->get_option( 'search_listings_sort', 'business_name' );
    if($sort_by == "business_name"){
        $orderby = 'post_title';
    }
    elseif($sort_by == "date"){
        $orderby = 'post_date';
    }
    elseif($sort_by == "id"){
        $orderby = 'ID';
    }
   
        $orderby =  $wpdb->prefix."posts.post_type ASC, {$wpdb->prefix}posts.{$orderby} {$sort_order}";
        
return  $orderby;
    
     
}
//add_filter('pre_get_posts', 'laudes_order',99);
add_action( 'pre_get_posts', 'add_blog_post_to_query' );
add_filter( 'pre_get_posts','myprefix_search_posts_per_page', 99 );

add_filter('posts_join', 'ldd_taxonomy_search_join' );
add_filter('posts_where', 'ldd_meta_search_where' );
add_filter('posts_join', 'ldd_meta_search_join' );
add_filter('posts_where', 'ldd_atom_search_where' );
add_filter('posts_groupby', 'ldd_meta_search_groupby' );
add_filter('posts_orderby','ldd_sort_custom',10,2);



function ldd_remove_menu_items() {
    if ( !is_multisite() ){
        if( current_user_can( 'contributor' ) ):
            remove_menu_page( 'edit.php?post_type=directory_listings' );
        endif;
    }
}
add_action( 'admin_menu', 'ldd_remove_menu_items' );






add_filter('wp_dropdown_users', 'ldd_SwitchUser');
function ldd_SwitchUser($output)
{
   if(get_post_type()!="directory_listings" ){ return $output;}
	//if(!isset($_GET['action']) || $_GET['action']!='edit') {return $output;}
		global $post;
		//global $post is available here, hence you can check for the post type here
		$users = get_users(array('role__in'=>array("administrator",'editor','author','directory_contributor')));
	
		$output = "<select id=\"post_author_override\" name=\"post_author_override\" class=\"ldd_usr\">";
	
		//Leave the admin in the list
		//$output .= "<option value=\"1\">Admin</option>";
		foreach($users as $user)
		{
			//print_r($post);
			//echo  "here";
			$sel = ($post->post_author == $user->ID)?"selected":'';
		   $user_roles = $user->roles;
	
			
			$output .= '<option  value="'.$user->ID.'"'.$sel.'>'.$user->display_name.' ('. $user_roles[0].')</option>';
		}
		$output .= "</select>";
	
		return $output;
	
    
}

// add new user as directory contributor if he logs in from directory page
add_filter('pre_option_default_role','ldd_create_directory_user');

 function ldd_create_directory_user($default_role){
     if(isset($_GET['pt']) && $_GET['pt']=="directory_listing"){
    
    return 'directory_contributor'; // This is changed
     } else {
    return $default_role; // This allows default
     }
}

// Remove admin menus from directory contributors
add_action( 'admin_init', 'ldd_remove_menu_pages' );
function ldd_remove_menu_pages() {
    
    
   global $user_ID;

   if ( !is_multisite() ){
       
    
        if ( current_user_can( 'directory_contributor' ) ) {
            
            remove_menu_page( 'edit.php' );
            remove_menu_page( 'edit-comments.php' );
        
        }
    }
    
}

// restrict directory contributor from dashboard

function ldd_contributor_redirect(){
    if(ldl()->get_option( 'directory_contributor_access')=="no"){
        if ( !is_multisite() ){
            if( is_admin() && !defined('DOING_AJAX') && ( current_user_can('directory_contributor') ) ){
                wp_redirect(home_url());
                exit;
            }
        }
        if(current_user_can('directory_contributor')){
           
            add_action('wp_footer','ldd_show_prof');
                    
        add_filter('show_admin_bar', '__return_false');
        }
    }
  }
  add_action('init','ldd_contributor_redirect');

  