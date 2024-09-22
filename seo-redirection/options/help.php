<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
$imgpath = $util->get_plugin_url() . 'custom/images/';
?>
<h2><img height="30px" src="<?php echo esc_url($imgpath) ?>help-icon.png">&nbsp;<?php _e("Help Center", "seo-redirection") ?>;</h2>
<hr />
<div class="row">
    <div class="col-sm-12">
        <p><?php _e('We recommend before contacting us and wait for reply, to explore the product page in our knowledge base, it contains many articles about how to use the plugin', 'seo-redirection') ?><br />
            <b><?php _e("To go to the", 'seo-redirection') ?> <a target="_blank" href="http://www.clogica.com/kb/topics/seo-redirection-premium"><?php _e("knowledge base click here", "seo-redirection") ?></a></b>
        </p>
        <br />
        <h2 style="display: inline; color: #636465; font-size:24px"><b><?php _e("Open a new ticket?", "seo-redirection") ?></b></h2>
    </div>
</div>
<div class="row">
    <div class="col-sm-10">



        <div class="form-group">
            <div class="col-sm-offset-1 col-sm-7">
                <p><?php _e("Please provide as much details as possible so we can best assist you. To update a previously submitted ticket, Please login.", 'seo-redirection') ?></p>

                <button class="button-secondary btn-custom btn-search" type="button" name="new_ticket" target="newwindow" onclick="window.open('https://www.wp-buy.com/support-center','_blank')" value="btn_send"><?php _e("Open a new ticket now", "seo-redirection") ?></button>
            </div>
        </div>

    </div>
</div>