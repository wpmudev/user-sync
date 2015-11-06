<?php
/*
Plugin Name: User Synchronization
Plugin URI: http://premium.wpmudev.org/project/wordpress-user-synchronization
Description: User Synchronization - This plugin allows you to create a Master site from which you can sync a user list with as many other sites as you like - once activated get started <a href="admin.php?page=user-sync">here</a>
Version: 1.1.5
Author: WPMUDEV
Author URI: http://premium.wpmudev.org
WDP ID: 218

Copyright 2007-2013 Incsub (http://incsub.com)

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


/**
* Plugin main class
**/

class User_Sync {

    var $debug_mode;
    var $plugin_dir;
    var $plugin_url;
    var $error;
    var $options;

	/**
	 * PHP 5 constructor
	 **/
	function __construct() {

        global $wpmudev_notices;
        $wpmudev_notices[] = array( 'id'=> 218,'name'=> 'User Synchronization', 'screens' => array( 'toplevel_page_user-sync' ) );
        include_once( $this->plugin_dir . 'wpmudev-dash-notification.php' );

        load_plugin_textdomain( 'user-sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        //setup proper directories
        if ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
            $this->plugin_dir = WPMU_PLUGIN_DIR . '/user-sync/';
            $this->plugin_url = WPMU_PLUGIN_URL . '/user-sync/';
        } else if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/user-sync/' . basename( __FILE__ ) ) ) {
            $this->plugin_dir = WP_PLUGIN_DIR . '/user-sync/';
            $this->plugin_url = WP_PLUGIN_URL . '/user-sync/';
        } else if ( defined('WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
            $this->plugin_dir = WP_PLUGIN_DIR;
            $this->plugin_url = WP_PLUGIN_URL;
        } else {
            wp_die( __( 'There was an issue determining where WPMU DEV User Sync is installed. Please reinstall.', 'user-sync' ) );
        }

        // Check for safe mode
        if ( ini_get( 'safe_mode' ) ) {
            //notice for safe mode
            $this->safe_mode_notice = __( "NOTE: Your server works in the 'Safe Mode' - If you have a large number of users for sync and your php settings are of little value for 'max_execution_time' it can cause problems with connection of subsites and full sinchronization.", 'user-sync' );
        } else {
            // set unlimit time
            set_time_limit(0);
        }

        add_action( 'admin_init', array( &$this, 'admin_init' ) );

        //rewrite old options from old version of plugin
        $this->rewrite_options();
        $this->options = $this->get_options();

        add_action( 'admin_menu', array( &$this, 'admin_page' ) );

        //actions only for master site
        if ( "central" == $this->options['status'] ) {
            add_action( 'profile_update', array( &$this, 'user_change_data' ), 20 );
            add_action( 'user_register', array( &$this, 'user_change_data' ), 20 );
           
            add_action( 'delete_user', array( &$this, 'user_delete_data' ), 20 );
        }
        add_action( 'bp_core_signup_after_activate', array( &$this, 'bp_users_activate' ), 20, 2 );
        add_action( 'wp_ajax_nopriv_user_sync_api', array( &$this, 'user_sync_ajax_action' ) );
        add_action( 'wp_ajax_user_sync_api', array( &$this, 'user_sync_ajax_action' ) );

        add_action( 'wp_ajax_nopriv_user_sync_settings', array( &$this, 'edit_settings' ) );
        add_action( 'wp_ajax_user_sync_settings', array( &$this, 'edit_settings' ) );

        add_action('admin_enqueue_scripts', array($this,'register_scripts_styles_admin'));
	}

    /**
     * Creating admin menu
     **/
    function admin_page() {
        add_menu_page( __( 'User Sync', 'user-sync' ), __( 'User Sync', 'user-sync' ), 'manage_options', 'user-sync', array( &$this, 'plugin_page' ), $this->plugin_url . 'images/icon.png' );
    }


    /**
     * set options
     **/
    function set_options( $section, $values ) {
        $options = get_option( 'user_sync_options' );
        $options[$section] = $values;
        update_option( "user_sync_options", $options );
        $this->options = $this->get_options();
    }

    /**
     * get options
     **/
    function get_options() {
       return $options = get_option( 'user_sync_options' );
    }

    /**
     * Rewrite plugin option from old version
     **/
    function rewrite_options() {

        if ( get_option( "user_sync_status" ) ) {
            $this->set_options( "status", get_option( "user_sync_status" ) );
            delete_option( 'user_sync_status' );
        }

        if ( get_option( "user_sync_key" ) ) {
            $this->set_options( "key", get_option( "user_sync_key" ) );
            delete_option( 'user_sync_key' );
        }

        if ( get_option( "user_sync_sub_urls" ) ) {
            $this->set_options( "sub_urls", get_option( "user_sync_sub_urls" ) );
            delete_option( 'user_sync_sub_urls' );
        }

        if ( get_option( "user_sync_url_c" ) ) {
            $this->set_options( "central_url", get_option( "user_sync_url_c" ) );
            delete_option( 'user_sync_url_c' );
        }

        if ( get_option( "user_sync_deleted_users" ) ) {
            $this->set_options( "deleted_users", get_option( "user_sync_deleted_users" ) );
            delete_option( 'user_sync_deleted_users' );
        }
    }


    /**
     * Write log
     **/
    function write_log( $message ) {
        if ( '1' == $this->options['debug_mode'] ) {
            if ( "central" == $this->options['status'] ) {
                $site_type = "[M] ";
                $file = $this->plugin_dir . "log/errors_m.log";
            } else {
                $site_type = "[S] ";
                $file = $this->plugin_dir . "log/errors_s.log";
            }

            $handle = fopen( $file, 'ab' );
            $data = date( "[Y-m-d H:i:s]" ) . $site_type . $message . "***\r\n";
            fwrite($handle, $data);
            fclose($handle);
        }
    }


    /**
     * Adding css style and script for admin page
     **/
    function register_scripts_styles_admin($hook) {
        if( $hook == 'toplevel_page_user-sync' ) {
            wp_register_style('user-sync-admin', $this->plugin_url.'/css/admin.css');
            wp_enqueue_style('user-sync-admin');

            wp_register_script( 'jquery-tooltips', $this->plugin_url . 'js/jquery.tools.min.js', array('jquery') );
            wp_enqueue_script( 'jquery-tooltips' );
        }
        global $wp_version;

        if ( $wp_version >= 3.8 ) {
            wp_register_style( 'user-sync-mp6', $this->plugin_url.'/css/mp6.css');
            wp_enqueue_style('user-sync-mp6');
        }
    }


    /**
     * plugin actions
     **/
    function admin_init() {
        if ( isset( $_POST['usync_action'] ) )
            switch( $_POST['usync_action'] ) {
                //Saving choose of site "Central" or "Subsite"
                case "install":
                    //creating additional options for central site
                    if ( "central" == $_POST['user_sync_status'] ) {
                        $this->set_options( 'status', $_POST['user_sync_status'] );
                        $this->set_options( 'key', $this->gener_key() );
                        $this->set_options( 'sub_urls', '' );
                    }

                    //creating additional options for sub site
                    if ( "sub" == $_POST['user_sync_status'] ) {
                        $this->set_options( 'status', $_POST['user_sync_status'] );
                        $this->set_options( 'key', '' );
                        $this->set_options( 'central_url', '' );
                        $this->set_options( 'deleted_users', '' );
                    }

                    //set debug mode
                    if ( isset ( $_POST['debug'] ) && '1' == $_POST['debug'] )
                        $this->set_options( 'debug_mode', '1' );

                    wp_redirect( add_query_arg( array( 'page' => 'user-sync'), 'admin.php' ) );
                    exit;
                break;

                //delete all plugin options
                case "uninstall":
                    $this->uninstall( );
                    wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( "Options are deleted!", 'user-sync' ) ) ), 'admin.php' ) );
                    exit;
                break;

                //Creating additional options of Subsite and Saving URL of Central site and Security Key
                case "sub_site":
                    if ( ! empty( $_POST['user_sync_url_c']) && ! empty( $_POST['user_sync_key'] ) ) {

                        $this->set_options( 'central_url', $_POST['user_sync_url_c'] );
                        $this->set_options( 'key', $_POST['user_sync_key'] );

                        //connect Subsite to Master site
                        $result = $this->connect_new_subsite( $_POST['user_sync_url_c'], $_POST['user_sync_key'] );

                        if ( "ok" == $result ) {
                            //Call Synchronization when activating new Subsite
                            $result = $this->sync_new_subsite( $_POST['user_sync_url_c'], $_POST['user_sync_key'] );

                            if ( "ok" == $result ) {
                                wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'Subsite successfully connected to Master site and Synchronization completed.', 'user-sync' ) ) ), 'admin.php' ) );
                                exit;
                            } else {
                                wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'There was a sync problem.', 'user-sync' ) ) ), 'admin.php' ) );
                                exit;
                            }
                        } else {
                            $this->set_options( 'central_url', '' );
                            $this->set_options( 'key', '' );

                            wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'There was a connection problem. Please check the URL and Key of the Master site.', 'user-sync' ) ) ), 'admin.php' ) );
                            exit;
                        }
                    }
                break;

                //Removing Subsite from Central list
                case "remove_settings":
                    $p = base64_encode( get_option( 'siteurl' ) );

                    $hash = md5( $p . $this->options['key'] );

                    //delete url from Sub list on central site
                    $this->send_request( $this->options['central_url'], "user_sync_action=delete_subsite&hash=". $hash . "&p=" . $p );

                    //reset options of Sub site
                    $this->set_options( 'central_url', '' );
                    $this->set_options( 'key', '' );
                    $this->set_options( 'deleted_users', '' );

                    wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'Subsite and Settings were successfully removed from the Central site!', 'user-sync' ) ) ), 'admin.php' ) );
                    exit;
                break;

                //Call function for Synchronization of all Subsites
                case "sync_all":

                    $this->sync_all_subsite();

                    wp_redirect( add_query_arg( array( 'page' => 'user-sync', 'updated' => 'true', 'dmsg' => urlencode( __( 'Synchronization of all Subsites completed.', 'user-sync' ) ) ), 'admin.php' ) );
                    exit;
                break;
            }

    }

    /**
     * Deleting options and Sub url
     **/
    function uninstall() {
        //check type of blog
        if ( "sub" == $this->options['status'] && '' != $this->options['key'] && '' != $this->options['central_url'] ) {
            $p = base64_encode( get_option( 'siteurl' ) );

            $hash = md5( $p . $this->options['key'] );

            //delete url from Sub list on central site
            $this->send_request( $this->options['central_url'], "user_sync_action=delete_subsite&hash=". $hash . "&p=" . $p );
        }

        delete_option( 'user_sync_options' );
    }

    /**
     * Editing settings of Sub site
     **/
    function edit_settings() {
        $sub_urls = $this->options['sub_urls'];

        $array_id = $this->get_index_by_url( base64_decode($_REQUEST['url']), $sub_urls );

        if ( -1 != $array_id ) {
            if ( "0" == $_POST['replace_user'] || "1" == $_POST['replace_user'] )
                $sub_urls[$array_id]['param']['replace_user'] = $_POST['replace_user'];

            if ( "0" == $_POST['overwrite_user'] || "1" == $_POST['overwrite_user'] )
                $sub_urls[$array_id]['param']['overwrite_user'] = $_POST['overwrite_user'];

            $this->set_options( 'sub_urls', $sub_urls );
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
    function send_request( $url, $param, $blocking = true ) {

        $url = $url . "/wp-admin/admin-ajax.php?action=user_sync_api";

        $args =  array(
            'method'    => 'POST',
            'timeout'   => 10,
            'blocking'  => $blocking,
            'body'      => $param
        );

        //writing some information in the plugin log file
        $this->write_log( "02 - sending request - url={$url};;" );

        $response = wp_remote_post( $url, $args );

        if( is_wp_error( $response ) ) {
            //writing some information in the plugin log file
            $this->write_log( "04 - sending request: something went wrong" );

//           echo 'Something went wrong!';
        } else {
            //writing some information in the plugin log file
            $this->write_log( "03 - sending request - response={$response["body"]};;" );
//            var_dump($response["body"]);
//            exit;
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
        $hash = $this->send_request( $url, "str=" . $str );

        //checking hash from Subsite and Central site
        if ( $hash == md5( $str . "" . $key ) ) {
            //writing some information in the plugin log file
            $this->write_log( "05 - checking key true;;" );

            return true;
        } else {
            //writing some information in the plugin log file
            $this->write_log( "06 - checking key false;;" );

            return false;
        }
    }

    /**
     *  Get all Users ID
     **/
    function get_all_users_id() {
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
		unset($userdata['user_pass']);

        //Update email on blank if email is duplicate
        if ( "temp@temp.temp" == $userdata['user_email'] )
            $result = $wpdb->query( $wpdb->prepare( "
                UPDATE {$wpdb->base_prefix}users SET
                user_email = '%s' WHERE ID = '%d'",
                "", $user_id ) );
		unset($userdata['user_email']);

        //Update user Role
        $result = $wpdb->query( $wpdb->prepare( "
            UPDATE {$wpdb->base_prefix}usermeta SET
            meta_value = '%s' WHERE user_id = '%d' AND meta_key = '{$wpdb->base_prefix}capabilities'",
            serialize( $userdata['wp_capabilities'] ), $user_id ) );
		unset($userdata['wp_capabilities']);

        //Update user Level
        $result = $wpdb->query( $wpdb->prepare( "
            UPDATE {$wpdb->base_prefix}usermeta SET
            meta_value = '%s' WHERE user_id = '%d' AND meta_key = '{$wpdb->base_prefix}user_level'",
            $userdata['wp_user_level'], $user_id ) );
		unset($userdata['wp_user_level']);


        foreach( $userdata as $k => $v ) {
        	update_user_meta($user_id,$k,$v);
        }

    }

    /**
     *  Synchronization user
     **/
    function sync_user( $users_id, $urls, $blocking = true ) {
        $key        = $this->options['key'];
        $urls       = (array) $urls;
        $users_id   = (array) $users_id;

        foreach ( $urls as $one ) {
            //Checking key of security from Subsite
            if ( $this->check_key( $one['url'], $key ) )
                foreach ( $users_id as $user_id ) {
                    //get all information about user
                    $userdata = $this->_get_user_data( $user_id );

                    $p = array ( 'param' => array( 'replace_user' => $one['param']['replace_user'], 'overwrite_user' => $one['param']['overwrite_user'] ),
                                'userdata' => $userdata );

                    $p =  base64_encode( serialize ( $p ) );
                    $hash = md5( $p . $key );

                    //writing some information in the plugin log file
                    $this->write_log( "09 - user sync" );

                    //sent information about user and hash to Subsite
                    $this->send_request( $one['url'], "user_sync_action=sync_user&hash=". $hash . "&p=" . $p, $blocking );

                    //Update last Sync date
                    $sub_urls = $this->options['sub_urls'];
                    $array_id = $this->get_index_by_url( $one['url'], $sub_urls );
                    $sub_urls[$array_id]['last_sync'] = date( "m.d.y G:i:s" );
                    $this->set_options( 'sub_urls', $sub_urls );
                }
        }
    }

    /**
     * Synchronization when user edit profile
     **/
    function user_change_data( $userID ) {
        //Call Synchronization function with ID of changed user and array of all Subsite URLs
        $this->sync_user( $userID, $this->options['sub_urls'], false );
    }

    /**
     * Synchronization when user deleting
     **/
    function user_delete_data( $userID ) {
        $user_data = $this->_get_user_data( $userID );

        $status = $this->options['status'];

        if ( "sub" == $status ) {
            //Adding login of user to list of deleted users on Sub site
            $deleted_users = (array) $this->options['deleted_users'];

            if ( false === array_search( $user_data['user_login'], $deleted_users ) ) {
                $deleted_users[] = $user_data['user_login'];
                $this->set_options( 'deleted_users', $deleted_users );
            }
        } elseif ( "central" == $status ) {
            $key      = $this->options['key'];
            $sub_urls = $this->options['sub_urls'];


            //Deleting user from all Subsite
            if ( false !== $sub_urls )
                foreach ( $sub_urls as $one ) {
                    //Checking key of security from Subsite
                    if ( $this->check_key( $one['url'], $key ) ) {

                        $p      = array( 'user_login' => $user_data['user_login'], 'overwrite_user' => $one['param']['overwrite_user'] );
                        $p      = base64_encode( serialize ( $p ) );
                        $hash   = md5( $p . $key );


                        $this->send_request( $one['url'], "user_sync_action=sync_user_delete&hash=". $hash . "&p=" . $p );

                        //Update last Sync date
                        $sub_urls = $this->options['sub_urls'];
                        $array_id = $this->get_index_by_url( $one['url'], $sub_urls );
                        $sub_urls[$array_id]['last_sync'] = date( "m.d.y G:i:s" );
                        $this->set_options( 'sub_urls', $sub_urls );
                    }
                }
        }
    }

    /**
     *  Connect new subsite to master site
     **/
    function connect_new_subsite( $central_url, $key ) {
        $replace_user   = 0;
        $overwrite_user = 0;

        //Settings of Sub site
        if ( isset( $_POST['replace_user'] ) && "1" == $_POST['replace_user'] )
            $replace_user = 1;

        if ( isset( $_POST['overwrite_user'] ) && "1" == $_POST['overwrite_user'] )
            $overwrite_user = 1;

        $p = array ( 'url' => get_option( 'siteurl' ), 'replace_user' => $replace_user, 'overwrite_user' => $overwrite_user );

        //writing some information in the plugin log file
        $this->write_log( "01 - new subsite conection - central_url={$central_url};; replace_user={$replace_user};; overwrite_user={$overwrite_user};;" );

        $p =  base64_encode( serialize ( $p ) );

        $hash = md5( $p . $key );

        $result = $this->send_request( $central_url, "user_sync_action=connect_new_subsite&hash=". $hash . "&p=" . $p );

        return $result;
    }

    /**
     *  Synchronization when activating new Subsite
     **/
    function sync_new_subsite( $central_url, $key ) {

        $p = array ( 'url' => get_option( 'siteurl' ) );

        //writing some information in the plugin log file
        $this->write_log( "01_2 - sync users for new subsite" );

        $p =  base64_encode( serialize ( $p ) );

        $hash = md5( $p . $key );

        $result = $this->send_request( $central_url, "user_sync_action=sync_new_subsite&hash=". $hash . "&p=" . $p );

        return $result;
    }

    /**
     *  Synchronization of all Subsites
     **/
    function sync_all_subsite() {
        //Get all users ID
        $users_id = $this->get_all_users_id();

        //Call Synchronization for all Subsites
        $this->sync_user( $users_id, $this->options['sub_urls'] );
    }

    /**
     * Ajax function for changes data on remote site
     **/
    function user_sync_ajax_action() {
        $key = $this->options['key'];
        //writing some information in the plugin log file
        $this->write_log( "0-10 - ajax actions" );

        if ( false === $key ) {
            //writing some information in the plugin log file
            $this->write_log( "1-10 - key not exist" );
            die( "" );
        }


        //action for checking security key on Subsite
        if ( isset ( $_REQUEST['str'] ) && "" != $_REQUEST['str'] ) {
            die( md5( $_REQUEST['str'] . $key ) );
        }

        if ( isset ( $_REQUEST['user_sync_action'] ) && "" == $_REQUEST['user_sync_action'] ) {
            //writing some information in the plugin log file
            $this->write_log( "2-10 - user_sync_action not exist" );
            die( "" );
        }

        if ( isset ( $_REQUEST['p'] ) && "" != $_REQUEST['p'] && isset ( $_REQUEST['hash'] ) && "" != $_REQUEST['hash'] ) {
            //checking hash sum
            if ( $_REQUEST['hash'] == md5( $_REQUEST['p'] . $key ) ) {
                $p = base64_decode( $_REQUEST['p'] );

                global $wpdb;

                switch( $_REQUEST[ 'user_sync_action' ] ) {
                    //action for Synchronization user
                    case "sync_user":
                        $p = unserialize( $p );
                        //writing some information in the plugin log file
                        $this->write_log( "5-10 - start sync_user" );

                        $user_sync_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->base_prefix}users WHERE user_login = '%s'", $p['userdata']['user_login'] ) );
                        if ( isset( $user_sync_id['0'] ) )
                            $user_sync_id = $user_sync_id['0']->ID;
                        else
                            $user_sync_id = false;

                        if( $user_sync_id ) {
                            //Update user

                            //writing some information in the plugin log file
                            $this->write_log( "10 - update user" );

                            //checking settings of overwrite user and flag of users that sync from master site
                            if ( 1 == $p['param']['overwrite_user'] && "1" != get_user_meta( $user_sync_id, "user_sync", true ) ) {

                                $user_sync_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->base_prefix}users WHERE user_login = '%s'", $p['userdata']['user_login'] . "_sync" ) );
                                if ( isset( $user_sync_id['0'] ) )
                                    $user_sync_id = $user_sync_id['0']->ID;
                                else
                                    $user_sync_id = false;

                                //if user exist we have new ID in $user_sync_id and we can use code below for Update user data
                                if( ! $user_sync_id ) {

                                    //writing some information in the plugin log file
                                    $this->write_log( "11 - don't overwrite user" );

                                    //changing user login adding  _sync
                                    $p['userdata']['user_login'] = $p['userdata']['user_login'] . "_sync";

                                    //checking email of user on duplicate
                                    if ( $user_sync_id != email_exists( $p['userdata']['user_email'] ) && false != email_exists( $p['userdata']['user_email'] ) )
                                        $p['userdata']['user_email'] = "temp@temp.temp";

                                    //user password
                                    $user_sync_pass  = $p['userdata']['user_pass'];

                                    //delete user ID and user_pass for Insert new user
                                    unset( $p['userdata']['ID'] );
                                    unset( $p['userdata']['user_pass'] );

                                    //Insert new user
                                    //TODO: try use real password
                                    $p['userdata']['user_pass'] = '';
                                    $user_sync_last_id = wp_insert_user( $p['userdata'] );

                                    //adding user password back for updating it
                                    $p['userdata']['user_pass'] = $user_sync_pass;

                                    //Update other data of user
                                    $this->update_other_user_data( $p['userdata'], $user_sync_last_id );

                                    //writing some information in the plugin log file
                                    $this->write_log( "12 - don't overwrite user - ok" );

                                    return;
                                }
                            }

                            //writing some information in the plugin log file
                            $this->write_log( "13 - overwrite user" );

                            $p['userdata']['ID'] = $user_sync_id;

                            //user password
                            $user_sync_pass  = $p['userdata']['user_pass'];

                            //delete user_pass for update user
                            unset( $p['userdata']['user_pass'] );


                            //checking email of user on duplicate
                            if ( $user_sync_id != email_exists( $p['userdata']['user_email'] ) && false != email_exists( $p['userdata']['user_email'] ) )
                                $p['userdata']['user_email'] = "temp@temp.temp";

                            //update user data
                            $p['userdata']['user_pass'] = '';
                            $user_sync_last_id = wp_insert_user( $p['userdata'] );

                            //adding user password back for updating it
                            $p['userdata']['user_pass'] = $user_sync_pass;

                            //Update other data of user
                            $this->update_other_user_data( $p['userdata'], $user_sync_last_id );

                            //writing some information in the plugin log file
                            $this->write_log( "14 - overwrite user - ok" );

                        } else {

                            //writing some information in the plugin log file
                            $this->write_log( "15 - insert user - step 1" );

                            if ( 1 == $p['param']['replace_user'] ) {
                                $deleted_users = $this->options['deleted_users'];

                                //writing some information in the plugin log file
                                $this->write_log( "16 - do not replace deleted users" );

                                if ( is_array( $deleted_users ) && false !== array_search( $p['userdata']['user_login'], $deleted_users ) )
                                    return;
                            }

                            //writing some information in the plugin log file
                            $this->write_log( "17 - insert user - step 2" );

                            //user password
                            $user_sync_pass  = $p['userdata']['user_pass'];

                            //delete user ID and user_pass for Insert new user
                            unset( $p['userdata']['ID'] );
                            unset( $p['userdata']['user_pass'] );

                            //checking email of user on duplicate
                            if ( $user_sync_id != email_exists( $p['userdata']['user_email'] ) && false != email_exists( $p['userdata']['user_email'] ) )
                                $p['userdata']['user_email'] = "temp@temp.temp";

                            //Insert new user
                            $p['userdata']['user_pass'] = '';
                            $user_sync_last_id = wp_insert_user( $p['userdata'] );

                            //adding user password back for updating it
                            $p['userdata']['user_pass'] = $user_sync_pass;

                            //Update other data of user
                            $this->update_other_user_data( $p['userdata'], $user_sync_last_id );

                            //flag for users that sync from master site
                            add_user_meta( $user_sync_last_id, "user_sync", "1", false );

                            //writing some information in the plugin log file
                            $this->write_log( "18 - insert user - ok" );
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
                    case "connect_new_subsite":
                        $p = unserialize( $p );

                        $sub_urls = $this->options['sub_urls'];

                        $p = array (
                                'url'       => $p['url'],
                                'last_sync' => '',
                                'param'     =>
                                    array(
                                        'replace_user'      => $p['replace_user'],
                                        'overwrite_user'    => $p['overwrite_user']
                                        ) );

                        if ( is_array( $sub_urls ) ) {
                            if ( -1 ==  $this->get_index_by_url( $p['url'], $sub_urls ) ) {
                                 $sub_urls[] = $p;
                                 $this->set_options( 'sub_urls', $sub_urls );
                            }
                        } else {
                            $sub_urls[] = $p;
                            $this->set_options( 'sub_urls', $sub_urls );
                        }

                        //writing some information in the plugin log file
                        $this->write_log( "07 - added new sub site" );

                        die( "ok" );
                    break;

                    //action for Synchronization when activating new Subsite
                    case "sync_new_subsite":
                        $p = unserialize( $p );

                        $sub_urls = $this->options['sub_urls'];

                        //Get all users ID
                        $users_id = $this->get_all_users_id();

                        //writing some information in the plugin log file
                        $this->write_log( "08 - count of users= ". count( $users_id ) . ";;" );

                        $array_id = $this->get_index_by_url( $p['url'], $sub_urls );

                        //Call Synchronization user function
                        $this->sync_user( $users_id, array( $sub_urls[$array_id] ) );

                        die( "ok" );
                    break;

                    //action for deleting Subsite URL from Central site
                    case "delete_subsite":
                        $sub_urls = $this->options['sub_urls'];

                        $array_id = $this->get_index_by_url( $p, $sub_urls );

                        if ( -1 != $array_id ) {
                            array_splice( $sub_urls, $array_id, 1 );

                            $this->set_options( 'sub_urls', $sub_urls );
                        }

                        die( "ok" );
                    break;
                }

            }
            //writing some information in the plugin log file
            $this->write_log( "4-10 - hash sum error" );
        }
        //writing some information in the plugin log file
        $this->write_log( "3-10 - p or hash not set" );
    }

    /**
     *  Tempalate of pages
     **/
    function plugin_page() {
        global $wpdb;

        //Display status message
        if ( isset( $_GET['updated'] ) ) {
            ?><br /><br /><div id="message" class="updated fade"><p><?php _e( urldecode( $_GET['dmsg']), 'user-sync' ) ?></p></div><?php
        }

        switch( $this->options['status'] ) {
            case "sub":
                require_once( $this->plugin_dir . "page-sub.php" );
            break;

            case "central":
                require_once( $this->plugin_dir . "page-central.php" );
            break;

            default:
                require_once( $this->plugin_dir . "page-main.php" );
            break;
        }
    }


    /**
     *  Get user data
     **/
    private function _get_user_data( $user_id ) {
        global $wpdb;

        $data = get_userdata( $user_id );

        if ( !empty( $data->data ) )
            $user_data = (array) $data->data;
        else
            $user_data = (array) $data;

        $user_meta = get_user_meta( $user_id );

        $keys = array();
        // replace empty array on empty string
        foreach ( $user_meta as $key => $value ) {
            $keys[] = $key;
        }

        foreach ( $keys as $key ) {
            $user_meta[$key] = get_user_meta( $user_id, $key, true );
        }

        $prefix_fix = array('capabilities', 'user_level');
        foreach ($prefix_fix as $key) {
            if(isset($user_meta[$wpdb->get_blog_prefix() . $key])) {
                $user_meta['wp_' . $key] = $user_meta[$wpdb->get_blog_prefix() . $key];
                if($wpdb->get_blog_prefix() != 'wp_')
                    unset($user_meta[$wpdb->get_blog_prefix() . $key]);
            }
        }

        return array_merge( $user_data, $user_meta );
    }


    function bp_users_activate($signup_ids, $result) {
        var_dump($signup_ids, $result);
        die();
    }
}

$user_sync = new User_Sync();
?>