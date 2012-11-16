  <?php
            $user_sync_key      = $this->options['key'];
            $user_sync_sub_urls = $this->options['sub_urls'];
            $user_sync_siteur   = get_option( 'siteurl' );

        ?>
            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    jQuery.fn.editSettings = function ( id ) {

                        if ( "Save" == jQuery( this ).val() ) {
                            jQuery( this ).attr( 'disabled', true );

                            var sub_url = jQuery( "#sub_url_" + id ).val();

                            if (true == jQuery( "#replace_user_" + id ).attr( 'checked' ) )
                                replace_user = 1;
                            else
                                replace_user = 0;

                            if (true == jQuery( "#overwrite_user_" + id ).attr( 'checked' ) )
                                overwrite_user = 1;
                            else
                                overwrite_user = 0;
                            jQuery( "#loading_" + id ).show();
                            jQuery.ajax({
                               type: "POST",
                               url: "<?php echo $user_sync_siteur;?>/wp-admin/admin-ajax.php",
                               data: "action=user_sync_settings&url=" + sub_url + "&replace_user=" + replace_user + "&overwrite_user=" + overwrite_user,
                               success: function(){
                                 jQuery( "input[value=Save]" ).val( 'Edit' );
                                 jQuery( "input[value=Edit]" ).attr( 'disabled', false );
                                 jQuery( "#loading_" + id ).hide();
                               }
                             });
                            jQuery( "#sub_list input.sett" ).attr( 'disabled', true );
                            jQuery( "#sub_list label" ).attr( 'class', 'description' );

                            return;

                        }

                        jQuery( "#sub_list input.sett" ).attr( 'disabled', true );
                        jQuery( "#sub_list label" ).attr( 'class', 'description' );

                        if ( "Edit" == jQuery( this ).val() ) {
                            jQuery( "#settings_" + id + " input" ).attr( 'disabled', false );
                            jQuery( "#settings_" + id + " label" ).attr( 'class', '' );
                            jQuery( this ).val('Close');
                            jQuery( "input[value=Edit]" ).attr( 'disabled', true );
                            return;
                        }

                        if ( "Close" == jQuery( this ).val() ) {
                            jQuery( this ).val('Edit');
                            jQuery( "input[value=Edit]" ).attr( 'disabled', false );
                            return;
                        }
                    };

                    jQuery( "#sub_list label" ).click(function () {
                        if ( ! jQuery( this ).find( 'input.sett' ).attr( 'disabled' ) ) {
                            jQuery( "input[value=Close]" ).val( 'Save' );
                        }

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

                <h2><?php _e( 'Master Site Settings', 'user-sync' ) ?></h2>
                <h3><?php _e( 'All user data from this master site will be synchronized with connected subsites.', 'user-sync' ) ?></h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php _e( 'URL of Master site:', 'user-sync' ) ?>
                        </th>
                        <td>
                            <input type="text" name=""  value="<?php echo $user_sync_siteur; ?>" readonly="readonly" size="50" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e( 'Security Key:', 'user-sync' ) ?>
                        </th>
                        <td>
                            <input type="text" name=""  value="<?php echo $user_sync_key; ?>" readonly="readonly" size="50" />
                        </td>
                    </tr>
                </table>

                <br />
            </div>
            <span class="description"><?php _e( "To create a sub site, simply install this plugin at the sub site, activate it, make that site a 'Sub' site and enter the URL (including http) and the key supplied here.", 'user-sync' ) ?></span>
            <p><?php _e( 'Use caution if you choose to overwrite existing users as it replaces all existing users and their passwords if the same username exists on the subsite.', 'user-sync' ) ?></p>
            <p><?php _e( 'Adding new user, or making any changes to user or their details, in the Master site are automatically synced to subsites.', 'user-sync' ) ?></p>
            <p><?php _e( "'Sync all sites now' is used if you edit a subsite setting and need to update users.", 'user-sync' ) ?></p>
            <p><?php _e( 'To stop a subsite from syncing with Master site simply log into the subsite, go to the User Sync menu and click on "Disconnect from the Master site" button or "Uninstall Options" button.', 'user-sync' ) ?></p>
            <p>
                <?php _e( "For detailed 'how to use' instructions refer to:", 'user-sync' ) ?><br />
                <a href="http://premium.wpmudev.org/project/wordpress-user-synchronization/installation/" target="_blank" ><?php _e( 'WordPress User Synchronization Installation and Use instructions.', 'user-sync' ) ?></a>
            </p>

            <?php
            //safe mode notice
            if ( isset( $this->safe_mode_notice ) ):?>
                <p>
                    <span style="color: red;" ><?php _e( 'Attention: Safe Mode!', 'user-sync' ); ?></span>
                    <img class="tooltip_img" src="<?php echo $this->plugin_url . "images/"; ?>info_small.png" title="<?php echo $this->safe_mode_notice; ?>"/>
                </p>
            <?php endif; ?>

            <p><?php _e( 'Registered Subsites:', 'user-sync' ) ?></p>
            <form method="post" action="" id="sub_list">
                <table width="700px" class="widefat post fixed" style="width:95%;">
                    <thead>
                        <tr>
                            <th style="width: 10px;">
                                #
                            </th>
                            <th>
                                <?php _e( 'URLs', 'user-sync' ) ?>
                            </th>
                            <th>
                                <?php _e( 'Last Sync', 'user-sync' ) ?>
                            </th>
                            <th  style="width: 400px;">
                                <?php _e( 'Settings', 'user-sync' ) ?>
                            </th>
                            <th>
                                <?php _e( 'Action', 'user-sync' ) ?>
                            </th>
                        </tr>
                    </thead>
                <?php
                $user_sync_i = 0;
                if ( $user_sync_sub_urls )
                    foreach( $user_sync_sub_urls as $one) {
                        if ($user_sync_i % 2 == 0)
                        {
                            echo "<tr class='alternate'>";
                        } else {
                            echo "<tr class='' >";
                        }
                        $user_sync_i++;
                ?>
                        <td style="vertical-align: middle;">
                           <?php echo $user_sync_i; ?>
                        </td>
                        <td style="vertical-align: middle;">
                           <?php echo $one['url']; ?>
                           <input type="hidden" id="sub_url_<?php echo $user_sync_i;?>"  value="<?php echo base64_encode($one['url']);?>"/>
                        </td>
                        <td style="vertical-align: middle;">
                        <?php echo $one['last_sync']; ?>
                        </td>
                        <td style="vertical-align: middle;">

                            <div class="settings_block" id="settings_<?php echo $user_sync_i;?>" >
                                <div class="loading_image" id="loading_<?php echo $user_sync_i;?>" ></div>
                                <label class="description" >
                                    <input type="checkbox" class="sett" name="replace_user" id="replace_user_<?php echo $user_sync_i;?>" value="1" disabled="disabled" <?php if ( "1" == $one['param']['replace_user']) echo 'checked="checked"'; ?> />
                                    <?php _e( 'Do not replace deleted users', 'user-sync' ) ?>
                                </label>
                                <br />
                                <label class="description">
                                    <input type="checkbox" class="sett" name="overwrite_user" id="overwrite_user_<?php echo $user_sync_i;?>" value="1" disabled="disabled" <?php if ( "1" == $one['param']['overwrite_user']) echo 'checked="checked"'; ?> />
                                    <?php _e( 'Don\'t overwrite any existing users (add them as extra users)', 'user-sync' ) ?>
                                </label>
                            </div>
                        </td>
                        <td style="vertical-align: middle;">
                        <input type="button" name="edit_button" id="actin_button_<?php echo $user_sync_i;?>" value="Edit" onclick="jQuery(this).editSettings( <?php echo $user_sync_i;?> );" />
                        </td>
                    </tr>
                <?php
                    }
                ?>
                </table>
            </form>

            <?php
            if ( $user_sync_sub_urls )  {
            ?>
            <br />
            <center>
            <form method="post" action="">
                <input type="hidden" name="usync_action" value="sync_all" />
                <input type="submit" value="<?php _e( 'Sync all sites now', 'user-sync' ) ?>"  />
            </form>
            </center>
            <br />
           <?php
           }
           ?>

            <form method="post" action="" id="user_sync_form">
                <input type="hidden" name="usync_action" id="usync_action" value="sync_all" />
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
            </form>
