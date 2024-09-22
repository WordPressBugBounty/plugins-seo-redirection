<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
global $wpdb, $table_prefix, $util;
$table_name = $table_prefix . 'WP_SEO_Redirection';



$rlink = $util->WPSR_get_current_parameters(array('search', 'page_num', 'add', 'edit', 'tab'));

$redirect_from = isset($redirect_from) ? $redirect_from : '';
$redirect_to = isset($redirect_to) ? $redirect_to : '';
?>
<br />

<script type="text/javascript">
    //---------------------------------------------------------

    function check_valid_redirect_from() {
        var site = "<?php echo home_url(); ?>";
        var redirect_from = document.getElementById('redirect_from').value;
        var redirect_from_type = document.getElementById('redirect_from_type').value;

        if ((redirect_from_type == 'Page' || redirect_from_type == 'Folder') && redirect_from != "") {
            if (redirect_from.length >= site.length) {
                if (redirect_from.substr(0, site.length) == site) {
                    return true;
                }
            }
            if (redirect_from.substr(0, 1) == '/') {
                return true;
            }
        } else {
            return true;
        }
        return false;
    }

    function check_redirect_from_all() {
        check_redirect_from();
        var valid_url = check_valid_redirect_from();
        if (!valid_url) {
            document.getElementById('invalid_redirect_from').style.display = 'block';
        } else {
            document.getElementById('invalid_redirect_from').style.display = 'none';
        }
    }


    function go_search() {
        <?php
        isset($_REQUEST['tab']) ? $url_op = WPSR_sanitize_text_or_array_field($_REQUEST['tab']) : $url_op = "";
        ?>
        var sword = document.getElementById('search').value;
        if (sword != '') {


            var url = "<?php echo sprintf('%s&tab=%s', esc_url($rlink), esc_html($url_op)); ?>&search=" + sword;
            url = decodeURIComponent(url);


            window.location = url;
        } else {
            alert('<?php _e("Please input any search words!", 'seo-redirection') ?>');
            document.getElementById('search').focus();
        }

    }
</script>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Custom Redirects</h4>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close" onclick="$('#myModal').modal('hide')"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">

                <form onsubmit="return check_from();" method="POST" id="myform" action="<?php echo esc_attr($util->WPSR_get_current_parameters(array('add', 'edit', 'page404'))); ?>">
                    <table class="cform" width="100%">
                        <tr>
                            <td class="label"><?php _e('Redirect status:', 'seo-redirection') ?></td>
                            <td> <select size="1" name="enabled" id="enabled">
                                    <option value="1"><?php _e('Enabled', 'seo-redirection') ?></option>
                                    <option value="0"><?php _e('Disabled', 'seo-redirection') ?></option>
                                </select>

                            </td>
                        </tr>
                        <tr>

                            <td class="label"><?php _e('Redirect from:', 'seo-redirection') ?></td>
                            <td>
                                <div id="rfrom_div">
                                    <select onchange="redirect_from_type_change()" size="1" name="redirect_from_type" id="redirect_from_type">
                                        <option value="Page"><?php _e('Page', 'seo-redirection') ?></option>
                                        <option value="Folder"><?php _e('Folder', 'seo-redirection') ?></option>
                                        <option value="Regex"><?php _e('Regex', 'seo-redirection') ?></option>
                                    </select>
                                    <input onblur="check_redirect_from_all()" type="text" id="redirect_from" style="height: 40px;" placeholder="<?php _e("Redirect from", 'seo-redirection') ?>" name="redirect_from" size="45" value="<?php echo esc_attr($redirect_from); ?>">
                                    <span class="help-block"></span>
                                    <select onchange="redirect_to_folder_settings_change()" size="1" name="redirect_from_folder_settings" id="redirect_from_folder_settings">
                                        <option value="1"><?php _e('Only the folder', 'seo-redirection') ?></option>
                                        <option value="2"><?php _e("The folder and it's content", 'seo-redirection') ?></option>
                                        <option value="3"><?php _e("Only the folder's content", 'seo-redirection') ?></option>
                                    </select>
                                    <br>
                                    <select size="1" name="redirect_from_subfolders" id="redirect_from_subfolders" class="cmb2_select">
                                        <option value="0"><?php _e("Include sub-folders", 'seo-redirection') ?></option>
                                        <option value="1"><?php _e("Do not include sub-folders", 'seo-redirection') ?></option>
                                    </select>


                                </div>
                                <?php if ($util->get('page404') != '') echo esc_html($redirect_from); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><?php _e("Redirect to:", 'seo-redirection') ?></td>
                            <td>
                                <select onchange="redirect_to_type_change()" size="1" class="cmb2_select" name="redirect_to_type" id="redirect_to_type">
                                    <option value="Page"><?php _e("Page:", 'seo-redirection') ?></option>
                                    <option value="Folder"><?php _e("Folder", 'seo-redirection') ?></option>
                                </select>

                                <input onblur="check_redirect_to()" type="text" id="redirect_to" placeholder="<?php _e("Redirect to", 'seo-redirection') ?>" class="regular-text" style="height: 40px;" name="redirect_to" size="45" value="<?php echo esc_attr($redirect_to); ?>">
                                <span class="help-block"></span>
                                <select size="1" name="redirect_to_folder_settings" id="redirect_to_folder_settings">
                                    <option value="1"><?php _e("Normal", 'seo-redirection') ?></option>
                                    <option value="2"><?php _e("Wild Card Redirect", 'seo-redirection') ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><?php _e("Redirect type:", 'seo-redirection') ?></td>
                            <td>
                                <select size="1" name="redirect_type" id="redirect_type">
                                    <option value="301"><?php _e("301 (SEO)", 'seo-redirection') ?></option>
                                    <option value="302"><?php _e("302", 'seo-redirection') ?></option>
                                    <option value="307"><?php _e("307", 'seo-redirection') ?></option>
                                </select>
                                <script type="text/javascript">
                                    <?php
                                    if (isset($redirect_type) && $redirect_type != '')
                                        echo "document.getElementById('redirect_type').value='" . esc_attr($redirect_type) . "';";

                                    if (isset($redirect_from_type) && $redirect_from_type != '')
                                        echo "document.getElementById('redirect_from_type').value='" . esc_attr($redirect_from_type) . "';";

                                    if (isset($redirect_from_folder_settings) && $redirect_from_type == 'Folder')
                                        echo "document.getElementById('redirect_from_folder_settings').value='" . esc_attr($redirect_from_folder_settings) . "';";

                                    if (isset($redirect_from_subfolders) && $redirect_from_type == 'Folder')
                                        echo "document.getElementById('redirect_from_subfolders').value='" . esc_attr($redirect_from_subfolders) . "';";

                                    if (isset($redirect_to_type) && $redirect_to_type != '')
                                        echo "document.getElementById('redirect_to_type').value='" . esc_attr($redirect_to_type) . "';";

                                    if (isset($redirect_to_folder_settings) && $redirect_to_type == 'Folder')
                                        echo "document.getElementById('redirect_to_folder_settings').value='" . esc_attr($redirect_to_folder_settings) . "';";

                                    if (isset($enabled) && $enabled != '')
                                        echo "document.getElementById('enabled').value='" . esc_attr($enabled) . "';";


                                    if ($util->get('page404') != '')
                                        echo "document.getElementById('rfrom_div').style.display = 'none';";
                                    ?>
                                </script>
                            </td>
                        </tr>
                    </table>

                    <label id="msg_response">
                    </label>
                    <br />
            </div>
            <div class="modal-footer">
                <?php
                echo '<input  class="button-primary btn-custom btn-add" id="btnSave" type="button" value="' . __("Add New", "seo-redirection") . '"  onclick="return save_function()">';
                ?>
                <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('seoredirection')); ?>" />
                <input type="hidden" id="edit" name="edit" value="<?php echo intval($util->get('edit')) ?>">
                <input type="hidden" id="add_new" name="add_new" value="">
                <input type="hidden" id="edit_exist" name="edit_exist" value="">
                <input type="hidden" id="action" name="action" value="customAddUpdate">

                <button type="button" style="margin-left: 4px;" class="button-primary btn-custom btn-delete" data-dismiss="modal" onclick="$('#myModal').modal('hide')"> <?php _e("Close", 'seo-redirection') ?> </button>
            </div>
        </div>
        </form>
    </div>
</div>
<div class="link_buttons">

    <table border="0" width="100%">
        <tr>
            <td> <button type="button" class="button-secondary btn-custom btn-add" onclick="add_rec()">
                    <span style="padding-top: 5px;" class="dashicons dashicons-plus"></span><?php _e('Add New', 'seo-redirection') ?>
                </button>

                <label id="waiting_lbl">
                    <div class="loading" style="display: none">Loading&#8230;</div>
                </label>
            </td>
            <td align="right" class="btn-right">
                <input onkeyup="if (event.keyCode == 13)
								go_search();" style="height: 30px;  border-radius: 3px !important;" id="search" type="text" name="search" value="<?php echo htmlentities($util->get('search'), ENT_QUOTES) ?>" size="30">
                <a class="button btn-custom btn-search" onclick="go_search()" href="#"><span style="padding-top: 3px;" class="dashicons dashicons-search "></span>&nbsp;<?php _e("Search", 'seo-redirection') ?></a>
                <a class="button btn-custom btn-search" href="<?php echo esc_url(htmlentities($util->WPSR_get_current_parameters('search'))) ?>"><span style="padding-top: 3px;" class="dashicons dashicons-screenoptions"></span>&nbsp;<?php _e("Show All", 'seo-redirection') ?></a>
            </td>
        </tr>
    </table>


</div>
<form method='post'>
    <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('seoredirection')); ?>" />
    <?php
    $grid = new datagrid();


    $grid->set_data_source($table_name);
    $grid->add_select_field('ID');
    $grid->add_select_field('redirect_from');
    $grid->add_select_field('redirect_from_type');
    $grid->add_select_field('redirect_to');
    $grid->add_select_field('redirect_to_type');
    $grid->add_select_field('hits');
    $grid->add_select_field('access_date');
    $grid->add_select_field('enabled');



    //$grid->add_select_field('redirect_hits');
    //$grid->add_select_field('redirect_last_access');


    $grid->set_table_attr('width', '100%');
    $grid->set_table_attr('class', 'wp-list-table widefat fixed striped');

    $grid->set_col_attr(1, 'width', '10px', 'header');
    $grid->set_col_attr(2, 'width', '30%', 'header');
    $grid->set_col_attr(3, 'width', '30%', 'header');
    $grid->set_col_attr(4, 'width', '50px', 'header');
    $grid->set_col_attr(4, 'style', 'text-align:center;', 'header');
    $grid->set_col_attr(5, 'width', '50px', 'header');
    $grid->set_col_attr(5, 'style', 'text-align:center;', 'header');
    $grid->set_col_attr(6, 'width', '150px', 'header');
    $grid->set_col_attr(6, 'style', 'text-align:center;', 'header');
    $grid->set_col_attr(7, 'width', '60px', 'header');
    $grid->set_col_attr(8, 'width', '60px', 'header');

    $grid->set_col_attr(4, 'style', 'text-align:center;');
    $grid->set_col_attr(5, 'style', 'text-align:center;');
    $grid->set_col_attr(6, 'style', 'text-align:center;');



    if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'hits') {
        if (isset($_REQUEST['sort']) && $_REQUEST['sort'] != '')
            $grid->set_order('hits ' . sanitize_text_field($_REQUEST['sort']));
    } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'dt') {
        if (isset($_REQUEST['sort']) && $_REQUEST['sort'] != '')
            $grid->set_order('hits ' . sanitize_text_field($_REQUEST['sort']));
    } else
        $grid->set_order(" ID desc ");

    $grid->set_filter("url_type=1");

    if ($util->get('search') != '') {
        $search = $util->get('search');
        $grid->set_filter("url_type=1 and (redirect_from like '%%$search%%' or redirect_to like '%%$search%%' or redirect_type like '%%$search%%'  )");
    }

    $grid->add_php_col("<input type='checkbox' class='chkthis' onclick='showhidebtn()' name='redirect_id[]' value='DB_ID' />", "<input type='checkbox' class='chkall' name='check_all' />");
    $grid->add_php_col("<div class='db_redirect_from_type_background_db_enabled'><a target='_blank' href='db_redirect_from_url'>db_redirect_from</a></div>", __('Redirect from ', 'seo-redirection'));
    $grid->add_php_col("<div class='db_redirect_to_type_background_db_enabled'><a target='_blank' href='db_redirect_to_url'>db_redirect_to</a></div>", __('Redirect to ', 'seo-redirection'));
    $grid->add_data_col('redirect_type', __('Type', 'seo-redirection'));


    $url = admin_url('options-general.php?page=' . sanitize_text_field($_REQUEST['page']));
    $url .= isset($_REQUEST['tab']) ? '&tab=' . sanitize_text_field($_REQUEST['tab']) : '';

    if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == 'asc') {
        $grid->add_data_col('hits', '<a class="hit text-white" href="' . esc_url($url) . '&type=hits&sort=desc" data-sort="desc">Hits <span class="dashicons dashicons-arrow-up"></span></a>');
        $grid->add_data_col('access_date', '<a href="' . esc_url($url) . '&type=dt&sort=desc" class="hit text-white" data-sort="desc">Last Access <span class="dashicons dashicons-arrow-up"></span></a>');
    } else if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == 'desc') {
        $grid->add_data_col('hits', '<a class="hit text-white" href="' . esc_url($url) . '&type=hits&sort=asc" data-sort="asc">Hits <span class="dashicons dashicons-arrow-up"></span></a>');
        $grid->add_data_col('access_date', '<a href="' . esc_url($url) . '&type=dt&sort=asc" class="dt text-white" data-sort="asc">Last Access <span class="dashicons dashicons-arrow-up"></span></a>');
    } else {
        $grid->add_data_col('hits', '<a class="hit text-white" href="' . esc_url($url) . '&type=hits&sort=asc" data-sort="asc">Hits <span class="dashicons dashicons-arrow-up"></span></a>');
        $grid->add_data_col('access_date', '<a href="' . esc_url($url) . '&type=dt&sort=asc" class="dt text-white" data-sort="asc">Last Access <span class="dashicons dashicons-arrow-up"></span></a>');
    }

    //$grid->add_template_col( $util->WPSR_get_current_parameters('del') . '&del={db_ID}', __('Actions', 'seo-redirection'));
    $grid->add_template_col('edit', '{db_ID}', __('', 'seo-redirection'));
    $grid->run();
    ?>
    <?php
    echo '<input style="display: none;"  class="button-primary btn-custom btn-delete btn-show" id="btnDelete" type="submit" value="' . __("Delete", "seo-redirection") . '"  onclick="return delete_function()">
	<style>
	
	td:first-child { text-align: left; width:30px }
	
	</style>
	';
    ?>
</form>
<script>
    function showhidebtn() {
        document.querySelector('.btn-show').style.display = "block";
    }
</script>