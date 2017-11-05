<?php
/**
 * @package Techotronic
 * @subpackage All in one Favicon
 *
 * @since 4.1
 * @author Arne Franken
 *
 * Frontend Favicon Settings
 */
?>
<div id="aio-favicon-<?php echo $this->identifier ?>-settings" class="postbox">
    <div class="inside">
        <h3 id="<?php echo $this->identifier ?>-settings"><?php echo $this->translatedIdentifier; echo " "; _e('Settings', AIOFAVICON_TEXTDOMAIN); ?></h3>
        <table class="form-table">
<?php
          // Loop over this list of icons.
          foreach ($this->faviconMap as $iconName => $iconType) { ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo AIOFAVICON_SETTINGSNAME .'-'. $iconName ?>"><?php echo $iconType . ' ' . $this->translatedIdentifier; ?>:</label>
                </th>
                <td width="32">
                    <div id="<?php echo $iconName ?>-favicon"></div>
                </td>
                <td>
                    <!-- text input field so URLs may be copy'n'pasted -->
                    <input id="<?php echo AIOFAVICON_SETTINGSNAME .'-'. $iconName ?>-text" type="text" name="<?php echo AIOFAVICON_SETTINGSNAME . '[' . $iconName ?>-text]" size="60" maxlength="100000" value="<?php echo array_key_exists($iconName, $this->aioFaviconSettings) ? $this->aioFaviconSettings[$iconName] : ''; ?>"/>
                    <!-- label for file input, is displayed as upload button. All browsers then trigger file upload but Mozilla, see aiofavicon.js for workaround. -->
                    <label id="<?php echo AIOFAVICON_SETTINGSNAME .'-'. $iconName ?>-button" name="<?php echo $iconName ?>-button" class="button-secondary trigger-file-input" for="<?php echo AIOFAVICON_SETTINGSNAME .'-'. $iconName ?>" ><?php _e('Upload') ?></label>
                    <br />
                    <?php //only display delete checkbox if a favicon was found.
                    if(!empty($this->aioFaviconSettings[$iconName])) { ?>
                    <input type="checkbox" name="delete-<?php echo $iconName ?>"/><?php _e('Check box to delete favicon.',AIOFAVICON_TEXTDOMAIN) ?>
                    <?php } ?>
                    <!-- input is hidden with width:0 and opacity:0 because some browsers will not display the file upload dialog if it's hidden with display:none -->
                    <input id="<?php echo AIOFAVICON_SETTINGSNAME .'-'. $iconName ?>" type="file" name="<?php echo $iconName ?>" size="50" maxlength="100000" accept="image/*" value="<?php echo array_key_exists($iconName, $this->aioFaviconSettings) ? $this->aioFaviconSettings[$iconName] : ''; ?>" style="width: 0; opacity: 0;"/>
                </td>
            </tr>
          <?php } ?>
        </table>
        <p class="submit">
            <input type="hidden" name="action" value="aioFaviconUpdateSettings"/>
            <input type="submit" name="aioFaviconUpdateSettings" class="button-primary" value="<?php _e('Save Changes') ?>"/>
        </p>
    </div>
</div>