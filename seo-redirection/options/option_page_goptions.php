<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
global $util;

$nonce = "";
if (isset($_REQUEST['_wpnonce']))
	$nonce = sanitize_text_field($_REQUEST['_wpnonce']);

if (isset($_POST) && wp_verify_nonce($nonce, 'seoredirection')) {
	if ($util->post('reset_all_options') != '') {
		c_init_my_options();
		$util->success_option_msg(__('All Options Restored to Defaults', 'seo-redirection'));
	} else if ($util->post('Save_general_options') != '') {
		c_save_redirection_general_options();
		$util->success_option_msg(__('General Options Saved!', 'seo-redirection'));
	} else if ($util->post('save_history_options') != '') {
		c_save_redirection_history_options();
		$util->success_option_msg(__('History Options Saved!', 'seo-redirection'));
	} else if ($util->post('clear_history') != '') {
		c_clear_redirection_history();
		$util->success_option_msg(__('History Cleared!', 'seo-redirection'));
	} else if ($util->post('save_404_options') != '') {
		c_save_404_redirection_options();
		$util->success_option_msg(__('404 Redirection Options Saved!', 'seo-redirection'));
	} else if ($util->post('clear_all_404') != '') {
		c_clear_all_404();
		$util->success_option_msg(__('All Discovered 404 Pages Cleared!', 'seo-redirection'));
	} else if ($util->post('save_data_options') != '') {
		c_save_keep_data();
		$util->success_option_msg(__('Data Options Saved!', 'seo-redirection'));
	} else if ($util->post('optimize_tables') != '') {
		c_optimize_tables();
		$util->success_option_msg(__('Data Tables Optimized!', 'seo-redirection'));
	} else if ($util->post('save_all_options')) {
		c_save_redirection_general_options();
		c_save_redirection_history_options();
		c_save_404_redirection_options();
		c_save_keep_data();

		$util->success_option_msg(__('All options saved!', 'seo-redirection'));
	}






	if ($util->there_is_cache() != '')

		$util->info_option_msg(__("You have a cache plugin installed", 'seo-redirection') . " <b>'" . $util->there_is_cache() . "'</b>, " . __("you have to clear cache after any changes to get the changes reflected immediately! ", 'seo-redirection'));
}

$options = $util->get_my_options();

?>



<form method="POST">

	<div class="container">
		<div class="row">


			<div class="col-md-6 mt-4">

				<h3><?php _e("General Options", "seo-redirection") ?>
					<hr>
				</h3>

				<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('seoredirection')); ?>" />

				<table class="cform" align="center" width="100%">
					<tr>
						<td>
							<?php _e("Plugin Status:", "seo-redirection") ?>
							<?php
							$drop = new dropdown('plugin_status');
							$drop->add(__('Enabled', 'seo-redirection'), '1');
							$drop->add(__('Disabled', 'seo-redirection'), '0');
							$drop->add(__('Disabled for admin only', 'seo-redirection'), '2');
							$drop->dropdown_print();
							$drop->select($options['plugin_status']);
							?>

						</td>
					</tr>


					<tr>
						<td>
							<?php _e("IP Logging:", "seo-redirection") ?>
							<?php
							$drop = new dropdown('ip_logging_status');
							$drop->add(__('No IP logging', 'seo-redirection'), '0');
							$drop->add(__('Full IP logging', 'seo-redirection'), '1');
							$drop->add(__('Anonymize IP (mask last part)', 'seo-redirection'), '2');
							$drop->dropdown_print();
							$drop->select($options['ip_logging_status']);
							?>
							<small>&nbsp; used for <a href="https://eugdpr.org/" target="_blank">GDPR</a> compliance</small>
						</td>
					</tr>

					<tr>
						<td>
							<?php $check = new checkoption('redirect_control_panel', $options['redirect_control_panel']); ?>
							<?php _e("Do not redirect control panel links (This will be usefull when making wrong expressions that may cause an infinit redirection loop).", "seo-redirection"); ?>

							<br />
							<?php $check = new checkoption('show_redirect_box', $options['show_redirect_box']); ?>
							<?php _e("Show redirect box in posts & pages edit page (Important to set up redirection for posts and pages easily).", "seo-redirection"); ?>


							<br />
							<?php $check = new checkoption('reflect_modifications', $options['reflect_modifications']); ?>
							<?php _e("Reflect any modifications in the post permalink to all redirection links (Mostly Recommended).", "seo-redirection"); ?>

							<script type="text/javascript">

							</script>
						</td>
					</tr>

				</table>
				<br />
				<br />


			</div>
			<div class="col-md-6 mt-4">

				<h3><?php _e("Redirection History Options", "seo-redirection") ?>
					<hr>
				</h3>
				<table class="cform" align="center" width="100%">
					<tr>
						<td>
							<?php _e("Redirection History Status:", "seo-redirection") ?>
							<?php
							$drop = new dropdown('history_status');
							$drop->add(__('Enabled', 'seo-redirection'), '1');
							$drop->add(__('Disabled', 'seo-redirection'), '0');
							$drop->dropdown_print();
							$drop->select($options['history_status']);
							?>

						</td>
					</tr>
					<tr>
						<td>
							<?php _e("Redirection History Limit:", "seo-redirection") ?>
							<?php
							$drop = new dropdown('history_limit');
							$drop->add(__('7 days', 'seo-redirection'), '7');
							$drop->add(__('1 month', 'seo-redirection'), '30');
							$drop->add(__('2 months', 'seo-redirection'), '60');
							$drop->add(__('3 months', 'seo-redirection'), '90');
							$drop->dropdown_print();
							$drop->select($options['history_limit']);
							?>

						</td>
					</tr>

				</table>
				<br />
				<input style="margin-left:5px" class="button-primary btn-custom btn-search" type="submit" value='Clear History ⊗' name="clear_history">

				<br />
				<br />

			</div>
			<div class="col-md-6 mt-4">

				<h3>404 Error Pages Options
					<hr>
				</h3>


				<table class="cform" align="center" width="100%">
					<tr>
						<td>
							404 Error Pages Discovery:
							<?php
							$drop = new dropdown('p404_discovery_status');
							$drop->add('Enabled', '1');
							$drop->add('Disabled', '0');
							$drop->dropdown_print();
							$drop->select($options['p404_discovery_status']);
							?>

						</td>
					</tr>

					<tr>
						<td>
							Unknown 404 Redirection Status:
							<?php
							$drop = new dropdown('p404_status');
							$drop->add('Enabled', '1');
							$drop->add('Disabled', '2');
							$drop->dropdown_print();
							$drop->select($options['p404_status']);
							?>
						</td>
					</tr>

					<tr>
						<td>
							Redirect All Unknown 404 Pages to: <input type="text" name="redirect_to" id="redirect_to" size="30" value="<?php echo esc_attr_e($options['p404_redirect_to']) ?>">&nbsp;<span style="color:red"><br>Have many broken links?</span>&nbsp;<a target="_blank" href="https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/">Click here to fix and improve your site SEO</a>
						</td>
					</tr>

				</table>
				<br />
				<input style="margin-left:5px" class="button-primary btn-custom btn-search" type="submit" value="Clear All Discovered 404 Pages ⊗" name="clear_all_404">

				<br /><br />

			</div>
			<div class="col-md-6 mt-4">
				<h3><?php _e("Redrection Data Options", "seo-redirection"); ?>
					<hr>
				</h3>
				<table class="cform" align="center" width="100%">
					<tr>
						<td>
							<?php $check = new checkoption('keep_data', $options['keep_data'], '1'); ?>
							<?php _e("Keep redirection data after uninstall the plugin, this will be useful when you install it later.", "seo-redirection"); ?>

						</td>
					</tr>
				</table>
				<br />
				<input style="margin-left:5px" class="button-primary btn-custom btn-search" type="submit" value="<?php _e("Optimize Data Tables ⊗", "seo-redirection") ?>" name="optimize_tables">
				<br /><br /><br />

			</div>
		</div>
	</div>

	<hr>

	<input style="margin-left:5px" class="button-primary btn-custom btn-add" type="submit" value="<?php _e("Save All Options", "seo-redirection") ?>" name="save_all_options">
	<input style="margin-left:5px" class="button-primary btn-custom btn-search" type="submit" value="<?php _e("Restore Default Settings", "seo-redirection") ?>" name="reset_all_options">

</form>