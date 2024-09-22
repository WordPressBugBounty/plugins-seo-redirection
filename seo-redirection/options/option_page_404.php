<?php

global $wpdb, $table_prefix, $util;

$table_name = $table_prefix . 'WP_SEO_404_links';

if ($util->post('redirect_to') != '') {
	global $util;

	$util->update_post_option('p404_status');
	$util->update_option('p404_redirect_to', $util->make_relative_url($util->post('redirect_to')));
	$util->success_option_msg('Options Saved!');

	if ($util->there_is_cache() != '')
		$util->info_option_msg("You have a cache plugin installed <b>'" . $util->there_is_cache() . "'</b>, you have to clear cache after any changes to get the changes reflected immediately! ");
}

if ($util->get('do_404_del') != '') {
	
	if (!current_user_can('manage_options')){
        return;
    }
	
	$WPSRnonce = $_REQUEST['_wpnonce'];
	
	if ( ! wp_verify_nonce( $WPSRnonce, 'WPSRnonce' ) ) {
    die( __( 'Security Error! Invalid Nonce.', 'seo-redirection' ) ); 
	}
	
	
	if ($util->get('do_404_del') == 1) {
		$wpdb->query("delete from $table_name where ctime <= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)");
	} else if ($util->get('do_404_del') == 2) {
		$wpdb->query("delete from $table_name where ctime <= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)");
	} else if ($util->get('do_404_del') == 3) {
		$wpdb->query("Truncate table $table_name ");
	}
}



$options = $util->get_my_options();


if ($util->get_option_value('p404_discovery_status') != '1')
	$util->info_option_msg("404 error pages discovery property is disabled now!, you can re-enable it from options tab.");



$WPSR_get_current_parameters_ = $util->WPSR_get_current_parameters(array('search', 'page_num', 'add', 'edit'));

$WPSRnonce = wp_create_nonce( 'WPSRnonce' );

?>

<script type="text/javascript">
	//---------------------------------------------------------

	function decodeHtmlCharCodes(str) {
		return str.replace(/(&#(\d+);)/g, function(match, capture, charCode) {
			return String.fromCharCode(charCode);
		});
	}


	function go_search() {
		var sword = document.getElementById('search').value;
		if (sword != '') {
			var link = decodeHtmlCharCodes("<?php echo sprintf('%s', esc_js($WPSR_get_current_parameters_)); ?>") + "&search=" + sword;
			window.location = link;
		} else {
			alert('Please input any search words!');
			document.getElementById('search').focus();
		}

	}


	function go_del() {

		if (confirm('Are you sure you want to delete all 404 links?')) {
			var goto_url = decodeHtmlCharCodes("<?php echo sprintf('%s', esc_js($WPSR_get_current_parameters_)); ?>");

			window.location = goto_url + "&do_404_del=" + document.getElementById('del_404_option').value+"&_wpnonce=<?php echo sprintf('%s', esc_js($WPSRnonce)); ?>";
		}
	}
</script>

<h3>New Discovered 404 links
	<hr>
</h3>
<div class="link_buttons">
	<table border="0" width="100%">
		<tr>
			<td align="left">
				<input onkeyup="if (event.keyCode == 13) go_search();" style="height: 30px;  border-radius: 3px !important;" id="search" type="text" name="search" value="<?php echo htmlentities(esc_attr($util->get('search'))) ?>" size="40">
				<a onclick="go_search()" style="padding:3px 10px !important" class="btn-custom btn-search" href="#">
					<span class="dashicons dashicons-search"></span>
					Search
				</a>
				<a class="btn-custom btn-search" style="padding:3px 10px !important" href="<?php echo esc_url($util->WPSR_get_current_parameters('search')); ?>">
					<span class="dashicons dashicons-screenoptions"></span>
					Show All
				</a>
			</td>
			<td align="right">
				<select style="height: 30px;  border-radius: 3px !important;" data-size="5" class="selectpicker" name="del_404_option" id="del_404_option">
					<option value="1"><?php echo __('Keep this month', 'seo-redirection'); ?></option>
					<option value="2"><?php echo __('Keep last 3 months', 'seo-redirection'); ?></option>
					<option value="3"><?php echo __('Delete all', 'seo-redirection'); ?></option>
				</select>
				<a class="btn-custom btn-delete" style="padding:3px 10px !important" onclick="go_del()" href="#">
					<span class="dashicons dashicons-trash"></span>
					Delete
				</a>
			</td>
		</tr>
	</table>
</div>
<?php

$grid = new datagrid();
$grid->set_data_source($table_name);
$grid->set_table_attr('class', 'wp-list-table widefat fixed striped');
$grid->add_select_field('ID');
$grid->add_select_field('link');
$grid->add_select_field('ip');
$grid->add_select_field('referrer');

$grid->set_order(" ID desc ");

if ($util->get('search') != '') {
	$search = $util->get('search');

	$grid->set_filter(" link like '%%$search%%' or ctime like '%%$search%%'
		or referrer like '%%$search%%'   or country like '%%$search%%'   or ip like '%%$search%%'
		or os like '%%$search%%' or browser like '%%$search%%'
		 ");
}

$grid->set_table_attr('width', '100%');

$grid->set_col_attr(1, 'width', '60%');
$grid->set_col_attr(1, 'align', 'left');

$grid->set_col_attr(2, 'width', '30%');
$grid->set_col_attr(2, 'align', 'left');

$grid->set_col_attr(3, 'width', '30%');
$grid->set_col_attr(3, 'align', 'left');



$grid->add_php_col(" <a target='_blank' href='db_link_url'>db_link</a>", 'Link');
$grid->add_data_col('ctime', 'Discovered');

if ($util->get_option_value('ip_logging_status') == 0) {
	$grid->add_html_col('--', __('IP', 'seo-redirection'));
} else if ($util->get_option_value('ip_logging_status') == 1) {
	$grid->add_html_col('<a target="_blank" href="https://tools.keycdn.com/geo?host={db_ip}">{db_ip}</a>', __('IP', 'seo-redirection'));
} else {

	$grid->add_php_col(' db_ip ', __('IP', 'seo-redirection'));
}




$grid->run();



?>
<div><b style="color:red">Have many broken links?</b><br />
	keep track of 404 errors using our powerful SEO Redirection Plugin to show and fix all broken links & 404 errors that occur on your site. <a target="_blank" href="https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/">click here to fix and improve your site SEO</a></div>
<br /><br />