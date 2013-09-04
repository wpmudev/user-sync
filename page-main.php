            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    jQuery.fn.makeChose = function ( id ) {
                        if ( 1 == id )
                            jQuery( "#user_sync_status" ).val( 'central' );
                        else
                            jQuery( "#user_sync_status" ).val( 'sub' );

                        jQuery( "#user_sync_form" ).submit();
                    };
                });
            </script>

            <div class="wrap">
                <h2><?php _e( 'Site Type', 'user-sync' ) ?></h2>
                <p><?php _e( "User sync works by making one site a 'Master' site and all other sites 'Sub'.", 'user-sync' ) ?></p>
                <p><?php _e( 'If you want to sync all the users from this site with other sites, make this a Master site.', 'user-sync' ) ?></p>
                <p><?php _e( 'However, if you want to sync the users from other sites to this site, make this a Sub site.', 'user-sync' ) ?></p>
                <p><?php _e( 'You can always change these options later by deactivating and then reactivating the plugin.', 'user-sync' ) ?></p>
                <p><?php _e( 'NB: You must have at least one Master site!', 'user-sync' ) ?></p>
                <p>
                    <?php _e( "For detailed 'how to use' instructions refer to:", 'user-sync' ) ?><br />
                    <a href="http://premium.wpmudev.org/project/wordpress-user-synchronization/installation/" target="_blank" ><?php _e( 'WordPress User Synchronization Installation and Use instructions.', 'user-sync' ) ?></a>
                </p>

                <?php
                //safe mode notice
                if ( isset(  $this->safe_mode_notice ) ) {
                   echo '<div id="message" class="error fade"><p> '. $this->safe_mode_notice . '</p></div>';
                }
                ?>

                <form method="post" action="" id="user_sync_form">
                    <input type="hidden" name="usync_action" value="install" />
                    <input type="hidden" name="user_sync_status" id="user_sync_status" value="" />

                                <p class="debug_message" >

                                    <?php _e( 'Note: If you have any problems with sync users you can use debug mode for writing some operations in the log file. You need open folder "/plugins/user-sync/log/" for writing. What do with log files you can read in instruction of plugin', 'user-sync' );  ?>
                                    <a href="http://premium.wpmudev.org/project/wordpress-user-synchronization/installation/" target="_blank" ><?php _e( 'here', 'user-sync' ) ?></a>
                                    <br /><br />
                                    <input type="checkbox" name="debug" id="debug" value="1" />
                                    <label for="debug"><?php _e( 'Use Debug Mode', 'user-sync' ) ?></label>

                                </p>

                            <p>
                            <input type="button" class="button" value="<?php _e( 'Make this site the Master site', 'user-sync' ) ?> " onclick="jQuery(this).makeChose( 1 );" />
                            <input type="button" class="button" value="<?php _e( 'Make this a Sub site', 'user-sync' ) ?> " onclick="jQuery(this).makeChose( 2 );" />
                            </p>
                </form>
            </div>