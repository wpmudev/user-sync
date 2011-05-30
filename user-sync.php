<?php
/*
Plugin Name: User Synchronization
Plugin URI: http://premium.wpmudev.org/project/wordpress-user-synchronization
Description: User Synchronization - This plugin allows you to create a Master site from which you can sync a user list with as many other sites as you like - once activated get started <a href="admin.php?page=user-sync">here</a>
Version: 1.0 Beta 6
Author: Andrey Shipilov (Incsub)
Author URI: http://premium.wpmudev.org
WDP ID: 218

Copyright 2009-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
  }
}


/**
* Plugin main class
**/

class User_Sync {

    function User_Sync() {
        __construct();
    }

	/**
	 * PHP 5 constructor
	 **/
	function __construct() {
        if( isset( $_POST[ 'user_sync_settings' ] ) )
            add_action( 'admin_init', array( &$this, 'add_settings' ) );

        add_action( 'admin_head', array( &$this, 'add_css' ) );
        add_action( 'admin_menu', array( &$this, 'admin_page' ) );
        add_action( 'profile_update', array( &$this, 'user_change_data' ) );
        add_action( 'user_register', array( &$this, 'user_create' ) );
        add_action( 'delete_user', array( &$this, 'user_delete_data' ) );
        register_deactivation_hook ( __FILE__, array( &$this, 'deactivation' ) );
        load_plugin_textdomain( 'user-sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        add_action( 'wp_ajax_nopriv_user_sync_api', array( &$this, 'user_sync_ajax_action' ) );
        add_action( 'wp_ajax_user_sync_api', array( &$this, 'user_sync_ajax_action' ) );

        add_action( 'wp_ajax_nopriv_user_sync_settings', array( &$this, 'user_sync_edit_settings' ) );
        add_action( 'wp_ajax_user_sync_settings', array( &$this, 'user_sync_edit_settings' ) );

	}

    /**
     * Creating admin menu
     **/
    function admin_page() {
        add_menu_page( __( 'User Sync', 'user-sync' ), __( 'User Sync', 'user-sync' ), 'manage_options', 'user-sync', array( &$this, 'main_page' ) );
    }

    /**
     * Adding css style in Head section
     **/
    function add_css() {
    ?>
        <style type="text/css">
            .settings_block {
                position: relative;
            }

            .loading_image {
                -ms-filter:'progid:DXImageTransform.Microsoft.Alpha(Opacity=80)';
                filter: alpha(opacity=80);
                opacity: 0.8;
                width: 100%;
                height: 100%;
                position: absolute;
                background: url('<?php echo get_option( 'siteurl' );?>/wp-admin/images/loading.gif') #fff no-repeat center center;
                z-index: 1099;
                display: none;
            }

            #toplevel_page_user-sync
            .wp-menu-image a img{
                display: none;
            }

           #toplevel_page_user-sync
           div.wp-menu-image{
                background: url("<?php echo WP_PLUGIN_URL; ?>/user-sync/user-sync.png") no-repeat scroll 0px 0px transparent;
            }

            #toplevel_page_user-sync:hover
            div.wp-menu-image{
                background: url("<?php echo WP_PLUGIN_URL; ?>/user-sync/user-sync.png") no-repeat scroll 0px -32px transparent;
            }

            #toplevel_page_user-sync.current
            div.wp-menu-image{
                background: url("<?php echo WP_PLUGIN_URL; ?>/user-sync/user-sync.png") no-repeat scroll 0px -32px transparent;
            }


        </style>
    <?php
    }

    /**
     * Set site settings
     **/
    function add_settings() {
        switch( $_POST[ 'user_sync_settings' ] ) {
            //Saving choose of site "Central" or "Subsite"
            case "type_site":
                //creating additional options for central site
                if ( "central" == $_POST['user_sync_status'] ) {
                    add_option( "user_sync_status", $_POST['user_sync_status'], '', 'no' );
                    add_option( "user_sync_key", $this->gener_key(), '', 'no' );
                    add_option( "user_sync_sub_urls", "", '', 'no' );
                }

                //creating additional options for sub site
                if ( "sub" == $_POST['user_sync_status'] ) {
                    add_option( "user_sync_status", $_POST['user_sync_status'], '', 'no' );
                    add_option( "user_sync_deleted_users", "", '', 'no' );
                }
            break;

            //Creating additional options of Subsite and Saving URL of Central site and Security Key
            case "sub_site":
                if ( ! empty( $_POST['user_sync_url_c']) && ! empty( $_POST['user_sync_key'] ) ) {

                if ( false === get_option( 'user_sync_url_c' ) )
                    add_option( "user_sync_url_c", $_POST['user_sync_url_c'], '', 'no' );
                else
                    update_option( "user_sync_url_c", $_POST['user_sync_url_c'] );

                if ( false === get_option( 'user_sync_key' ) )
                    add_option( "user_sync_key", $_POST['user_sync_key'], '', 'no' );
                else
                    update_option( "user_sync_key", $_POST['user_sync_key'] );


                    //Call Synchronization when activating new Subsite
                    $result = $this->sync_new_subsite( $_POST['user_sync_url_c'], $_POST['user_sync_key'] );

                    if ( "ok" == $result ) {
                        wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'Subsite successfully connected to Master site and Synchronization completed.', 'user-sync' ) ) ), 'admin.php' ) );
                    } else {
                        update_option( "user_sync_url_c", "" );
                        update_option( "user_sync_key", "" );

                        wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'There was a connection problem. Please check the URL and Key of the Master site.', 'user-sync' ) ) ), 'admin.php' ) );
                    }
                }
            break;

            //Removing Subsite from Central list
            case "remove_settings":
                $p = base64_encode( get_option( 'siteurl' ) );

                $hash = md5( $p . get_option( 'user_sync_key' ) );

                //delete url from Sub list on central site
                $this->user_sync_send_request( get_option( 'user_sync_url_c' ), "user_sync_action=delete_subsite&hash=". $hash . "&p=" . $p );

                //reset options of Sub site
                update_option( "user_sync_url_c", "" );
                update_option( "user_sync_key", "" );
                update_option( "user_sync_deleted_users", "" );

                wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'Subsite and Settings were successfully removed from the Central site!', 'user-sync' ) ) ), 'admin.php' ) );
            break;

            //Call function for Synchronization of all Subsites
            case "sync_all":

                $this->sync_all_subsite();

                wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'Synchronization of all Subsites completed.', 'user-sync' ) ) ), 'admin.php' ) );
            break;
        }

    }

    /**
     * Deleting options and Sub url when deactivation plugin
     **/
    function deactivation() {
        //check type of blog
        if ( "sub" == get_option( 'user_sync_status' ) ) {
            $p = base64_encode( get_option( 'siteurl' ) );

            $hash = md5( $p . get_option( 'user_sync_key' ) );

            //delete url from Sub list on central site
            $this->user_sync_send_request( get_option( 'user_sync_url_c' ), "user_sync_action=delete_subsite&hash=". $hash . "&p=" . $p );
        }

        delete_option( 'user_sync_key' );
        delete_option( 'user_sync_sub_urls' );
        delete_option( 'user_sync_status' );
        delete_option( 'user_sync_url_c' );
        delete_option( 'user_sync_deleted_users' );
    }

    /**
     * Editing settings of Sub site
     **/
    function user_sync_edit_settings() {
        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );

        $array_id = $this->get_index_by_url( base64_decode($_REQUEST['url']), $user_sync_sub_urls );

        if ( -1 != $array_id ) {
            if ( "0" == $_POST['replace_user'] || "1" == $_POST['replace_user'] )
                $user_sync_sub_urls[$array_id]['param']['replace_user'] = $_POST['replace_user'];

            if ( "0" == $_POST['overwrite_user'] || "1" == $_POST['overwrite_user'] )
                $user_sync_sub_urls[$array_id]['param']['overwrite_user'] = $_POST['overwrite_user'];

            update_option( "user_sync_sub_urls", $user_sync_sub_urls );
        }
    }

    /**
     * Generating key of security
     **/
    function gener_key() {
        return wp_generate_password( 15 );
    }

    /**
     * Sending request on URL
     **/
    function user_sync_send_request( $url, $par ) {

        $url = $url . "/wp-admin/admin-ajax.php?action=user_sync_api&". $par;

        $args =  array(
            'timeout' => 90
            );

        $response = wp_remote_get( $url, $args );

        if( is_wp_error( $response ) ) {
//           echo 'Something went wrong!';
        } else {
           return $response["body"];
        }

    }

    /**
     * Checking key of security on Subsite
     **/
    function check_key( $url, $key ) {
        //generate rendom string
        $str = substr( md5( uniqid( rand(), true ) ), 0, 10);

        //get hash from Subsite
        $hash = $this->user_sync_send_request( $url, "str=" . $str );

        //checking hash from Subsite and Central site
        if ( $hash == md5( $str . "" . $key ) )
            return true;
        else
            return false;
    }

    /**
     *  Get all Users ID
     **/
    function user_sync_get_all_users_id() {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT ID FROM {$wpdb->base_prefix}users" );

        foreach( $rows as $row ) {
            $result[] = $row->ID;
        }
        return $result;
    }

    /**
     *  Get array index by URL
     **/
    function get_index_by_url( $url, $arr ) {
        $i = 0;
        foreach ( $arr as $one ) {
            if ( $url == $one['url'] )
                return $i;

            $i++;
        }

        return -1;
    }


    /**
     * Update other data of user
     **/
    function update_other_user_data( $userdata, $user_id ) {
        Global $wpdb;

        //Update password - becouse if add password "wp_update_user" will be double md5 - wrong password
        $result = $wpdb->query( $wpdb->prepare( "
            UPDATE {$wpdb->base_prefix}users SET
            user_pass = '%s' WHERE ID = '%d'",
            $userdata['user_pass'], $user_id ) );

        //Update email on blank if email is duplicate
        if ( "temp@temp.temp" == $userdata['user_email'] )
            $result = $wpdb->query( $wpdb->prepare( "
                UPDATE {$wpdb->base_prefix}users SET
                user_email = '%s' WHERE ID = '%d'",
                "", $user_id ) );

        //Update user Role
        $result = $wpdb->query( $wpdb->prepare( "
            UPDATE {$wpdb->base_prefix}usermeta SET
            meta_value = '%s' WHERE user_id = '%d' AND meta_key = 'wp_capabilities'",
            serialize( $userdata['wp_capabilities'] ), $user_id ) );

        //Update user Level
        $result = $wpdb->query( $wpdb->prepare( "
            UPDATE {$wpdb->base_prefix}usermeta SET
            meta_value = '%s' WHERE user_id = '%d' AND meta_key = 'wp_user_level'",
            $userdata['wp_user_level'], $user_id ) );

    }

    /**
     *  Synchronization user
     **/
    function sync_user( $users_id, $urls ) {
        $key        = get_option( 'user_sync_key' );
        $urls       = (array) $urls;
        $users_id   = (array) $users_id;

        foreach ( $urls as $one ) {
            //Checking key of security from Subsite
            if ( $this->check_key( $one['url'], $key ) )
                foreach ( $users_id as $user_id ) {
                    // get all information about user
                    $userdata = (array) get_userdata( $user_id );

                    $p = array ( 'param' => array( 'replace_user' => $one['param']['replace_user'], 'overwrite_user' => $one['param']['overwrite_user'] ),
                                'userdata' => $userdata );

                    $p =  base64_encode( serialize ( $p ) );
                    $hash = md5( $p . $key );

                    //sent information about user and hash to Subsite
                    $this->user_sync_send_request( $one['url'], "user_sync_action=sync_user&hash=". $hash . "&p=" . $p );

                    //Update last Sync date
                    $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );
                    $array_id = $this->get_index_by_url( $one['url'], $user_sync_sub_urls );
                    $user_sync_sub_urls[$array_id]['last_sync'] = date( "m.d.y G:i:s" );
                    update_option( "user_sync_sub_urls", $user_sync_sub_urls );
                }
        }
    }

    /**
     * Synchronization when new user creating
     **/
    function user_create( $userID ) {
        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );

        //Call Synchronization function with ID of created user and array of all Subsite URLs
        $this->sync_user( $userID, $user_sync_sub_urls );
    }

    /**
     * Synchronization when user edit profile
     **/
    function user_change_data( $userID ) {
        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );

        //Call Synchronization function with ID of changed user and array of all Subsite URLs
        $this->sync_user( $userID, $user_sync_sub_urls );
    }

    /**
     * Synchronization when user deleting
     **/
    function user_delete_data( $userID ) {
        $user_data = (array) get_userdata( $userID );

        $user_sync_status = get_option( 'user_sync_status' );

        if ( "sub" == $user_sync_status ) {
            //Adding login of user to list of deleted users on Sub site
            $user_sync_deleted_users = (array) get_option( 'user_sync_deleted_users' );

            if ( false === array_search( $user_data['user_login'], $user_sync_deleted_users ) ) {
                $user_sync_deleted_users[] = $user_data['user_login'];
                update_option( "user_sync_deleted_users", $user_sync_deleted_users );
            }
        } elseif ( "central" == $user_sync_status ) {
            $key                = get_option( 'user_sync_key' );
            $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );


            //Deleting user from all Subsite
            if ( false !== $user_sync_sub_urls )
                foreach ( $user_sync_sub_urls as $one ) {
                    //Checking key of security from Subsite
                    if ( $this->check_key( $one['url'], $key ) ) {

                        $p      = array( 'user_login' => $user_data['user_login'], 'overwrite_user' => $one['param']['overwrite_user'] );
                        $p      = base64_encode( serialize ( $p ) );
                        $hash   = md5( $p . $key );


                        $this->user_sync_send_request( $one['url'], "user_sync_action=sync_user_delete&hash=". $hash . "&p=" . $p );

                        //Update last Sync date
                        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );
                        $array_id = $this->get_index_by_url( $one['url'], $user_sync_sub_urls );
                        $user_sync_sub_urls[$array_id]['last_sync'] = date( "m.d.y G:i:s" );
                        update_option( "user_sync_sub_urls", $user_sync_sub_urls );
                    }
                }
        }
    }

    /**
     *  Synchronization when activating new Subsite
     **/
    function sync_new_subsite( $central_url, $key ) {
        $replace_user   = 0;
        $overwrite_user = 0;

        //Settings of Sub site
        if ( "1" == $_POST['replace_user'] )
            $replace_user = 1;

        if ( "1" == $_POST['overwrite_user'] )
            $overwrite_user = 1;

        $p = array ('url' => get_option( 'siteurl' ), 'replace_user' => $replace_user, 'overwrite_user' => $overwrite_user );
        $p =  base64_encode( serialize ( $p ) );

        $hash = md5( $p . $key );

        $result = $this->user_sync_send_request( $central_url, "user_sync_action=sync_new_subsite&hash=". $hash . "&p=" . $p );

        return $result;
    }

    /**
     *  Synchronization of all Subsites
     **/
    function sync_all_subsite() {
        //Get all users ID
        $user_sync_users_id = $this->user_sync_get_all_users_id();

        //Get all URLs of Subsites
        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );

        //Call Synchronization for all Subsites
        $this->sync_user( $user_sync_users_id, $user_sync_sub_urls );
    }

    /**
     * Ajax function for changes data on remote site
     **/
    function user_sync_ajax_action() {
        $user_sync_key = get_option( "user_sync_key" );

        if ( false === $user_sync_key )
            die( "" );

        //action for checking security key on Subsite
        if ( "" != $_REQUEST['str'] ) {
            die( md5( $_REQUEST['str'] . $user_sync_key ) );
        }

        if ( "" == $_REQUEST['user_sync_action'] )
            die( "" );

        if ( "" != $_REQUEST['p'] && "" != $_REQUEST['hash'] ) {
            //checking hash sum
            if ( $_REQUEST['hash'] == md5( $_REQUEST['p'] . $user_sync_key ) ) {
                $p = base64_decode( $_REQUEST['p'] );

                global $wpdb;

                switch( $_REQUEST[ 'user_sync_action' ] ) {
                    //action for Synchronization user
                    case "sync_user":
                        $p = unserialize( $p );

                        $user_sync_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->base_prefix}users WHERE user_login = '%s'", $p['userdata']['user_login'] ) );
                        $user_sync_id = $user_sync_id['0']->ID;

                        if( $user_sync_id ) {
                            //Update user
                            //checking settings of overwrite user and flag of users that sync from master site
                            if ( 1 == $p['param']['overwrite_user'] && "1" != get_user_meta( $user_sync_id, "user_sync", true ) ) {

                                $user_sync_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->base_prefix}users WHERE user_login = '%s'", $p['userdata']['user_login'] . "_sync" ) );
                                $user_sync_id = $user_sync_id['0']->ID;

                                //if user exist we have new ID in $user_sync_id and we can use code below for Update user data
                                if( ! $user_sync_id ) {
                                    //changing user login adding  _sync
                                    $p['userdata']['user_login'] = $p['userdata']['user_login'] . "_sync";

                                    //checking email of user on duplicate
                                    if ( $user_sync_id != email_exists( $p['userdata']['user_email'] ) && false != email_exists( $p['userdata']['user_email'] ) )
                                        $p['userdata']['user_email'] = "temp@temp.temp";

                                    //delete user ID for Insert new user
                                    array_splice( $p['userdata'], 0, 1 );

                                    //cut user password
                                    $user_sync_pass  = array_splice( $p['userdata'], 1, 1 );

                                    //Insert new user
                                    $user_sync_last_id = wp_update_user ( $p['userdata'] );

                                    //adding user password back for updating it
                                    $p['userdata']['user_pass'] = $user_sync_pass['user_pass'];

                                    //Update other data of user
                                    $this->update_other_user_data( $p['userdata'], $user_sync_last_id );


                                    return;
                                }
                            }
                            $p['userdata']['ID'] = $user_sync_id;

                            //cut user password
                            $user_sync_pass  = array_splice($p['userdata'], 2, 1);

                            //checking email of user on duplicate
                            if ( $user_sync_id != email_exists( $p['userdata']['user_email'] ) && false != email_exists( $p['userdata']['user_email'] ) )
                                $p['userdata']['user_email'] = "temp@temp.temp";

                            //update user data
                            $user_sync_last_id = wp_update_user ( $p['userdata'] );

                            //adding user password back for updating it
                            $p['userdata']['user_pass'] = $user_sync_pass['user_pass'];

                            //Update other data of user
                            $this->update_other_user_data( $p['userdata'], $user_sync_last_id );

                        } else {
                            if ( 1 == $p['param']['replace_user'] ) {
                                $user_sync_deleted_users = get_option( 'user_sync_deleted_users' );

                                if ( false !== array_search( $p['userdata']['user_login'], $user_sync_deleted_users ) )
                                    return;
                            }

                            //delete user ID for Insert new user
                            array_splice( $p['userdata'], 0, 1 );

                            //cut user password
                            $user_sync_pass  = array_splice( $p['userdata'], 1, 1 );

                            //checking email of user on duplicate
                            if ( $user_sync_id != email_exists( $p['userdata']['user_email'] ) && false != email_exists( $p['userdata']['user_email'] ) )
                                $p['userdata']['user_email'] = "temp@temp.temp";

                            //Insert new user
                            $user_sync_last_id = wp_update_user ( $p['userdata'] );

                            //adding user password back for updating it
                            $p['userdata']['user_pass'] = $user_sync_pass['user_pass'];

                            //Update other data of user
                            $this->update_other_user_data( $p['userdata'], $user_sync_last_id );

                            //flag for users that sync from master site
                            add_user_meta( $user_sync_last_id, "user_sync", "1", false );



                        }

                        die( "ok" );
                    break;

                    //action for Synchronization when deleting user
                    case "sync_user_delete":
                        $p = unserialize( $p );

                        //checking that user exist
                        $user_sync_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->base_prefix}users WHERE user_login = '%s'", $p['user_login'] ) );
                        $user_sync_id = $user_sync_id['0']->ID;

                        if( $user_sync_id ) {
                            //Update user
                            //checking settings of overwrite user and flag of users that sync from master site
                            if ( 1 == $p['overwrite_user'] && "1" != get_user_meta( $user_sync_id, "user_sync", true ) ) {

                                $user_sync_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->base_prefix}users WHERE user_login = '%s'", $p['user_login'] . "_sync" ) );
                                $user_sync_id = $user_sync_id['0']->ID;

                                if( ! $user_sync_id )
                                    return;

                            }

                            //deleting user
                            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}usermeta WHERE user_id = %d", $user_sync_id ) );
                            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}users WHERE ID = %d", $user_sync_id ) );
                        }

                        die( "ok" );
                    break;

                    //action for Synchronization when activating new Subsite
                    case "sync_new_subsite":
                        $p = unserialize( $p );

                        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );

                        $p = array (
                                'url' => $p['url'],
                                'last_sync' => date( "m.d.y G:i:s" ),
                                'param' =>
                                    array(
                                        'replace_user'=> $p['replace_user'],
                                        'overwrite_user'=> $p['overwrite_user']
                                        ) );

                        if ( is_array( $user_sync_sub_urls ) ) {
                            if ( -1 ==  $this->get_index_by_url( $p['url'], $user_sync_sub_urls ) ) {
                                 $user_sync_sub_urls[] = $p;
                                 update_option( "user_sync_sub_urls", $user_sync_sub_urls );
                            }

                        } else {
                            $user_sync_sub_urls[] = $p;
                            update_option( "user_sync_sub_urls", $user_sync_sub_urls );
                        }

                        //Get all users ID
                        $user_sync_users_id = $this->user_sync_get_all_users_id();

                        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );

                        $array_id = $this->get_index_by_url( $p['url'], $user_sync_sub_urls );

                        //Call Synchronization user function
                        $this->sync_user( $user_sync_users_id, array($user_sync_sub_urls[$array_id]) );

                        die( "ok" );
                    break;

                    //action for deleting Subsite URL from Central site
                    case "delete_subsite":
                        $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );

                        $array_id = $this->get_index_by_url( $p, $user_sync_sub_urls );

                        if ( -1 != $array_id ) {
                            array_splice( $user_sync_sub_urls, $array_id, 1 );

                            update_option( "user_sync_sub_urls", $user_sync_sub_urls );
                        }

                        die( "ok" );
                    break;
                }
            }
        }
    }

    /**
     *  Tempalate of pages
     **/
    function main_page() {
        global $wpdb;

        //Display status message
        if ( isset( $_GET['updated'] ) ) {
            ?><br /><br /><div id="message" class="updated fade"><p><?php _e( urldecode( $_GET['dmsg']), 'user-sync' ) ?></p></div><?php
        }

        $user_sync_status = get_option( 'user_sync_status' );

        if ( false == ( $user_sync_status ) ) {
        ?>
            <script language="JavaScript">
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
                <form method="post" action="" id="user_sync_form">
                    <input type="hidden" name="user_sync_settings" value="type_site" />
                    <input type="hidden" name="user_sync_status" id="user_sync_status" value="" />
                    <table class="form-table">
                        <tr valign="top">
                            <td>
                            <input type="button" value="<?php _e( 'Make this site the Master site', 'user-sync' ) ?> " onclick="jQuery(this).makeChose( 1 );" />
                            <input type="button" value="<?php _e( 'Make this a Sub site', 'user-sync' ) ?> " onclick="jQuery(this).makeChose( 2 );" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        <?php

        } elseIf ( "sub" == $user_sync_status ) {
            $user_sync_key   = get_option( 'user_sync_key' );
            $user_sync_url_c = get_option( 'user_sync_url_c' );

            if ( "" != $user_sync_url_c )
                $disabled = 'readonly="readonly"';

        ?>
            <script language="JavaScript">
                jQuery( document ).ready( function() {
                    jQuery( "#user_sync_form" ).submit( function () {
                        if ( "" == jQuery( "#user_sync_url_c" ).val() ) {
                            alert( "<?php _e( 'Please write URL of Central blog', 'user-sync' ) ?>" );
                            return false;
                        }

                        if ( "" == jQuery( "#user_sync_key" ).val() ) {
                            alert( "<?php _e( 'Please write Key of Central blog', 'user-sync' ) ?>" );
                            return false;
                        }

                        return true;
                    });
                });
            </script>
            <div class="wrap">
                <h2><?php _e( 'Subsite Settings', 'user-sync' ) ?></h2>
                <h3><?php _e( 'All user data from the Master site will be synchronized with this site.', 'user-sync' ) ?></h3>
                <form method="post" action="" id="user_sync_form">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'URL of Master site:', 'user-sync' ) ?>
                            </th>
                            <td>
                                <input type="text" name="user_sync_url_c" id="user_sync_url_c" value="<?php echo $user_sync_url_c; ?>" size="70" <?php echo $disabled; ?> />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'Key of Master site:', 'user-sync' ) ?>
                            </th>
                            <td>
                                <input type="text" name="user_sync_key" id="user_sync_key" value="<?php echo $user_sync_key; ?>" size="30" <?php echo $disabled; ?> />
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
                        <p><?php _e( "Use caution if you choose to overwrite existing users as it replaces all existing users and their passwords if the same username exists on the subsite.", 'user-sync' ) ?></p>
                    <?php } else {?>
                        <br />
                        <p><?php _e( "Click on 'Remove all settings' to disconnect syncing with Master site.", 'user-sync' ) ?></p>
                    <?php } ?>
                    <p>
                        <?php _e( "For detailed 'how to use' instructions refer to:", 'user-sync' ) ?><br />
                        <a href="http://premium.wpmudev.org/project/wordpress-user-synchronization/installation/" target="_blank" ><?php _e( 'WordPress User Synchronization Installation and Use instructions.', 'user-sync' ) ?></a>
                    </p>

                    <?php if ( "" == $disabled ) {?>
                        <p class="submit">
                            <input type="hidden" name="user_sync_settings" value="sub_site" />
                            <input type="submit" value="<?php _e( 'Connect this site to the Master site, and do a FULL synchronization', 'user-sync' ) ?>" />
                        </p>
                    <?php } else {?>
                        <p class="submit">
                            <input type="hidden" name="user_sync_settings" value="remove_settings" />
                            <input type="submit" value="<?php _e( 'Remove all settings', 'user-sync' ) ?>" />
                        </p>
                    <?php } ?>
                </form>
            </div>
        <?php

        } elseIf ( "central" == $user_sync_status ) {
            $user_sync_key      = get_option( 'user_sync_key' );
            $user_sync_sub_urls = get_option( 'user_sync_sub_urls' );
            $user_sync_siteur = get_option( 'siteurl' );

        ?>
            <script language="JavaScript">
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

                });
            </script>
            <div class="wrap">
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
            <p><?php _e( 'To stop a subsite from syncing with Master site simply log into the subsite, go to the User Sync menu and click on "Remove all settings" button or simple deactivate the plugin.', 'user-sync' ) ?></p>
            <p>
                <?php _e( "For detailed 'how to use' instructions refer to:", 'user-sync' ) ?><br />
                <a href="http://premium.wpmudev.org/project/wordpress-user-synchronization/installation/" target="_blank" ><?php _e( 'WordPress User Synchronization Installation and Use instructions.', 'user-sync' ) ?></a>
            </p>

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
                <input type="hidden" name="user_sync_settings" value="sync_all" />
                <input type="submit" value="<?php _e( 'Sync all sites now', 'user-sync' ) ?>"  />
            </form>
            </center>
            <br />
           <?php
           }
        }
    }// end of function "main_page"

}

$user_sync =& new User_Sync();
?>