<?php
/**
 * @package Techotronic
 * @subpackage All in one Favicon
 *
 * @since 1.0
 * @author Arne Franken
 *
 * HTML for settings page
 */
?>
<div class="wrap">
  <?php
  if (!is_plugin_active( 'sumome/sumome.php' )) 
  {
    add_thickbox();
    ?>
      <style type="text/css">
        #aio_global_notification a.button:active {vertical-align:baseline;}
      </style> 
      <div id="aio_global_notification" style="border:3px solid #31964D;position:relative;background:#6AB07B;color:#ffffff;height:70px;margin:5px 0 15px;padding:1px 12px;">
      <p style="font-size:16px;line-height:40px;">
      <?php _e('Tools to grow your Email List, Social Sharing and Analytics.'); ?> &nbsp;<a style="background-color: #6267BE;border-color: #3C3F76;" href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=sumome&TB_iframe=true&width=743&height=500'); ?>" class="thickbox button button-primary">Get SumoMe WordPress Plugin</a>
      </p>
      </div> 
    <?php
  }
  ?>
  <div>
    <?php screen_icon(); ?>
    <h1><?php echo AIOFAVICON_NAME . ' ' . __('Settings', AIOFAVICON_TEXTDOMAIN); ?></h1>
    <br class="clear"/>

    <div class="updated" id="javascriptWarning" style="background-color:#f66;">
      <p><?php _e("Please enable JavaScript. Upload-functionality on this page relies on JavaScript and will not work if it's not enabled.", AIOFAVICON_TEXTDOMAIN) ?></p>
    </div>

<?php
    require_once 'settings-page/sp-left-column.php';
    //require_once 'settings-page/sp-right-column.php';
    ?>
  </div>
<?php
  //require_once 'settings-page/sp-footer.php';
  ?>
</div>