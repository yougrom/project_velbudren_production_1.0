<?php
/**
 * @package Techotronic
 * @subpackage All in one Favicon
 *
 * @since 4.1
 * @author Arne Franken
 *
 * Meta Settings
 */
?>
<div id="aio-favicon-meta-settings" class="postbox">
    <div class="inside">
        <h3 id="meta-settings"><?php _e('Settings', AIOFAVICON_TEXTDOMAIN); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="<?php echo AIOFAVICON_SETTINGSNAME ?>-removeReflectiveShine"><?php _e("Don't add reflective shine to Apple Touch Icon", AIOFAVICON_TEXTDOMAIN); ?>:</label>
                </th>
                <td width="32"></td>
                <td>
                    <input type="checkbox" name="<?php echo AIOFAVICON_SETTINGSNAME ?>[removeReflectiveShine]" id="<?php echo AIOFAVICON_SETTINGSNAME ?>-removeReflectiveShine" value="true" <?php echo ($this->aioFaviconSettings['removeReflectiveShine'])
                        ? 'checked="checked"' : '';?>/>
                    <br/><?php _e("Don't add reflective shine to Apple Touch Icon", AIOFAVICON_TEXTDOMAIN); ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?php echo AIOFAVICON_SETTINGSNAME ?>-removeLinkFromMetaBox"><?php _e('Remove link from Meta-box', AIOFAVICON_TEXTDOMAIN); ?>:</label>
                </th>
                <td width="32"></td>
                <td>
                    <input type="checkbox" name="<?php echo AIOFAVICON_SETTINGSNAME ?>[removeLinkFromMetaBox]" id="<?php echo AIOFAVICON_SETTINGSNAME ?>-removeLinkFromMetaBox" value="true" <?php echo ($this->aioFaviconSettings['removeLinkFromMetaBox'])
                        ? 'checked="checked"' : '';?>/>
                    <br/><?php _e('Remove the link to the developers site from the WordPress meta-box.', AIOFAVICON_TEXTDOMAIN); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="hidden" name="action" value="aioFaviconUpdateSettings"/>
            <input type="submit" name="aioFaviconUpdateSettings" class="button-primary" value="<?php _e('Save Changes') ?>"/>
        </p>
    </div>
</div>