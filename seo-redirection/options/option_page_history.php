<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
global $wpdb, $table_prefix, $util;
$table_name = $table_prefix . 'WP_SEO_Redirection_LOG';
$rlink = $util->WPSR_get_current_parameters(array('search', 'page_num', 'add', 'edit'));

if ($util->get('del') != '') {
	
	if (!current_user_can('manage_options')){
        return;
    }
	
	if ($util->get('del') == 'all') {
		
		
		$WPSRnonce = $_REQUEST['_wpnonce'];
	
			if ( ! wp_verify_nonce( $WPSRnonce, 'WPSRnonce' ) ) 
			{
			die( __( 'Security Error! Invalid Nonce.', 'seo-redirection' ) ); 
			}else{
				c_clear_redirection_history();
			}
			
			
		c_clear_redirection_history();

		if ($util->there_is_cache() != '')
			$util->info_option_msg(__("You have a cache plugin installed", 'seo-redirection') . " <b>'" . $util->there_is_cache() . "'</b>, " . __("you have to clear cache after any changes to get the changes reflected immediately! ", 'seo-redirection'));
	}
}

if ($util->get_option_value('history_status') != '1')
	$util->info_option_msg(__("Redirection history property is disabled now!, you can re-enable it from options tab.", 'seo-redirection'));

?>

<script type="text/javascript">
	//---------------------------------------------------------
	function go_search() {
		var sword = document.getElementById('search').value;
		if (sword != '') {
			window.location = "<?php echo esc_url($rlink) ?>&search=" + sword;
		} else {
			alert('<?php _e("Please input any search words!", "seo-redirection") ?>');
			document.getElementById('search').focus();
		}

	}
</script>

<h3><?php _e("Redirection History", "seo-redirection"); ?>
	<hr>
</h3>
<?php
$WPSRnonce = wp_create_nonce( 'WPSRnonce' );
?>
<div class="link_buttons">
	<table border="0" width="100%">
		<tr>
			<td width="150"><a href="<?php echo esc_url($rlink) ?>&del=all&_wpnonce=<?php echo $WPSRnonce;?>" class="button btn-custom btn-delete"><span style="padding-top: 3px;" class="dashicons dashicons-trash"></span>&nbsp;<?php _e("Clear History", "seo-redirection"); ?></a></td>
			<td align="right">
				<input onkeyup="if (event.keyCode == 13) go_search();" style="height: 30px; border-radius: 3px !important;" id="search" type="text" name="search" value="<?php echo htmlentities($util->get('search'), ENT_QUOTES) ?>" size="30">
				<a onclick="go_search()" href="#" class="button btn-custom btn-search"><span style="padding-top: 3px;" class="dashicons dashicons-search"></span>&nbsp;<?php _e("Search", "seo-redirection"); ?></a>
				<a href="<?php echo esc_url($util->WPSR_get_current_parameters('search')) ?>" class="button btn-custom btn-search"><span style="padding-top: 3px;" class="dashicons dashicons-screenoptions"></span>&nbsp;<?php _e("Show All", "seo-redirection"); ?></a>
			</td>
		</tr>
	</table>
</div>

<?php


$grid = new datagrid();
$grid->set_data_source($table_name);
$grid->set_table_attr('class', 'wp-list-table widefat fixed striped');
$grid->set_order(" ID desc ");

if ($util->get('search') != '') {
	$search = $util->get('search');

	$grid->set_filter(" rfrom like '%%$search%%' or rto like '%%$search%%' or ctime like '%%$search%%'
		or referrer like '%%$search%%'   or country like '%%$search%%'   or ip like '%%$search%%'
		or os like '%%$search%%' or browser like '%%$search%%' or rsrc like '%%$search%%' or rtype like '%%$search%%' 
		 ");
}

$grid->add_select_field('rID');
$grid->add_select_field('postID');
$grid->add_select_field('referrer');
$grid->add_select_field('ip');
$grid->add_select_field('os');
$grid->add_select_field('browser');
$grid->add_select_field('rsrc');
$grid->add_select_field('rfrom');
$grid->add_select_field('rto');
$grid->add_select_field('ctime');

$grid->set_table_attr('width', '100%');
$grid->set_col_attr(1, 'width', '120px');
$grid->set_col_attr(3, 'width', '20px');
$grid->set_col_attr(3, 'align', 'center');
$grid->set_col_attr(4, 'width', '20px');
$grid->set_col_attr(4, 'align', 'center');
$grid->set_col_attr(7, 'width', '30px');
$grid->set_col_attr(7, 'align', 'center');
$grid->set_col_attr(6, 'width', '75px');
$grid->set_col_attr(5, 'width', '130px');

$grid->set_col_attr(1, 'width', '90px', 'header');
$grid->set_col_attr(3, 'width', '40px', 'header');
$grid->set_col_attr(4, 'width', '40px', 'header');
$grid->set_col_attr(5, 'width', '125px', 'header');
$grid->set_col_attr(6, 'width', '120px', 'header');
$grid->set_col_attr(7, 'width', '50px', 'header');

$grid->add_php_col('db_date_y<br/>db_date_h', __('Time', 'seo-redirection'));

$grid->add_php_col("<div class='arrow_from'><a target='_blank' href='db_rfrom_url'>db_rfrom</a></div><div class='arrow_to'><a target='_blank' href='db_rto_url'>db_rto</a></div>", __('Redirection', 'seo-redirection'));
$grid->add_data_col('rtype', 'Type');
$grid->add_php_col('db_referrer_var', __('Ref', 'seo-redirection'));



if ($util->get_option_value('ip_logging_status') == 0) {
	$grid->add_html_col('--', __('IP', 'seo-redirection'));
} else if ($util->get_option_value('ip_logging_status') == 1) {
	$grid->add_html_col('<a target="_blank" href="https://tools.keycdn.com/geo?host={db_ip}">{db_ip}</a>', __('IP', 'seo-redirection'));
} else {

	$grid->add_php_col('db_ip', __('IP', 'seo-redirection'));
}


$grid->add_html_col('{db_os}<br/>{db_browser}', __('Agent', 'seo-redirection'));

$grid->add_php_col('db_rsrc_custom', __('Class', 'seo-redirection'));


$grid->run();


?>