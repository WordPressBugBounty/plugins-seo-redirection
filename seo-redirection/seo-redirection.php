<?php
/*
Plugin Name: SEO Redirection
Plugin URI: https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/
Description: By this plugin you can manage all your website redirection types easily.
Author: wp-buy
Version: 9.11
Author URI: https://www.wp-buy.com
Text Domain: seo-redirection
*/

require_once('common/controls.php');
require_once('custom/controls.php');
require_once('custom/controls/cf.SR_redirect_cache.class.php');

define('WPSR_PATH', plugin_dir_path(__FILE__));

if (!defined('WPSR_URL')) define('WPSR_URL', plugin_dir_url(__FILE__));


if (!defined('WP_SEO_REDIRECTION_OPTIONS')) {
    define('WP_SEO_REDIRECTION_OPTIONS', 'wp-seo-redirection-group');
}

if (!defined('WP_SEO_REDIRECTION_VERSION')) {
    define('WP_SEO_REDIRECTION_VERSION', 9.4);
}

$util = new clogica_util_1();
$util->init(WP_SEO_REDIRECTION_OPTIONS, __FILE__);


add_action('admin_enqueue_scripts', 'WPSR_header_code');
add_action('admin_menu', 'WPSR_admin_menu');
add_action('wp', 'WPSR_redirect', 1);
add_action('save_post', 'WPSR_get_post_redirection');
add_action('add_meta_boxes', 'WPSR_adding_custom_meta_boxes', 10, 3);
add_action('admin_head', 'WPSR_check_default_permalink');
add_action('plugins_loaded', 'WPSR_upgrade');

register_activation_hook(__FILE__, 'WPSR_upgrade');
register_uninstall_hook(__FILE__, 'WPSR_uninstall');

/////////////////////////////////////////////////////////////////////////

if (!function_exists("WPSR__filter_action_links")) {
    function WPSR__filter_action_links($links)
    {
        $links['settings'] = sprintf('<a href="%s">Settings</a>', admin_url('options-general.php?page=seo-redirection.php'));
        $network_dir_append = "";
        if (is_multisite()) $network_dir_append = "network/";
        $links['MorePlugins'] = sprintf('<a href="%s"><b style="color:#f18500">More Plugins</b></a>', admin_url($network_dir_append . 'plugin-install.php?s=wp-buy&tab=search&type=author'));
        return $links;
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WPSR__filter_action_links', 10, 1);
}

/////////////////////////////////////////////////////////////////////////
if (!function_exists("WPSR_add_link_to_admin_bar")) {
    function WPSR_add_link_to_admin_bar($wp_admin_bar)
    {

        if (current_user_can('manage_options')) {

            global $wpdb;

            if (!is_admin_bar_showing()) {
                return;
            }

            $table_name = $wpdb->prefix . 'WP_SEO_Redirection';

            $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $relative_url = str_replace(home_url(), '', $current_url);

            $wp_admin_bar->add_node(array(
                'id'    => 'seo_redirection',
                'title' => '<img src="' . plugins_url('icon.png', __FILE__) . '" style="width: 20px; height: 20px; vertical-align: middle;" alt="Icon" /> SEO Redirection',
                'href'  => '#',
                'meta'  => array(
                    'class' => 'seo-redirection-admin-bar',  // Add a custom CSS class
                ),
            ));


            // Check if we are on an admin page or not
            if (is_admin()) {
                // Fetch the total number of 404 errors
                $total_404_errors = WPSR_Get_total_404();
                // Format the number with commas for readability
                $formatted_404_errors = number_format($total_404_errors);

                // Admin pages sub-nodes
                $wp_admin_bar->add_node(array(
                    'id'     => 'manage_redirects',
                    'title'  => 'Manage Redirects',
                    'href'   => admin_url('options-general.php?page=seo-redirection.php&tab=cutom'),
                    'parent' => 'seo_redirection',
                ));

                $wp_admin_bar->add_node(array(
                    'id'     => 'post_redirects',
                    'title'  => 'Post Redirects',
                    'href'   => admin_url('options-general.php?page=seo-redirection.php&tab=posts'),
                    'parent' => 'seo_redirection',
                ));

                $wp_admin_bar->add_node(array(
                    'id'     => 'history_redirects',
                    'title'  => 'History',
                    'href'   => admin_url('options-general.php?page=seo-redirection.php&tab=history'),
                    'parent' => 'seo_redirection',
                ));

                // Add the 404 errors node with a dynamic total and red background
                $wp_admin_bar->add_node(array(
                    'id'     => 'manage_404_errors',
                    'title'  => '404 Errors (' . $formatted_404_errors . ')',  // Display number of 404 errors with formatting
                    'href'   => admin_url('options-general.php?page=seo-redirection.php&tab=404'),
                    'parent' => 'seo_redirection',
                    'meta'   => array(
                        'class' => 'wpsr-404-errors-admin-bar',  // Custom class for styling
                    ),
                ));
            } else {
                // Front-end sub-nodes (same as your original function)
                $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE redirect_from = %s", $relative_url));

                if (!empty($result)) {
                    $wp_admin_bar->add_node(array(
                        'id'     => 'edit_redirection',
                        'title'  => 'Edit Redirection',
                        'href'   => admin_url('options-general.php?page=seo-redirection.php&tab=cutom&redirect_from=' . $relative_url),
                        'parent' => 'seo_redirection',
                    ));

                    $wp_admin_bar->add_node(array(
                        'id'     => 'view_history',
                        'title'  => 'View History',
                        'href'   => admin_url('options-general.php?page=seo-redirection.php&tab=history'),
                        'parent' => 'seo_redirection',
                    ));
                } else {
                    $wp_admin_bar->add_node(array(
                        'id'     => 'add_redirection',
                        'title'  => 'Redirect this page',
                        'href'   => admin_url('options-general.php?page=seo-redirection.php&tab=cutom&redirect_from=' . $relative_url),
                        'parent' => 'seo_redirection',
                    ));
                }
            }
        }
    }

    add_action('admin_bar_menu', 'WPSR_add_link_to_admin_bar', 999);
}
/////////////////////////////////////////////////////////////////////////

function custom_admin_bar_styles()
{
    if (is_admin_bar_showing()) {
        echo '<style>
            #wp-admin-bar-seo_redirection > .ab-item {
                background-color: #114e0e; /* Light green background color */
                color: white;
                display: flex;
                align-items: center;
                padding: 0 10px; /* Add padding for better alignment */
            }

            #wp-admin-bar-seo_redirection > .ab-item:hover {
                background-color: #7ccd7c; /* Darker green on hover */
            }

            .wpsr-404-notice {
                border-left: 4px solid red;
                padding: 15px;
                background-color: #ffebe6; /* Light red background */
            }

            .wpsr-404-notice .button-primary {
                background-color: orange;
                border-color: orange;
            }

            #wp-admin-bar-seo_redirection .seo-redirection-icon {
                width: 20px !important;  /* Resize the image to a smaller size */
                height: 20px !important; /* Keep it proportional */
                vertical-align: middle; /* Align icon vertically */
            }

            #wp-admin-bar-seo_redirection .ab-item .seo-redirection-icon {
                max-width: 100%;
                max-height: 100%; 
            }

            /* Custom style for the 404 node with red background */
            #wp-admin-bar-manage_404_errors > .ab-item {
                background-color: red; /* Red background for the 404 node */
                color: white;          /* White text color */
                font-weight: bold;     /* Bold text to highlight the 404 count */
            }

            #wp-admin-bar-manage_404_errors > .ab-item:hover {
                background-color: darkred; /* Darker red on hover */
            }
          </style>';
    }
}

// Apply styles to the front-end
add_action('wp_head', 'custom_admin_bar_styles');

// Apply styles to the admin dashboard
add_action('admin_head', 'custom_admin_bar_styles');



/////////////////////////////////////////////////////////////////////////
if (!function_exists('wpsr_dashboard_notice')) {

    function wpsr_dashboard_notice()
    {
        // Get the total number of 404 errors
        $total_404_errors = WPSR_Get_total_404();

        // Only show the notice if there are more than 100 broken links and if the user has not dismissed it
        if ($total_404_errors > 100 && !get_user_meta(get_current_user_id(), 'wpsr_404_notice_dismissed')) {
            // Image URL for the icon (Replace with your own uploaded image or the default WordPress icon)
            $icon_url = plugins_url('icon.png', __FILE__);

            // Message content with buttons
            $message = __('<strong>SEO Redirection</strong>: You have', 'seo-redirection') . ' <b style="color:red;     padding:3px;">' . intval($total_404_errors) . '</b>' . __(' broken links (404). Manage them now to fix the issue and improve your site\'s SEO performance.', 'seo-redirection');

            // Display the message with inline styles
            echo '
            <div class="notice notice-error is-dismissible wpsr-404-notice" style="border-left: 4px solid red;  display: flex; align-items: center;">
                <div style="display: flex; align-items: center;">
                    <img src="' . esc_url($icon_url) . '" style="margin-right: 15px; width: 40px; height: 40px;" alt="Error icon" />
                    <div>
                        <p>' . $message . '</p>
                        <p>
                            <a href="' . admin_url('options-general.php?page=seo-redirection.php&tab=404') . '" class="button button-primary" style="background-color: green; border-color: green; margin-right: 10px;">' . __('Fix Now', 'seo-redirection') . '</a>
                            <button type="button" class="button button-secondary wpsr-dismiss-notice" style="border-color: #0073aa; color: #0073aa;">' . __('Dismiss', 'seo-redirection') . '</button>
                        </p>
                    </div>
                </div>
            </div>
            ';

            // Add the script for handling the dismiss action
            echo '
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $(\'.wpsr-dismiss-notice\').on(\'click\', function() {
                        var ajaxurl = "' . admin_url('admin-ajax.php') . '";
                        $.post(ajaxurl, {
                            action: "wpsr_dismiss_404_notice",
                            user_id: ' . get_current_user_id() . '
                        });
                        $(this).closest(\'.wpsr-404-notice\').remove();
                    });
                });
            </script>
            ';
        }
    }
    add_action('admin_notices', 'wpsr_dashboard_notice');
}


if (!function_exists('wpsr_dismiss_404_notice')) {

    function wpsr_dismiss_404_notice()
    {
        // Update the user meta to mark the notice as dismissed
        update_user_meta(get_current_user_id(), 'wpsr_404_notice_dismissed', true);
        wp_die(); // This is required to terminate immediately and return a proper response
    }
    add_action('wp_ajax_wpsr_dismiss_404_notice', 'wpsr_dismiss_404_notice');
}
//////////////////////////////////////////////////////////////////////////////////////////////////   
if (!function_exists("WPSR_multiple_plugin_activate_trial")) {
    function WPSR_multiple_plugin_activate_trial()
    {
        global $wpdb;
        if (is_multisite()) {
            if (is_plugin_active_for_network(__FILE__)) {
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                }
            }
        }
    }

    register_activation_hook(__FILE__, 'WPSR_multiple_plugin_activate_trial');
}

if (!function_exists("WPSR_adding_custom_meta_boxes")) {

    function WPSR_adding_custom_meta_boxes()
    {
        global $util;
        if ($util->get_option_value('show_redirect_box') == '1') {

            $screens = array('post', 'page');

            foreach ($screens as $screen) {

                add_meta_box(
                    'WPSR_meta_box',
                    __('SEO Redirection'),
                    'WPSR_render_meta_box',
                    $screen
                );
            }
        }
    }
}
if (!function_exists("WPSR_render_meta_box")) {

    function WPSR_render_meta_box($post)
    {
        global $wpdb, $table_prefix, $util;
        $table_name = $table_prefix . 'WP_SEO_Redirection';

        if (get_post_status() != 'auto-draft') {
            $permalink = "";
            if (in_array($post->post_status, array('draft', 'pending'))) {
                list($permalink, $postname) = get_sample_permalink($post->ID);
                $permalink = str_replace('%postname%', $postname, $permalink);
            } else {

                $permalink = get_permalink($post->ID);
            }

            $permalink = $util->make_relative_url(urldecode($permalink));

            $postID = $post->ID;


            $theurl = $wpdb->get_row($wpdb->prepare(" select redirect_to,redirect_from from $table_name where postID=%d  ", $postID));

            $urlredirect_to = '';
            if ($wpdb->num_rows > 0)
                $urlredirect_to = $theurl->redirect_to;

            if ($urlredirect_to != '' && $theurl->redirect_from != $permalink) {
                // the post_name field changed!
                $wpdb->query($wpdb->prepare(" update $table_name set redirect_from=%s  where postID=%d ", $permalink, $postID));
                if ($util->get_option_value('reflect_modifications') == '1') {
                    $wpdb->query($wpdb->prepare(" update $table_name set redirect_to=%s  where redirect_to=%s ", $permalink, $theurl->redirect_from));
                    $util->info_option_msg('<b>' . __("SEO Redirection", 'seo-redirection') . '</b>' . __('has detected a change in Permalink, this will be reflected to the redirection records!', 'seo-redirection'));
                }
                //-------------------------------------------
            }

            echo '
<table border="0" width="100%" cellpadding="2">
	<tr>
		<td width="99%"><input onchange="redirect_check_click()" type="checkbox" name="redirect_check"  id="redirect_check" value="ON">
		Redirect&nbsp;<font id="wp_seo_redirection_url_from_label" color="#008000">' . esc_html($permalink) . '</font><input type="hidden" ID="wp_seo_redirection_url_from" name="wp_seo_redirection_url_from" value="' . esc_attr($permalink) . '"></td>
	</tr>
</table>
<div id="redirect_frame">
<table border="0" width="100%" cellpadding="2">
	<tr>
		<td>

		<b>' . __(" Redirect to", 'seo-redirection') . '</b><input type="text" name="wp_seo_redirection_url" id="wp_seo_redirection_url" value="' . esc_attr($urlredirect_to) . '" size="62"></td>
	</tr>
	<tr>
		<td>
		<ul>
			<li>' . __(" To make a redirection, put the", 'seo-redirection') . ' <b>' . __("URL", 'seo-redirection') . '</b> ' . __("in the text field above and then click the button  ", 'seo-redirection') . '<b>' . __("Update", 'seo-redirection') . '</b>.</li>
			<li>' . __("If you have a caching plugin installed, clear cache to reflect the
			changes immediately.", 'seo-redirection') . '</li>

			<li>' . __("To remove the redirection, just uncheck the check box above and then click the button", 'seo-redirection') . ' <b>' . __("Update", 'seo-redirection') . '</b>.</li>
		</ul>
		</td>
	</tr>
</table>
</div>';

            echo "

<script type='text/javascript'>
function WSR_check_status(x)
{

        if(x==0)
	{
		document.getElementById('redirect_check').checked=false;
		document.getElementById('redirect_frame').style.display = 'none';
		document.getElementById('wp_seo_redirection_url').value='';
	}else
	{
	   	document.getElementById('redirect_check').checked=true;
	   	document.getElementById('redirect_frame').style.display= 'block';
	}

}

function redirect_check_click()
{
	if(document.getElementById('redirect_check').checked)
	WSR_check_status(1);
	else
	WSR_check_status(0);
}
</script>
";

            if ($urlredirect_to == '')
                echo "<script type='text/javascript'>WSR_check_status(0);</script>";
            else
                echo "<script type='text/javascript'>WSR_check_status(1);</script>";
        } else {
            echo __('You can not make a redirection for the new posts before saving them.', 'seo-redirection');
        }
    }
}

//--------------------------------------------------------------------------------------------

//---------------------------------------------------------------
// added 2/2/2020
if (!function_exists("WPSR_get_site_404_page_path")) {

    function WPSR_get_site_404_page_path()
    {
        $url = str_ireplace("://", "", site_url());
        $site_404_page = substr($url, stripos($url, "/"));

        if (stripos($url, "/") === FALSE || $site_404_page == "/")
            $site_404_page = "/index.php?error=404";
        else
            $site_404_page = $site_404_page . "/index.php?error=404";

        return $site_404_page;
    }
}
//---------------------------------------------------------------
// updated 2/2/2020

function WPSR_check_default_permalink()
{
    $file = get_home_path() . "/.htaccess";
    $content = "ErrorDocument 404 " . WPSR_get_site_404_page_path();

    $marker_name = "FRedirect_ErrorDocument";
    $filestr = "";


    if (is_readable($file)) {
        $f = @fopen($file, 'r+');
        if ($f !== false) {
            $filestr = @fread($f, filesize($file));
            if (strpos($filestr, $marker_name) === false) {
                insert_with_markers($file,  $marker_name,  $content);
            }
        }
    } else {
        echo $file . ' is not readable!';
    }
}

//------------------------------------------------------------------------


/**
 * Recursive sanitation for text or array
 * 
 * @param $array_or_string (array|string)
 * @since  0.1
 * @return mixed
 */
if (!function_exists("WPSR_sanitize_text_or_array_field")) {

    function WPSR_sanitize_text_or_array_field($array_or_string)
    {
        if (is_string($array_or_string)) {
            $array_or_string = sanitize_text_field($array_or_string);
        } elseif (is_array($array_or_string)) {
            foreach ($array_or_string as $key => &$value) {
                if (is_array($value)) {
                    $value = WPSR_sanitize_text_or_array_field($value);
                } else {
                    $value = sanitize_text_field($value);
                }
            }
        }

        return $array_or_string;
    }
}
if (!function_exists("WPSR_get_post_redirection")) {

    function WPSR_get_post_redirection($post_id)
    {

        global $wpdb, $util, $table_prefix;
        $table_name = $table_prefix . 'WP_SEO_Redirection';

        // Autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        // AJAX
        if (defined('DOING_AJAX') && DOING_AJAX)
            return;
        // Post revision
        if (false !== wp_is_post_revision($post_id))
            return;

        $redirect_from = isset($_POST['wp_seo_redirection_url_from']) ? WPSR_sanitize_text_or_array_field($_POST['wp_seo_redirection_url_from']) : '';
        $redirect_to = isset($_POST['wp_seo_redirection_url']) ? WPSR_sanitize_text_or_array_field($_POST['wp_seo_redirection_url']) : '';

        if ($redirect_to != '') {


            $wpdb->get_results($wpdb->prepare("select ID from $table_name where postID=%d ", $post_id));

            if ($wpdb->num_rows > 0) {

                $sql = $wpdb->prepare("update $table_name set redirect_to=%s,redirect_from=%s,redirect_type='301',url_type=2 where postID=%d", $redirect_to, $redirect_from, $post_id);
                $wpdb->query($sql);
            } else {
                $wpdb->query($wpdb->prepare("delete from $table_name where redirect_from=%s", $redirect_from));
                $sql = $wpdb->prepare("insert into $table_name(redirect_from,redirect_to,redirect_type,url_type,postID) values (%s,%s,'301',2,%d) ", $redirect_from, $redirect_to, $post_id);
                $wpdb->query($sql);
            }
        } else {
            $wpdb->query($wpdb->prepare("delete from $table_name where postID=%d", $post_id));
        }

        $SR_redirect_cache = new free_SR_redirect_cache();
        $SR_redirect_cache->free_cache();
    }
}
//-------------------------------------------------------------
if (!function_exists("WPSR_log_404_redirection")) {

    function WPSR_log_404_redirection($link)
    {
        global $wpdb, $table_prefix, $util;
        $table_name = $table_prefix . 'WP_SEO_404_links';

        $referrer = $util->get_ref();
        $ip = $util->get_visitor_IP();
        $country = ""; //$util->get_visitor_country();
        $os = $util->get_visitor_OS();
        $browser = $util->get_visitor_Browser();

        if ($os != 'Unknown' || $browser != 'Unknown') {
            $wpdb->query($wpdb->prepare(" insert IGNORE into $table_name(ctime,link,referrer,ip,country,os,browser) values(NOW(),%s,%s,%s,%s,%s,%s) ", $link, $referrer, $ip, $country, $os, $browser));
        }
    }
}
//-------------------------------------------------------------
if (!function_exists("WPSR_log_redirection_history")) {

    function WPSR_log_redirection_history($rID, $postID, $rfrom, $rto, $rtype, $rsrc)
    {
        global $wpdb, $table_prefix, $util;
        $SR_redirect_cache = new free_SR_redirect_cache();
        $SR_redirect_cache->free_cache();
        $table_name = $table_prefix . 'WP_SEO_Redirection_LOG';
        $rfrom = esc_url($rfrom);
        $referrer = $util->get_ref();
        $ip = $util->get_visitor_IP();
        $country = ""; //$util->get_visitor_country();
        $os = $util->get_visitor_OS();
        $browser = $util->get_visitor_Browser();

        $wpdb->query($wpdb->prepare(" insert into $table_name(rID,postID,rfrom,rto,rtype,rsrc,ctime,referrer,ip,country,os,browser) values(%d ,%d,%s,%s,%s,%s,NOW(),%s,%s,%s,%s,%s) ", $rID, $postID, $rfrom, $rto, $rtype, $rsrc, $referrer, $ip, $country, $os, $browser));

        $limit = $util->get_option_value('history_limit');

        $expdate = date('Y-n-j', time() - (intval($limit) * 24 * 60 * 60));
        $wpdb->query("delete FROM $table_name WHERE date_format(date(ctime),'%Y-%m-%d') < date_format(date('$expdate'),'%Y-%m-%d')");
    }
}
//-------------------------------------------------------------
if (!function_exists("WPSR_make_redirect")) {

    function WPSR_make_redirect($redirect_to, $redirect_type, $redirect_from, $obj = '')
    {

        global $wpdb, $util, $table_prefix, $post;

        if (is_admin()) {
            return 0;
        }
        $SR_redirect_cache = new free_SR_redirect_cache();
        if ($redirect_to == $redirect_from || !$util->is_valid_url($redirect_to))
            return 0;

        if ($util->make_relative_url($redirect_from) == $util->make_relative_url($redirect_to))
            return 0;

        if (substr($redirect_from, -1) == "/" || substr($redirect_to, -1) == "/") {
            if (substr($redirect_from, -1) != "/") {
                if (($redirect_from . "/") == $redirect_to)
                    return 0;
            } else {
                if (($redirect_to . "/") == $redirect_from)
                    return 0;
            }
        }


        if ($util->make_relative_url($redirect_from) == $util->make_relative_url($redirect_to))
            return 0;

        if (is_object($obj)) {
            if ($obj->ID > 0) {
                $table_name = $table_prefix . 'WP_SEO_Redirection';
                $sql = "update " . $table_name . " set hits=hits+1, access_date= NOW() where ID='" . $obj->ID . "'";
                $wpdb->query($sql);
            }
        }

        if (is_object($obj) && $obj->redirect_to_type == 'Folder' && $obj->redirect_to_folder_settings == '2') {

            if ($obj->redirect_from_type == 'Folder') {

                if ($obj->redirect_from_folder_settings == '2' || $obj->redirect_from_folder_settings == '3') {
                    if (strlen($redirect_from) > strlen($obj->redirect_from)) {
                        $difference = substr($redirect_from, intval(strlen($obj->redirect_from) - strlen($redirect_from)));
                        $redirect_to = $redirect_to . $difference;
                    }
                }
            } else if ($obj->redirect_from_type == 'Regex') {
                $page = substr(strrchr($redirect_from, "/"), 1);
                $redirect_to = $redirect_to . '/' . $page;
            }
        }

        $rID = 0;
        $rsrc = '404';
        $postID = 0;

        if (is_object($obj)) {
            $rID = $obj->ID;
            $postID = $obj->postID;
            if ($obj->url_type == 1)
                $rsrc = 'Custom';
            else if ($obj->url_type == 2)
                $rsrc = 'Post';
        }

        if ($util->get_option_value('history_status') == '1') {

            WPSR_log_redirection_history($rID, $postID, $redirect_from, $redirect_to, $redirect_type, $rsrc);
        }

        $redirect_to = $util->make_absolute_url($redirect_to);


        if (is_singular()) {
            //$SR_redirect_cache = new free_SR_redirect_cache();

            $SR_redirect_cache->add_redirect($post->ID, 1, $redirect_from, $redirect_to, $redirect_type);
            $SR_redirect_cache->free_cache();
        }

        if ($redirect_type == '301') {
            header('HTTP/1.1 301 Moved Permanently');
            header("Location: " . $redirect_to);
            exit();
        } else if ($redirect_type == '307') {
            header('HTTP/1.0 307 Temporary Redirect');
            header("Location: " . $redirect_to);
            exit();
        } else if ($redirect_type == '302') {
            header("Location: " . $redirect_to);
            exit();
        }
    }
}
add_filter('pre_get_table_charset', function ($charset, $table) {
    global $table_prefix;
    $table_name = $table_prefix . 'WP_SEO_Redirection';
    if ($table == $table_name) {
        return 'utf8mb4';
    }
}, 10, 2);
//-------------------------------------------------------------
if (!function_exists("WPSR_redirect")) {

    function WPSR_redirect()
    {
        global $wpdb, $post, $table_prefix, $util;


        if ($util->get_option_value('plugin_status') != '0') { // if not disabled

            // if disable for admin and the user is admin
            if (current_user_can('manage_options') == 1 && $util->get_option_value('plugin_status') == 2) {
                // nothing

            } else {

                $table_name = $table_prefix . 'WP_SEO_Redirection';
                $permalink = urldecode($util->get_current_relative_url());

                if (substr($permalink, 0, 1) == ":") {
                    $first_slash = stripos($permalink, "/");
                    $permalink = substr($permalink, $first_slash, strlen($permalink) - $first_slash);
                }
                $permalink_alternative = "";
                if (substr($permalink, -1) == '/') {
                    $permalink_alternative = substr($permalink, 0, intval(strlen($permalink) - 1));
                } else {
                    $permalink_alternative = $permalink . '/';
                }

                $post_cache_result = "";
                $SR_redirect_cache = new free_SR_redirect_cache();
                if (is_singular()) {
                    $post_cache_result = $SR_redirect_cache->redirect_cached($post->ID);
                }

                if ($post_cache_result == 'not_redirected') {

                    return 0;
                }

                $permalink_options = $wpdb->prepare("( redirect_from = %s OR redirect_from = %s)", $permalink, $permalink_alternative);

                $permalink_regex_options = $wpdb->prepare("(%s regexp regex or %s regexp regex )", $permalink, $permalink_alternative);


                if (($util->get_option_value('redirect_control_panel') != '1') || ($util->get_option_value('redirect_control_panel') == '1' && !preg_match('/^' . str_replace('/', '\/', get_admin_url()) . '/i', $permalink) && !preg_match('/^' . str_replace('/', '\/', site_url()) . '\/wp-login.php/i', $permalink))) {

                    $theurl = $wpdb->get_row(" select * from $table_name where enabled=1 and regex='' and $permalink_options  ");
                    if ($wpdb->num_rows > 0 && $theurl->redirect_to != '') {
                        WPSR_make_redirect($theurl->redirect_to, $theurl->redirect_type, $permalink, $theurl);
                    }

                    $theurl = $wpdb->get_row(" select * from $table_name where enabled=1 and regex<>'' and $permalink_regex_options order by LENGTH(regex) desc ");

                    if ($wpdb->num_rows > 0 && $theurl->redirect_to != '') {
                        WPSR_make_redirect($theurl->redirect_to, $theurl->redirect_type, $permalink, $theurl);
                    }


                    if (is_404()) {

                        if ($util->get_option_value('p404_discovery_status') == '1') {
                            WPSR_log_404_redirection($permalink);
                        }

                        $options = $util->get_my_options();
                        if ($options['p404_status'] == '1') {

                            WPSR_make_redirect($options['p404_redirect_to'], '301', $permalink);
                        }
                    }
                }

                if (is_singular() && $post_cache_result == 'not_found') {
                    $SR_redirect_cache->add_redirect($post->ID, 0, '', '', 0);
                }
            }
        }
    }
}
//---------------------------------------------------------------
if (!function_exists("WPSR_header_code")) {

    function WPSR_header_code()
    {

        $my_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
        if ($my_page == 'seo-redirection.php') {

            wp_register_style('c_admin_css_common', plugins_url() . '/' . basename(dirname(__FILE__)) . '/common/' . "style.css");
            wp_enqueue_style('sweetalert', plugins_url() . '/' . basename(dirname(__FILE__)) . '/common/' . "sweetalert.css");
            wp_register_style('c_admin_css_custom', plugins_url() . '/' . basename(dirname(__FILE__)) . '/custom/' . "style.css");
            wp_enqueue_script('jquery');
            wp_localize_script('jquery', 'seoredirection', array('ajax_url' => admin_url('admin-ajax.php'), 'msg' => ""));
            wp_enqueue_style('c_admin_css_common');
            $rand = rand(1, 99999999999);
            wp_register_style('c_css_common', plugins_url() . '/' . basename(dirname(__FILE__)) . '/common/' . "custom.css", '', $rand);
            wp_enqueue_style('c_css_common');
            wp_enqueue_style('c_admin_css_custom');

            wp_enqueue_script('custom', plugins_url() . '/' . basename(dirname(__FILE__)) . '/common/js/' . 'bootstrap.min.js', array('jquery'), false, true);
            wp_enqueue_script('customJS', plugins_url() . '/' . basename(dirname(__FILE__)) . '/common/' . "customJs.js", array('jquery'), 1.2, true);
            wp_enqueue_script('sweetalert', plugins_url() . '/' . basename(dirname(__FILE__)) . '/common/js/' . "sweetalert.min.js", array('jquery'), false, true);

            wp_enqueue_style('bootstrap', plugins_url() . '/' . basename(dirname(__FILE__)) . '/common/' . "bootstrap.css");
        }
    }
}
//---------------------------------------------------------------
if (!function_exists("WPSR_customAddUpdate_callback")) {

    add_action("wp_ajax_customAddUpdate", "WPSR_customAddUpdate_callback");

    function WPSR_customAddUpdate_callback()
    {
        global $wpdb, $table_prefix, $util;
        $table_name = $table_prefix . 'WP_SEO_Redirection';
        $table_name_404 = $table_prefix . 'WP_SEO_404_links';
        parse_str($_POST['formData'], $_POST);
        $nonce = "";
        if (isset($_POST['_wpnonce']))
            $nonce = WPSR_sanitize_text_or_array_field($_POST['_wpnonce']);
        $data = array();
        $data['error_string'] = array();
        $data['inputerror'] = array();
        $data['bool'] = TRUE;
        if (trim($_POST['redirect_from']) == '') {
            $data['inputerror'][] = 'redirect_from';
            $data['error_string'][] = __("You must input the 'Redirect From' URL", "seo-redirection");
            $data['bool'] = FALSE;
        }

        $redirect_from = isset($_POST['redirect_from']) ? WPSR_sanitize_text_or_array_field($_POST['redirect_from']) : '';

        /* $wpdb->get_results(" select ID from $table_name where redirect_from='" . trim($redirect_from) . "' ");
        if ($wpdb->num_rows > 0) {
            $data['inputerror'][] = 'redirect_from';
            $data['error_string'][] = __("This 'Redirect From' value already exists in database!", "seo-redirection");
            $data['bool'] = FALSE;
        }

*/
        //  elseif (!preg_match( '/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/', $_POST['redirect_from'])) {
        //            $data['inputerror'][] = 'redirect_from';
        //            $data['error_string'][] = __("Invalid redirect from target URL!",'seo-redirection');
        //            $data['bool'] = FALSE;
        //  }
        if (trim($_POST['redirect_to']) == '') {
            $data['inputerror'][] = 'redirect_to';
            $data['error_string'][] = __("You must input the 'Redirect To' URL", "seo-redirection");
            $data['bool'] = FALSE;
        } elseif ($_POST['edit_exist'] == '' && substr(strtolower($_POST['redirect_to']), 0, 4) != "http" && substr(strtolower($_POST['redirect_to']), 0, 1) != "/") {
            $data['inputerror'][] = 'redirect_to';
            $data['error_string'][] = __("Invalid  redirect target URL!", 'seo-redirection');
            $data['bool'] = FALSE;
        }
        if ($data['bool'] === FALSE) {
            echo json_encode($data);
            exit();
        } else {
            if ($_POST['redirect_from'] != '' && wp_verify_nonce($nonce, 'seoredirection')) {


                $redirect_from = isset($_POST['redirect_from']) ? WPSR_sanitize_text_or_array_field($_POST['redirect_from']) : '';
                $redirect_to = isset($_POST['redirect_to']) ? WPSR_sanitize_text_or_array_field($_POST['redirect_to']) : '';


                $redirect_from = urldecode($util->make_relative_url($redirect_from));

                $redirect_to = $util->make_relative_url($redirect_to);
                $redirect_type = WPSR_sanitize_text_or_array_field($_POST['redirect_type']);

                $redirect_from_type = WPSR_sanitize_text_or_array_field($_POST['redirect_from_type']);
                $redirect_from_folder_settings = WPSR_sanitize_text_or_array_field($_POST['redirect_from_folder_settings']);
                $redirect_from_subfolders = WPSR_sanitize_text_or_array_field($_POST['redirect_from_subfolders']);

                $redirect_to_type = WPSR_sanitize_text_or_array_field($_POST['redirect_to_type']);
                $redirect_to_folder_settings = WPSR_sanitize_text_or_array_field($_POST['redirect_to_folder_settings']);

                $enabled = WPSR_sanitize_text_or_array_field($_POST['enabled']);

                $regex = "";

                if ($redirect_from_type == 'Folder') {

                    if (substr($redirect_from, -1) != '/')
                        $redirect_from = $redirect_from . '/';

                    if ($redirect_from_folder_settings == 2) {
                        if ($redirect_from_subfolders == 0) {
                            $regex = '^' . $util->regex_prepare($redirect_from) . '.*';;
                        } else {
                            $regex = '^' . $util->regex_prepare($redirect_from) . '[^/]*$';
                        }
                    } else if ($redirect_from_folder_settings == 3) {
                        if ($redirect_from_subfolders == 0) {
                            $regex = '^' . $util->regex_prepare($redirect_from) . '.+';
                        } else {
                            $regex = '^' . $util->regex_prepare($redirect_from) . '[^/]+$';
                        }
                    }
                } else if ($redirect_from_type == 'Regex') {
                    $regex = $redirect_from;
                }

                if ($redirect_from_type == 'Page' || $redirect_from_type == 'Regex') {
                    $redirect_from_folder_settings = "";
                    $redirect_from_subfolders = "";
                }

                if ($redirect_to_type == 'Page') {
                    $redirect_to_folder_settings = "";
                }

                if ($redirect_to_type == 'Folder') {
                    if (substr($redirect_to, -1) != '/')
                        $redirect_to = $redirect_to . '/';
                }


                if ($_POST['add_new'] != '') {

                    $theurl = $wpdb->get_row($wpdb->prepare(" select count(ID) as cnt from $table_name where redirect_from=%s ", $redirect_from));

                    if ($theurl->cnt > 0) {
                        $msg = __("This URL", 'seo-redirection') . " <b>" . esc_html($redirect_from) . "</b>" . __("is added previously!", 'seo-redirection');
                        echo json_encode(array('status' => 'error', 'msg' => $msg));
                        //		$util->failure_option_msg(__("This URL",'seo-redirection')." <b>'$redirect_from'</b>". __("is added previously!",'seo-redirection'));
                    } else {


                        if ($redirect_from == '' || $redirect_to == '' || $redirect_type == '') {
                            $util->failure_option_msg(__('Please input all required fields!', 'seo-redirection'));
                        } else {

                            $wpdb->insert($table_name, array(
                                'redirect_from' => $redirect_from,
                                'redirect_to' => $redirect_to,
                                'redirect_type' => $redirect_type,
                                'url_type' => 1,
                                'redirect_from_type' => $redirect_from_type,
                                'redirect_from_folder_settings' => $redirect_from_folder_settings,
                                'redirect_from_subfolders' => $redirect_from_subfolders,
                                'redirect_to_type' => $redirect_to_type,
                                'redirect_to_folder_settings' => $redirect_to_folder_settings,
                                'regex' => $regex,
                                'enabled' => $enabled

                            ));

                            $wpdb->query($wpdb->prepare(" delete from $table_name_404 where link=%s ", $redirect_from));
                            $SR_redirect_cache = new free_SR_redirect_cache();
                            $SR_redirect_cache->free_cache();
                            $msg = "Redirection Added Successfully";
                            echo json_encode(array('status' => 'success', 'msg' => $msg, 'url' => admin_url('options-general.php?page=seo-redirection.php')));
                            die;
                        }
                    }
                } else if ($_POST['edit_exist'] != '') {

                    $edit = WPSR_sanitize_text_or_array_field($_POST['edit']);

                    if ($redirect_from == '' || $redirect_to == '' || $redirect_type == '') {
                        $util->failure_option_msg('Please input all required fields!');
                    } else {

                        $wpdb->query($wpdb->prepare("update $table_name set redirect_from=%s,redirect_to=%s,redirect_type=%s,redirect_from_type=%s ,redirect_from_folder_settings=%d,redirect_from_subfolders=%d ,redirect_to_type=%s ,redirect_to_folder_settings=%d ,regex=%s,enabled=%s  where ID=%d ", $redirect_from, $redirect_to, $redirect_type, $redirect_from_type, $redirect_from_folder_settings, $redirect_from_subfolders, $redirect_to_type, $redirect_to_folder_settings, $regex, $enabled, $edit));

                        $SR_redirect_cache = new free_SR_redirect_cache();
                        $SR_redirect_cache->free_cache();
                    }
                    $msg = "Redirection Update Successfully";
                    echo json_encode(array('status' => 'success', 'msg' => $msg, 'url' => admin_url('options-general.php?page=seo-redirection.php')));
                    die;
                }

                if ($util->there_is_cache() != '')
                    $util->info_option_msg(__("You have a cache plugin installed", 'seo-redirection') . " <b>'" . $util->there_is_cache() . "'</b>, " . __("you have to clear cache after any changes to get the changes reflected immediately! ", 'seo-redirection'));
            }
        }


        die;
    }
}
if (!function_exists("WPSR_customUpdateRec_callback")) {

    add_action("wp_ajax_customUpdateRec", "WPSR_customUpdateRec_callback");
    function WPSR_customUpdateRec_callback()
    {


        global $wpdb, $table_prefix, $util;

        $table_name = $table_prefix . 'WP_SEO_Redirection';
        $table_name_404 = $table_prefix . 'WP_SEO_404_links';

        $myid = (int)$_POST['ID'];
        $item = $wpdb->get_row($wpdb->prepare(" select * from $table_name where ID=%d ", $myid));

        if ($wpdb->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'msg' => __("Sorry, this redirect rule is not found, it may deleted by the user!", 'seo-redirection')));
            die;
        }


        $data = array(
            "redirect_from" => $item->redirect_from,
            "redirect_to" => $item->redirect_to,
            "redirect_type" => $item->redirect_type,

            "redirect_from_type" => $item->redirect_from_type,
            "redirect_from_folder_settings" => $item->redirect_from_folder_settings,
            "redirect_from_subfolders" => $item->redirect_from_subfolders,

            "redirect_to_type" => $item->redirect_to_type,
            "redirect_to_folder_settings" => $item->redirect_to_folder_settings,

            "enabled" => $item->enabled
        );
        echo json_encode(array('status' => 'suucess', 'rec' => $data));
        die;
    }
}

if (!function_exists("WPSR_admin_menu")) {

    function WPSR_admin_menu()
    {
        add_options_page('SEO Redirection', 'SEO Redirection', 'manage_options', basename(__FILE__), 'WPSR_options_menu');
    }
}
//---------------------------------------------------------------
if (!function_exists("WPSR_options_menu")) {

    function WPSR_options_menu()
    {
        global $util;

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'seo-redirection'));
        }


        if ($util->get_option_value('plugin_status') == '0') {
            $util->info_option_msg(__('SEO Redirection is disabled now, you can go to option tab and enable it!', 'seo-redirection'));
        } else if ($util->get_option_value('plugin_status') == '2') {
            $util->info_option_msg(__('SEO Redirection is', 'seo-redirection') . ' <b>' . __('disabled for admin', 'seo-redirection') . '</b>' . __(' only, you can go to option tab and enable it!', 'seo-redirection'));
        }
        $total_404_errors = (WPSR_Get_total_404() > 10) ? __('You have', 'seo-redirection') . ' <b  style="color:red; background-color:yellow; padding:3px;">' . intval(WPSR_Get_total_404()) . '</b>' . __(' broken link (404 links)', 'seo-redirection') . ', <br>' : '';


        echo '<div class="wrap"><h2>' . __("SEO Redirection Free", 'seo-redirection') . '</h2><b>' . __('Upgrade to', 'seo-redirection') . ' <a target="_blank" onclick="swal.clickConfirm();" href="https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/">' . __("Pro Version", "seo-redirection") . '</a>' . __(" to manage 404 errors and empower your site SEO", "seo-redirection") . '&nbsp;&nbsp;&nbsp;<strong style="color:yellow; background-color:red; padding:3px;"> ' . __("NOW 50% OFF ", 'seo-redirection') . '</strong></b><br/><br/>';

        if ($total_404_errors != '') {
?>
            <script type="text/javascript">
                seoredirection.msg = '<?php echo wp_kses_post($total_404_errors); ?>';
            </script>

            <?php
        }

        if (is_multisite()) {

            echo '<div class="error" id="message"><p></p><div class="warning_icon"></div>' . __('This version does not support Multisite WordPress installation, you may face troubles like losing redirects when adding new sites to your network, the premium version supports multisite well', 'seo-redirection') . '(<a target="_blank" href="https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/">
https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/</a>) <p></p></div>';
        }

        $mytabs = new phptab();

        $mytabs->set_ignore_parameter(array('search', 'page_num', 'add', 'edit', 'page404', 'do_404_del'));
        $mytabs->add_file_tab('cutom', __('Custom Redirects', 'seo-redirection'), 'option_page_custome_redirection.php', 'file');
        $mytabs->add_file_tab('posts', __('Post Redirects', 'seo-redirection'), 'option_page_post_redirection_list.php', 'file');
        $mytabs->add_file_tab('history', __('History', 'seo-redirection'), 'option_page_history.php', 'file');
        $mytabs->add_file_tab('404', '<span style="color:red;"><b>404 Errors</b></span>', 'option_page_404.php', 'file');
        $mytabs->add_file_tab('goptions', __('Options', 'seo-redirection'), 'option_page_goptions.php', 'file');
        $mytabs->add_file_tab('help', '<span style="color:green;"><b>' . __('Help', 'seo-redirection') . '</b></span>', 'help.php', 'file');
        $mytabs->add_file_tab('premium', '<span style="color:brown;"><b>&#9658; ' . __('Premium Features', 'seo-redirection') . '</b></span>', 'premium.php', 'file');
        $mytabs->run();

        $imgpath = $util->get_plugin_url() . 'custom/images/';

        echo '<p>&nbsp;</p><p style="color:green"><a target="_blank" href="http://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin"><b>' . __("Upgrade to premium version now", "wsr") . '</b></a>' . __(" to get more features", "wsr") . ' , <small>' . __("The premium version of SEO redirection is completely different from the free version as there are a lot more features included.", "wsr") . '</small></p>';
        echo __('<p><a href="https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin" target="_blank"><img src="' . $imgpath . 'seopro.png" /></a></p>');
    }
}
if (!function_exists("WPSR_upgrade")) {

    function WPSR_upgrade()
    {
        //Edited by Ibrahim Shatat 4/11/2024
        //Make the upgrade statment to reach the latest version always & remove if clause
        $util = new clogica_util_1();
        WPSR_install();
        $util->update_option('plugin_version', WP_SEO_REDIRECTION_VERSION);
    }
}

//-----------------------------------------------------

if (!function_exists("WPSR_install")) {

    function WPSR_install()
    {
        global $wpdb, $table_prefix;

        $util = new clogica_util_1();
        $util->init(WP_SEO_REDIRECTION_OPTIONS, __FILE__);

        $options = get_option(WP_SEO_REDIRECTION_OPTIONS);
        if (!is_array($options)) {
            add_option(WP_SEO_REDIRECTION_OPTIONS);
            $options = array();
        }


        if (!array_key_exists('plugin_status', $options))
            $options['plugin_status'] = '1';

        if (!array_key_exists('ip_logging_status', $options))
            $options['ip_logging_status'] = '1';

        if (!array_key_exists('redirection_base', $options))
            $options['redirection_base'] = site_url();

        if (!array_key_exists('redirect_control_panel', $options))
            $options['redirect_control_panel'] = '1';

        if (!array_key_exists('show_redirect_box', $options))
            $options['show_redirect_box'] = '1';

        if (!array_key_exists('reflect_modifications', $options))
            $options['reflect_modifications'] = '1';

        if (!array_key_exists('history_status', $options))
            $options['history_status'] = '1';

        if (!array_key_exists('history_limit', $options))
            $options['history_limit'] = '30';

        if (!array_key_exists('p404_discovery_status', $options))
            $options['p404_discovery_status'] = '1';

        if (!array_key_exists('p404_redirect_to', $options))
            $options['p404_redirect_to'] = site_url();

        if (!array_key_exists('p404_status', $options))
            $options['p404_status'] = '2';

        if (!array_key_exists('keep_data', $options))
            $options['keep_data'] = '1';

        update_option(WP_SEO_REDIRECTION_OPTIONS, $options);


        $table_name = $table_prefix . 'WP_SEO_Redirection';
        if (strtolower($wpdb->get_var("show tables like '$table_name'")) != strtolower($table_name)) {
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `enabled` int(1) NOT NULL DEFAULT '1',
                  `redirect_from` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `redirect_from_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `redirect_from_folder_settings` int(1) NOT NULL,
                  `redirect_from_subfolders` int(1) NOT NULL DEFAULT '1',
                  `redirect_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `redirect_to_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `redirect_to_folder_settings` int(1) NOT NULL DEFAULT '1',
                  `regex` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `redirect_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `url_type` int(2) NOT NULL DEFAULT 1,
                  `postID` int(11) unsigned DEFAULT NULL,
                  `import_flag` tinyint(1) NOT NULL DEFAULT 0,
                  `hits` int(11) unsigned NOT NULL DEFAULT 0,
                  `access_date` datetime DEFAULT NULL,
                  PRIMARY KEY (`ID`),
                  UNIQUE KEY `redirect_from` (`redirect_from`)
                )ENGINE = MyISAM ;";
            $wpdb->query($sql);
        } else {
            //check if Innodb convert it to myisam.
            $status = $wpdb->get_row("SHOW TABLE STATUS WHERE Name = '$table_name'");
            if ($status->Engine == 'InnoDB') {
                $wpdb->query("alter table $table_name engine = MyISAM;");
            }

            /* add column for import flag */
            $column_data = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'import_flag'");

            if (!$column_data) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN import_flag tinyint(1) DEFAULT 0");
            }

            $wpdb->query("ALTER TABLE $table_name CHANGE `redirect_from` `redirect_from` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;");

            // if the table exists
            $redirects = $wpdb->get_results(" select redirect_from,redirect_to,ID from $table_name; ");
            foreach ($redirects as $redirect) {
                $redirect_from = $util->make_relative_url($redirect->redirect_from);
                $redirect_to = $util->make_relative_url($redirect->redirect_to);
                $ID = $redirect->ID;
                $wpdb->query($wpdb->prepare(" update $table_name set  redirect_from=%s,redirect_to=%s where ID=%d", $redirect_from, $redirect_to, $ID));
            }

            // Fix add blog field if not exist.
            if ($wpdb->get_var(" SELECT count(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS
                       WHERE TABLE_NAME = '$table_name'
                            AND table_schema = DATABASE()
                            AND COLUMN_NAME = 'hits' ") == '0') {

                $sql = "
                    ALTER TABLE $table_name
                    ADD COLUMN `hits` int(11) unsigned NOT NULL DEFAULT 0,
                    ADD COLUMN `access_date` datetime DEFAULT NULL;
                ";

                $wpdb->query($sql);
            }
        }

        $table_name = $table_prefix . 'WP_SEO_Cache';
        if (strtolower($wpdb->get_var("show tables like '$table_name'")) != strtolower($table_name)) {
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
              `ID` int(11) unsigned NOT NULL,
              `is_redirected` int(1) unsigned NOT NULL,
              `redirect_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `redirect_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `redirect_type` int(3) unsigned NOT NULL DEFAULT 301,
              PRIMARY KEY (`ID`)
            ) ENGINE = MyISAM ;
			";
            $wpdb->query($sql);
        } else {
            //check if Innodb convert it to myisam.
            $status = $wpdb->get_row("SHOW TABLE STATUS WHERE Name = '$table_name'");
            if ($status->Engine == 'InnoDB') {
                $wpdb->query("alter table $table_name engine = MyISAM;");
            }
        }


        $res = $wpdb->get_var(" SELECT count(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_NAME = '$table_name'
			AND table_schema = DATABASE()
                        AND COLUMN_NAME = 'redirect_from' ");

        if ($res == '0') {

            $sql = "
                ALTER TABLE $table_name
                ADD COLUMN `redirect_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL;
            ";
            $wpdb->query($sql);
        }

        $table_name = $table_prefix . 'WP_SEO_404_links';
        if (strtolower($wpdb->get_var("show tables like '$table_name'")) != strtolower($table_name)) {
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
              `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `ctime` datetime NOT NULL,
              `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `referrer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `ip` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
              `country` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
              `os` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
              `browser` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`ID`),
              UNIQUE KEY `link` (`link`)
            ) ENGINE = MyISAM ;
			";
            $wpdb->query($sql);
        } else {
            //check if Innodb convert it to myisam.
            $status = $wpdb->get_row("SHOW TABLE STATUS WHERE Name = '$table_name'");
            if ($status->Engine == 'InnoDB') {
                $wpdb->query("alter table $table_name engine = MyISAM;");
            }
        }


        $table_name = $wpdb->prefix . 'WP_SEO_Redirection_LOG';

        if (strtolower($wpdb->get_var("SHOW TABLES LIKE '$table_name'")) != strtolower($table_name)) {
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `rID` int(11) unsigned DEFAULT NULL,
                `postID` int(11) unsigned DEFAULT NULL,
                `ctime` datetime NOT NULL,
                `rfrom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `rto` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `rtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `rsrc` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
                `referrer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `ip` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
                `country` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                `os` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
                `browser` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`ID`)
            ) ENGINE = MyISAM;";

            $wpdb->query($sql);
        } else {
            // Check if InnoDB and convert to MyISAM if necessary
            $status = $wpdb->get_row("SHOW TABLE STATUS WHERE Name = '$table_name'");
            if ($status->Engine == 'InnoDB') {
                $wpdb->query("ALTER TABLE $table_name ENGINE = MyISAM;");
            }

            // Check if the postID column exists and add it if missing
            $res = $wpdb->get_var("SELECT count(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = '$table_name'
                AND COLUMN_NAME = 'postID'
                AND table_schema = DATABASE()");

            if ($res == '0') {
                $sql = "ALTER TABLE $table_name ADD COLUMN `postID` int(11) unsigned DEFAULT NULL;";
                $wpdb->query($sql);
            }
        }
    }
}
//---------------------------------------------------------------

if (!function_exists("WPSR_uninstall")) {

    function WPSR_uninstall()
    {
        global $wpdb, $table_prefix;

        $util = new clogica_util_1();
        $util->init(WP_SEO_REDIRECTION_OPTIONS, __FILE__);


        if ($util->get_option_value('keep_data') != '1') {

            $table_name = $table_prefix . 'WP_SEO_Redirection';
            $wpdb->query($wpdb->prepare(" DROP TABLE %s  ", $table_name));

            $table_name = $table_prefix . 'WP_SEO_Cache';
            $wpdb->query($wpdb->prepare(" DROP TABLE %s  ", $table_name));

            $table_name = $table_prefix . 'WP_SEO_404_links';
            $wpdb->query($wpdb->prepare(" DROP TABLE %s  ", $table_name));

            $table_name = $table_prefix . 'WP_SEO_Redirection_LOG';
            $wpdb->query($wpdb->prepare(" DROP TABLE %s  ", $table_name));


            $util->delete_my_options();
        }
    }
}
//---------------------------------------------------------------
if (!function_exists("WPSR_HideMessageAjaxFunction")) {

    function WPSR_HideMessageAjaxFunction()
    {
        add_option('nsr_upgrade_message', 'yes');
    }
}
//---------------------------------------------------------------
if (!function_exists("WPSR_admin_notice_callback")) {

    /* display import from redirection plugin in admin notice */
    function WPSR_admin_notice_callback()
    {
        global $wpdb;
        global $current_user;
        $plugins = get_option('active_plugins', array());
        $found = false;
        $user_id = $current_user->ID;
        $val = get_user_meta($user_id, 'sr_notice_dismissed', true);

        foreach ($plugins as $plugin) {
            if (strpos(strval($plugin), 'redirection.php') == true && $val != 1 && strpos(strval($plugin), 'seo-redirection.php') == FALSE) {
                $found = true;
                //$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}redirection_items");
                $total = WPSR_getRedirectCount();
                if ($total > 0) {
            ?>
                    <div class="notice-success notice is-dismissible sr_notice">
                        <p>
                            <strong><?php _e('SEO Redirection : ', 'seo-redirection'); ?></strong><?php echo __('The plugin detected ', 'seo-redirection') . intval($total) . __(' redirects in the Redirection plugin, Import it now. ', 'seo-redirection'); ?>
                            <?php
                            $SR_import = isset($_GET['SR_import']) ? sanitize_text_field($_GET['SR_import']) : '';
                            if ($SR_import == 'yes') { ?>
                                <button type='button' data-toggle="modal" class="button" href="#" data-target="#import_modal" value="btn_import"><span style="margin-top: 3px;" class="dashicons dashicons-migrate"></span>&nbsp; <?php _e('Import Now', 'seo-redirection'); ?></button>
                            <?php } else { ?>
                                <a href="options-general.php?page=seo-redirection.php&tab=export_import&SR_import=yes" data-target="#import_modal" value="btn_import"><?php _e('Import', 'seo-redirection'); ?></a>
                            <?php } ?>
                        </p>
                    </div>
        <?php
                }
                break;
            }
        }
    }
    add_action('admin_notices', 'WPSR_admin_notice_callback');
}

if (!function_exists("WPSR_dismiss_notice_callback")) {

    add_action('wp_ajax_sr_dismiss_notice', 'WPSR_dismiss_notice_callback');
    function WPSR_dismiss_notice_callback()
    {
        global $current_user;
        $user_id = $current_user->ID;
        update_user_meta($user_id, 'sr_notice_dismissed', '1');
        echo "1";
        exit;
    }
}
if (!function_exists("WPSR_getRedirectCount")) {

    function WPSR_getRedirectCount()
    {
        global $wpdb;

        $table_name_ = $wpdb->prefix . 'redirection_items';
        if (strtolower($wpdb->get_var("show tables like '$table_name_'")) == strtolower($table_name_)) {
            $result = $wpdb->get_results("SELECT url FROM {$wpdb->prefix}redirection_items");
            $cnt = 0;
            $table_name = $wpdb->prefix . 'WP_SEO_Redirection';
            if ($result) {
                foreach ($result as $redirect) {
                    $redirect_from = stripslashes($redirect->url);
                    $redirect_from_slash = ltrim(stripslashes($redirect->url), '/');
                    $redirectID = $wpdb->get_var($wpdb->prepare("select ID from $table_name where redirect_from=%s or redirect_from=%s", $redirect_from, $redirect_from_slash));
                    if ($redirectID > 0) {
                    } else {
                        $cnt++;
                    }
                }
            }
            return $cnt;
        } else {
            return 0;
        }
    }
}
if (!function_exists("SR_init_delete_callback")) {

    add_action('admin_init', 'SR_init_delete_callback');
    function SR_init_delete_callback()
    {
        if (isset($_POST['redirect_id']) && count($_POST['redirect_id']) > 0) {

            $nonce = '';
            if (isset($_REQUEST['_wpnonce']))
                $nonce = WPSR_sanitize_text_or_array_field($_REQUEST['_wpnonce']);

            if (wp_verify_nonce($nonce, 'seoredirection')) {

                global $wpdb, $table_prefix, $util;
                $table_name = $wpdb->prefix . 'WP_SEO_Redirection';
                foreach ($_POST['redirect_id'] as $post_id) {
                    $post_id = (int)$post_id;
                    $wpdb->query($wpdb->prepare(" delete from $table_name where ID=%s ", $post_id));
                    $SR_redirect_cache = new free_SR_redirect_cache();
                    $SR_redirect_cache->free_cache();
                }
            }
        }
    }
}




//---------------------------------------------------------------

function WPSR_HideMessageAjaxFunction()
{
    add_option('nsr_upgrade_message', 'yes');
}


function WPSR_after_plugin_row($plugin_file, $plugin_data, $status)
{

    if (get_option('nsr_upgrade_message') != 'yes') {
        $class_name = $plugin_data['slug'];
        $class_name = isset($plugin_data['slug']) ? $plugin_data['slug'] : 'seo-redirection';

        echo '<tr id="' . $class_name . '-plugin-update-tr" class="plugin-update-tr active">';
        echo '<td  colspan="6" class="plugin-update">';
        echo '<div id="' . $class_name . '-upgradeMsg" class="update-message notice inline notice-warning notice-alt"  >';

        echo 'You are running SEO redirection free. To get more features, you can <a href="http://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin" target="_blank"><strong>upgrade now</strong></a> or ';

        echo '<span id="HideMe" style="cursor:pointer" ><a href="javascript:void(0)"><strong>dismiss</strong></a> this message</span>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';

        ?>

        <script type="text/javascript">
            jQuery(document).ready(function() {
                var row = jQuery('#<?php echo $class_name; ?>-plugin-update-tr').closest('tr').prev();
                jQuery(row).addClass('update');

                jQuery("#HideMe").click(function() {
                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url(); ?>/admin-ajax.php',
                        data: {
                            action: 'WPSR_HideMessageAjaxFunction'
                        },
                        success: function(data, textStatus, XMLHttpRequest) {

                            jQuery("#<?php echo $class_name; ?>-upgradeMsg").hide();

                        },
                        error: function(MLHttpRequest, textStatus, errorThrown) {
                            alert(errorThrown);
                        }
                    });
                });

            });
        </script>

<?php
    }
}

$path = plugin_basename(__FILE__);
add_action("after_plugin_row_{$path}", 'WPSR_after_plugin_row', 10, 3);
// creating Ajax call for WordPress  
add_action('wp_ajax_nopriv_WPSR_HideMessageAjaxFunction', 'WPSR_HideMessageAjaxFunction');
add_action('wp_ajax_WPSR_HideMessageAjaxFunction', 'WPSR_HideMessageAjaxFunction');
//---------------------------------------------------------------

?>