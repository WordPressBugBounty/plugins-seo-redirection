<?php
global $wpdb, $table_prefix;
require_once WPSR_PATH . "custom/lib/cf.SR_redirect_cache.class.php";
require_once WPSR_PATH . "cf/lib/cf.jforms.class.php";
require_once WPSR_PATH . "cf/lib/forms/cf.dropdownlist.class.php";


$SR_jforms = new jforms();
$SR_redirect_cache = new clogica_SR_redirect_cache();

function WPSR_get_current_parameters($remove_parameter = "") {
    if ($_SERVER['QUERY_STRING'] != '') {
        $qry = '?' . urldecode(sanitize_text_field($_SERVER['QUERY_STRING']));
        if (is_array($remove_parameter)) {
            for ($i = 0; $i < count($remove_parameter); $i++) {
                if (array_key_exists($remove_parameter[$i], $_GET)) {
					
					
                    $string_remove = '&' . $remove_parameter[$i] . "=" . sanitize_text_field(urldecode($_GET[$remove_parameter[$i]]));
                    $qry = str_ireplace($string_remove, "", $qry);
                    $string_remove = '?' . $remove_parameter[$i] . "=" . sanitize_text_field(urldecode($_GET[$remove_parameter[$i]]));
                    $qry = str_ireplace($string_remove, "", $qry);
                }
            }
        } else {
            if ($remove_parameter != '') {
                if (array_key_exists($remove_parameter, $_GET)) {
                    $string_remove = '&' . $remove_parameter . "=" . sanitize_text_field(urldecode($_GET[$remove_parameter]));
                    $qry = str_ireplace($string_remove, "", $qry);
                    $string_remove = '?' . $remove_parameter . "=" . sanitize_text_field(urldecode($_GET[$remove_parameter]));
                    $qry = str_ireplace($string_remove, "", $qry);
                }
            }
        }
        return $qry;
    } else {
        return "";
    }
}

function WPSR_echo_message($msgtxt, $type = 'success') {
    $css = $type;
    $icon = "";
    if ($type == 'updated' || $type == 'success') {
        $css = 'success';
       
		
		echo '<div class="alert alert-' . esc_attr($css) . '" role="alert"><span class="glyphicon glyphicon-ok"></span> ' . esc_html($msgtxt) . '</div>';
    } else if ($type == 'error' || $type == 'danger') {
        $css = 'danger';
       
		echo '<div class="alert alert-' . esc_attr($css) . '" role="alert"><span class="glyphicon glyphicon-warning-sign"></span> ' . esc_html($msgtxt) . '</div>';
    }

    
}

function WPSR_csv_arr($file_name) {
    $arrResult = array();
    $handle = fopen($file_name, "r");
    if ($handle) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $arrResult[] = $data;
        }
        fclose($handle);
    }
    return $arrResult;
}

function WPSR_add_csv_mime_upload_mimes($existing_mimes) {
    $existing_mimes['csv'] = 'application/octet-stream'; //allow CSV files
    return $existing_mimes;
}

if ( isset($_POST['btn_import']) && $_POST['btn_import'] != '') {
    add_filter('upload_mimes', 'WPSR_add_csv_mime_upload_mimes');

    if (array_key_exists('import_file', $_FILES) && $_FILES['import_file']['name'] != '') {
        $filename = sanitize_text_field($_FILES['import_file']['name']);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (strtolower($ext) == 'csv') {
            if (!function_exists('wp_handle_upload')) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }

            $uploadedfile = sanitize_text_field($_FILES['import_file']);
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {


                WPSR_echo_message(__("File is valid, and was successfully uploaded.", 'seo-redirection'));
                $results = WPSR_csv_arr($movefile['file']);

                // start add to database ----------------------------------

                $index = 0;

                //if($request->post('col_names','int')!=0) $index++;
                if ($_POST['col_names'] != 0)
                    $index++;

                $errors = 0;
                $exist = 0;
                $new = 0;
                //$grpID=$request->post('grpID','int');
                $grpID = isset($_POST['grpID'])? (int) $_POST['grpID']:'';


                for ($i = $index; $i < count($results); $i++) {

                    $sql = "";
                    $redirect_from_type = 'Page';
                    $redirect_to_type = 'Page';
                    $redirect_from_folder_settings = '1';
                    $redirect_from_subfolders = '0';
                    $redirect_to_folder_settings = '1';
                    $redirect_type = '301';
                    $regex = '';
                    $redirect_from = '';
                    $redirect_to = '';

                    if (count($results[$i]) > 0)
                        $redirect_from = $results[$i][0];

                    if (count($results[$i]) > 1)
                        $redirect_to = $results[$i][1];

                    if (count($results[$i]) > 2)
                        $redirect_type = $results[$i][2];

                    if (count($results[$i]) > 3)
                        $redirect_from_type = $results[$i][3];

                    if (count($results[$i]) > 4)
                        $redirect_from_folder_settings = $results[$i][4];

                    if (count($results[$i]) > 5)
                        $redirect_from_subfolders = $results[$i][5];

                    if (count($results[$i]) > 6)
                        $redirect_to_type = $results[$i][6];

                    if (count($results[$i]) > 7)
                        $redirect_to_folder_settings = $results[$i][7];

                    if (count($results[$i]) > 8)
                        $regex = $results[$i][8];

                    if ($redirect_from != '' && $redirect_to != '' && intval($redirect_type) != 0) {
                        $exec = 0;
                        $seo_table = $table_prefix . "WP_SEO_Redirection";
                        if ($wpdb->get_var(" select redirect_from from $seo_table where redirect_from='$redirect_from'")) {
                            $exist++;
                            //if($request->post('rule')=='replace')
                            if ($_POST['rule'] == 'replace') {
                                $wpdb->get_var(" delete from $seo_table where redirect_from='$redirect_from'");
                                $exec = 1;
                            }
                        } else {
                            $exec = 1;
                            $new++;
                        }

                        if ($exec == 1) {

                            $wpdb->insert($seo_table, array(
                                "redirect_from" => $redirect_from,
                                "redirect_to" => $redirect_to,
                                "redirect_type" => $redirect_type,
                                "redirect_from_type" => $redirect_from_type,
                                "redirect_from_folder_settings" => $redirect_from_folder_settings,
                                "redirect_from_subfolders" => $redirect_from_subfolders,
                                "redirect_to_type" => $redirect_to_type,
                                "redirect_to_folder_settings" => $redirect_to_folder_settings,
                                "regex" => $regex,
                            ));
                        }
                    } else {
                        echo "err";
                        $errors++;
                    }
                }

                $report = intval($errors + $exist + $new) . " redirects are imported with $errors errors,$new new redirects and $exist are ";
                //if($request->post('rule')=='replace')
                if ($_POST['rule'] == 'replace') {
                    $report = $report . 'replaced!';
                } else {
                    $report = $report . 'skipped!';
                }

                WPSR_echo_message($report);

                // end the entrance to database ---------------------------


                unlink($movefile['file']);
                WPSR_echo_message(__("File is deleted!", 'seo-redirection'));
                $SR_redirect_cache->free_cache();
            } else {
                echo esc_html($movefile['error']);
            }
        } else {
            WPSR_echo_message(__("Please choose a CSV file", 'seo-redirection'), 'danger');
        }
    } else {
        WPSR_echo_message(__("You need to select a file to upload it!", 'seo-redirection'), 'danger');
    }
}
?>



<h3><?php _e("Import Redirects", 'seo-redirection'); ?></h3><hr/>

<form id="import" name="import" enctype='multipart/form-data' action="<?php echo WPSR_get_current_parameters(array("add", "edit", "del")); ?>" method="post" class="form-horizontal" role="form" data-toggle="validator">

    <table cellpadding="10">
        <tr>
            <td><label class="control-label col-sm-2" for="import_file_type"><?php _e("File Type:", 'seo-redirection') ?></label></td>
            <td>
                <?php
                $drop = new dropdown_list('import_file_type');
                $drop->add(__('CSV', 'seo-redirection'), 'csv');
                $drop->run($SR_jforms);
                ?>
            </td>
        </tr>
        <tr>
            <td><label class="control-label col-sm-2" for="file"><?php _e("Choose File:", 'seo-redirection'); ?></label></td>
            <td>
                <input class="btn btn-default btn-sm" type="file" accept="text/csv" id="import_file" name="import_file" required/>
            </td>
        </tr>
        <tr>
            <td><label class="control-label col-sm-2" for="Rule"><?php _e("Column Titles:", 'seo-redirection') ?> </label></td>
            <td>
                <input type="checkbox" autocomplete="off" value="1" id="col_names" name="col_names" checked="checked">
				<?php
                
                echo __(" Skip the first row of the file (if there is a table header)", 'seo-redirection');
                ?>
            </td>
        </tr>
        <tr>
            <td>  <label class="control-label col-sm-2" for="Rule"><?php _e("Import Rule:", 'seo-redirection'); ?></label></td>
            <td>
                <?php
                $drop = new dropdown_list('rule');
                $drop->add(__('Skip the existing redirects with the same source', 'seo-redirection'), 'skip');
                $drop->add(__('Replace the existing redirects with the same source', 'seo-redirection'), 'replace');
                $drop->run($SR_jforms);
                ?>
            </td>
        </tr>
    </table>

    <input type="hidden" name="MAX_FILE_SIZE" value="999000000" />
    <br>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-12">
            <button class="button" type="submit" name="btn_import" value="btn_import"><span  style="padding-top: 3px;" class="dashicons dashicons-migrate"></span>&nbsp;<?php _e("Import", 'seo-redirection') ?></button>
        </div>
    </div>
    <br/>
    <h3><a target="_blank" href="<?php echo WPSR_URL . 'custom/export/sample.csv' ?>">Sample Csv File</a></h3>
    <div style="text-align: right"><?php _e("* Need Help?", 'seo-redirection'); ?> <a target="_blank" href="https://www.wp-buy.com/knowledge-base/category/seo-redirection-free-plugin/"><?php _e("click here to see info about import and export", "seo-redirection"); ?></a></div>
    <br/>
</form>

<?php
/* Import redirects from the redirection plugin*/

$plugins = get_option( 'active_plugins', array() );
$found = false;
foreach ( $plugins as $plugin ) 
{
	if ( strpos( strval($plugin), 'redirection.php' ) == true && strpos( strval($plugin), 'seo-redirection.php' ) == FALSE ) 
	{
		$found = true;
		//$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}redirection_items" );
		$total = WPSR_getRedirectCount();
                if($total > 0){
                        ?>
                        <h3><?php _e("Import redirects from the redirection plugin", 'seo-redirection') ?></h3><hr/>
                        <div class="import_link_wrap">
                                <button type='button' data-toggle="modal"  class="button" href="#" data-target="#import_modal"  value="btn_import"><span style="margin-top: 3px;" class="dashicons dashicons-migrate"></span>&nbsp; <?php  _e('Import Now','seo-redirection'); ?></button>
                        </div>
                        <?php	
                }
		break;
	}
	
}