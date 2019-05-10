# WordPress User Sync

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations

## User Synchronization links user profiles across multiple single WordPress installations.

Create and manage user accounts on one master list that can be used to connect with any subsite–without Multisite.

### Smarter, Safer, User Management

Automated profile, password, user role and email address share and sync moves user management to one convenient location. Connect more people to more content by allowing users to register once on a master list  and use the same login credentials across any attached site. Protect your users with bulletproof security keys. Leave sites connected for ongoing continuity or transfer users and disconnect. Use uninstall and completely wipe your database clean from any plugin artifacts. 

![Transfer and wipe your database clean.](http://premium.wpmudev.org/wp-content/uploads/2011/04/uninstall.jpg)

 Transfer and wipe your database clean.

### Leverage Control and Simplicity

It takes a matter of seconds to configure, requiring only a URL and security key. Use synchronize-overwrite for an identical master list match, or support backward user password compatibility on existing sites by allowing duplicates. 

![Full control over overwrite and duplicates](http://premium.wpmudev.org/wp-content/uploads/2011/04/sync-list.jpg)

 Full control over overwrite and duplicates

 You can also ban a user from a specific sub-site by blocking deleted users from repopulating when synced with the master list. Protect identities, stay synced and save countless hours with User Synchronization. Get linked user management for transferring user accounts from a master list to all of your WordPress sites.

## Usage

Start by reading [Installing Plugins](https://premium.wpmudev.org/wpmu-manual/installing-regular-plugins-on-wpmu/) section in our comprehensive [WordPress and WordPress Multisite Manual](https://premium.wpmudev.org/wpmu-manual/) if you are new to WordPress.

### To install:

1. Download the plugin file. 2\. Unzip the file into a folder on your hard drive. 3\. Upload **/user-sync/** folder to **/wp-content/plugins/** folder on the sites you would like to sync between. 4\. Login to your admin panel for your WordPress site and activate the User Synchronisztion plugin in **Plugins**.

### To Use:

Once the plugin is activated you'll see a new "User Sync" menu added. 1.  Go to **User Sync** in the dashboard of the site you want to make the Master site.

*   This is the parent site that is used to sync/duplicate all users to your other sites

![User Sync menu](https://premium.wpmudev.org/wp-content/uploads/2011/09/sync_menu01.png)

2.  Click on **Make this Site the Master Site** 

![Make ste the master site](https://premium.wpmudev.org/wp-content/uploads/2011/04/sync64.jpg)

 3.  You'll now see your Master Site URL and security key displayed. 

![Your master key details](https://premium.wpmudev.org/wp-content/uploads/2011/04/mastersitekey.jpg)

 4.  Now log into the dashboard site(s) you want to sync users from the Master site to and go to **User Sync** 5.  Click on **Make this a Sub site** 

![Make this a sub site](https://premium.wpmudev.org/wp-content/uploads/2011/04/sync65.jpg)

 6.  Add the URL of the Master Site, Key of Master site, select Default Settings then click **Connect this site to the Master Site, and do a FULL synchronization**.

*   Use overwrite existing users with _**caution**_ -- it does overwrite existing users and will lock out an admin user account if they're using the same username
*   Don't overwrite any existing users (add them as extra users) is normally the best option.  If you use this option and a user from the master site has the same username as the subsite the new user is created suffixed with _sync attached to their username.  For example. if you had the username admin already existing on both sites the username would be created as admin_sync on the subsite.

![Connecting with Master site](https://premium.wpmudev.org/wp-content/uploads/2011/09/sync_connect.png)

 7.  All new users on the master site are replicated on the subsite(s)including passwords, their emails and their user roles and they'll now be listed in subsite's User list. 8.  Now whenever a new user is added to the Master site they are automatically created with the same details on the subsite(s). 9.  To remove a subsite so it no longer syncs with the Master site you just need to click **Disconnect from the Master site** in the subsite's **User Sync.** 

![Disconnect from the Master site](https://premium.wpmudev.org/wp-content/uploads/2011/09/sync_discon.png)

 10\. To uninstall plugin or reset all settings of plugin just need to click **"Uninstall Options"** in the bottom of the page.

### Debug Mode:

If you have any problems with sync users you can use debug mode for writing some operations in the log file. **How to use:** 1\. Select checkbox "Use Debug Mode" on main plugin page before select "Master" or "Sub-site". 

![Debug Mode](https://premium.wpmudev.org/wp-content/uploads/2011/09/sync_debug.png)
