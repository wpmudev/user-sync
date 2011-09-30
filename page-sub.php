<?php
            $user_sync_key   = $this->options['key'];
            $user_sync_url_c = $this->options['central_url'];
            $disabled = '';

            if ( "" != $user_sync_url_c )
                $disabled = 'readonly="readonly"';

        ?>
            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    jQuery( "#user_sync_form" ).submit( function () {
                        if ( "" == jQuery( "#user_sync_url_c" ).val() && "uninstall" != jQuery( "#usync_action" ).val( ) ) {
                            alert( "<?php _e( 'Please write URL of Central blog', 'user-sync' ) ?>" );
                            return false;
                        }

                        if ( "" == jQuery( "#user_sync_key" ).val() && "uninstall" != jQuery( "#usync_action" ).val( ) ) {
                            alert( "<?php _e( 'Please write Key of Central blog', 'user-sync' ) ?>" );
                            return false;
                        }

                        return true;
                    });

                    jQuery(".tooltip_img[title]").tooltip();


                    //uninstall plugin options
                    jQuery( "#uninstall_yes" ).click( function() {
                        jQuery( "#usync_action" ).val( "uninstall" );
                        jQuery( "#user_sync_form" ).submit();
                        return false;
                    });

                    jQuery( "#uninstall" ).click( function() {
                        jQuery( "#uninstall_confirm" ).show( );
                        return false;
                    });

                    jQuery( "#uninstall_no" ).click( function() {
                        jQuery( "#uninstall_confirm" ).hide( );
                        return false;
                    });

                });


            </script>
            <div class="wrap">

                <?php
                //Debug mode
                if ( isset( $this->options['debug_mode'] ) && '1' == $this->options['debug_mode'] ):?>
                <div class="updated fade"><p><?php _e( 'Debug Mode is activated.', 'user-sync' ); ?></p></div>
                <?php endif; ?>

                <h2><?php _e( 'Subsite Settings', 'user-sync' ) ?></h2>
                <h3><?php _e( 'All user data from the Master site will be synchronized with this site.', 'user-sync' ) ?></h3>
                <form method="post" action="" id="user_sync_form">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'URL of Master site:', 'user-sync' ) ?>
                            </th>
                            <td>
                                <input type="text" name="user_sync_url_c" id="user_sync_url_c" value="<?php echo $user_sync_url_c; ?>" size="50" <?php echo $disabled; ?> />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'Key of Master site:', 'user-sync' ) ?>
                            </th>
                            <td>
                                <input type="text" name="user_sync_key" id="user_sync_key" value="<?php echo $user_sync_key; ?>" size="50" <?php echo $disabled; ?> />
                            </td>
                        </tr>
                    <?php if ( "" == $disabled ) {?>
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'Default settings:', 'user-sync' ) ?>
                            </th>
                            <td>
                                <span class="description">
                                    <?php _e( 'You can change these settings later on the Master site.', 'user-sync' ) ?>
                                </span>
                                <br />
                                <label>
                                    <input type="checkbox" name="replace_user" value="1" />
                                    <?php _e( 'Do not replace deleted users', 'user-sync' ) ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" name="overwrite_user" value="1" />
                                    <?php _e( 'Don\'t overwrite any existing users (add them as extra users)', 'user-sync' ) ?>
                                </label>
                            </td>
                        </tr>
                    <?php } ?>
                    </table>
                    <?php if ( "" == $disabled ) {?>
                        <p><?php _e( "Note: Use caution if you choose to overwrite existing users as it replaces all existing users and their passwords if the same username exists on the subsite.", 'user-sync' ) ?></p>
                    <?php }?>

                    <?php if ( "" == $disabled ) {?>
                        <?php
                        //safe mode notice
                        if ( isset( $this->safe_mode_notice ) ):?>
                            <span style="color: red;" ><?php _e( 'Attantion: Safe Mode!', 'user-sync' ); ?></span>
                            <img class="tooltip_img" src="<?php echo $this->plugin_url . "images/"; ?>info_small.png" title="<?php echo $this->safe_mode_notice; ?>"/>
                            <br />
                        <?php endif; ?>
                        <p class="submit">
                            <input type="hidden" name="usync_action" id="usync_action" value="sub_site" />
                            <input type="submit" value="<?php _e( 'Connect this site to the Master site, and do a FULL synchronization', 'user-sync' ) ?>" />
                        </p>

                    <?php } else {?>
                        <p class="submit">
                            <input type="hidden" name="usync_action" id="usync_action" value="remove_settings" />
                            <input type="submit" value="<?php _e( 'Disconnect from the Master site', 'user-sync' ) ?>" />
                            <span class="description"><?php _e( 'Disconnect syncing with Master site', 'email-newsletter' ) ?></span>
                        </p>
                        <?php } ?>
                        <p class="submit">
                            <input type="button" id="uninstall" style="color: red;" value="<?php _e( 'Uninstall Options', 'user-sync' ) ?>" />
                            <span class="description"><?php _e( "Delete all plugin's options from DB.", 'email-newsletter' ) ?></span>
                            <span id="uninstall_confirm" style="display: none;">
                                <br />
                                <span class="submit">
                                    <span class="description"><?php _e( 'Are you sure?', 'email-newsletter' ) ?></span>
                                    <br />
                                    <input type="button" name="uninstall" id="uninstall_no" value="<?php _e( 'No', 'email-newsletter' ) ?>" />
                                    <input type="button" name="uninstall" id="uninstall_yes" value="<?php _e( 'Yes', 'email-newsletter' ) ?>" />
                                </span>
                            </span>
                        </p>

                        <p>
                            <?php _e( "For detailed 'how to use' instructions refer to:", 'user-sync' ) ?><br />
                            <a href="http://premium.wpmudev.org/project/wordpress-user-synchronization/installation/" target="_blank" ><?php _e( 'WordPress User Synchronization Installation and Use instructions.', 'user-sync' ) ?></a>
                        </p>

                </form>
            </div>