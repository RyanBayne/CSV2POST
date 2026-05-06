<?php
/**
* Uninstall procedure is called when user deletes the plugin from
* the plugins table. Delete the plugins files directly using the
* hosting file browser or FTP if the plugins options and/or database tables
* are to be kept.
* 
* @version 1.2
*/

if (
	!defined( 'WP_UNINSTALL_PLUGIN' )
||
	!WP_UNINSTALL_PLUGIN
||
	dirname( WP_UNINSTALL_PLUGIN ) != dirname( plugin_basename( __FILE__ ) )
) {
	status_header( 404 );
	exit;
}  

// TODO 1 -o Ryan Bayne -c Installation: this is not working, throwing errors, come up with a new approach to removing options and tables possible without using any other files/classes   
// Delete most options (in rare cases some are kept).
//CSV2POST_Options::uninstall_options();
          
// Delete all database tables. 
//CSV2POST_Install::uninstalldatabasetables();
?>
