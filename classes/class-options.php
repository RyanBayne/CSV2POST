<?php
/** 
 * Handle all things "options".
 * 
 * @todo Add option to log all option changes for monitoring users more.
 * 
 * @todo Create a list of options and values. On options view.
 * 
 * @package CSV 2 POST
 * @author Ryan Bayne   
 * @since 0.0.1
 * @version 1.2 
 */
class CSV2POST_Options {
	use CSV2POST_OptionsTrait;
}

trait CSV2POST_OptionsTrait {
    private static $grouped_options = array(
        'compact' => 'csv2post_options',
        'compactold' => 'csv2post_settings',
        'private' => 'csv2post_private_options',
        'webtechglobal' => 'webtechglobal_options'
    );

    /**
    * All valid option names. Used to validation and throw error
    * prior to attempting to access specified option. 
    * 
    * @param mixed $type
    * @version 1.0
    */
    public static function get_option_names( $type = 'compact' ) {
        switch ( $type ) {
        case 'non-compact' :
        
        //Individual options here, will be prepended with "csv2post".
        case 'non_compact' :
            return array(
            	'notifications',            // (array) admin side notification storage.
            	'installedversion',         // (string) original installed version.
            	'installeddate',            // (timestamp) original time when plugin was installed.
            	'formvalidation',			// (array) stores the plugins forms for comparison after submission.
                'capabilities', 			// (array) individual admin view capability requirements.
                'adm_trig_auto',         	// (bool) switch for administrator triggered automation.
                'securityevent_admincap',   // (array) details about a security event related to maximum admin accounts.  
            );                                                                                               

					      
        //Add security sensitive options here i.e. tokens, keys.
        case 'private' :
            return array(
            );
        }

        // Return compact options.
        return array(
            'postdump', // (boolean) switch in Developer Menu for displaying $_POST.
            'getdump', // (boolean) switch in Developer Menu for displaying $_GET.
            'debugtracedisplay',
            'debugtracelog'
        );
    }

    /**
    * Created to replace get_options_names() which holds only option names.
    * 
    * This method holds option names and their default values. We can thus
    * query default values to correct missing options.
    * 
    * We can also set each option to be installed by default or by trigger i.e.
    * during procedure.      
    * 
    * @author Ryan R. Bayne
    * @param mixed $type
    * @version 1.1
    * 
    * @param mixed $type single|merged|secure|deprec
    * @param mixed $return all|keys|install|update|delete|value
    * @param string|array $name use to get specific option details
    * 
    * @todo complete $return by allowing specific information to be returned.
    * @todo complete $name which makes procedure return data for one or more options.
    * @todo add this method to get_options_names() and return keys only in it. 
    * @todo move installation options to compact.
    */
    public function get_option_information( $type = 'merged', $return = 'keys', $name = array() ) {
    	
		    /* 
		    
            Types Explained
            Single - individual records in the WP options table.
            Merged - a single array of many options installed in WP options table.
            Secure - coded options, not installed in data, developer must configure.
            Deprec - depreciated option.

        	Options Array Values Explained
        	0. Install (0|1)  - add to options on activation of the plugin using add_option() only.
        	1. Autoload (0|1) - autoload the option.
        	2. Delete (0|1)   - delete when user uninstalls using form (most should be removed). 
        	3. Value (mixed)  - options default value.	
        	
			*/
        	
        	switch ( $type ) {
        		case 'single':
        		
        			// Remember the real option names are prepend with "csv2post".
					$single_options = array(  
					    // CSV 2 POST core options.                                  
					    'notifications'          => array( 1,1,1, array()           ),// (array) admin side notification storage.
					    'installedversion'       => array( 1,0,1, CSV2POST_VERSION ),// (string) original installed version.
					    'installeddate'          => array( 1,0,1, time()            ),// (timestamp) original time when plugin was installed.
					    'updatededversion'       => array( 1,0,1, CSV2POST_VERSION ),// (string) original installed version.
					    'formvalidation'         => array( 1,1,1, array()           ),// (array) stores the plugins forms for comparison after submission.
						'capabilities'           => array( 0,0,1, array()           ),// (array) individual admin view capability requirements.
						'adm_trig_auto'          => array( 0,1,1, false             ),// (bool) switch for administrator triggered automation.
						'securityevent_admincap' => array( 0,0,1, array()           ),// (array) details about a security event related to maximum admin accounts.    

						// System specific options
					    'twitterservice'         => array( 0,0,1, false             ),// (unknown???) likely to be a boolean switch for twitter services.
					);  
					
					return $single_options;
					        	
        	    	break;
        	    case 'webtechglobal':
        	
        			// Remember the real option names are prepend with "csv2post".
					$webtechglobal_options = array(  
					    // CSV 2 POST core options.                                  
					    'webtechglobal_twitterservice'         => array( 1,1,1, true     ),// (boolean) Switch for all Twitter services on all WTG plugins.
					    'webtechglobal_helpauthoring'   	   => array( 1,0,1, false    ),// (boolean) Help content authoring fields switch.
					    'webtechglobal_displayerrors'   	   => array( 1,1,1, false    ),// (boolean) Switch for displaying errors for all WTG plugins.
					    'csv2post_auto_switch'   		   => array( 1,1,1, false    ),// (boolean) Swtich for all automation offered by WTG plugins.
					    'csv2post_auto_plugins'           => array( 1,0,1, array()  ),// (array) All the plugins to be included in WTG automation.
					    'csv2post_auto_lasttime'          => array( 1,0,1, time()   ),// (time()) The last time an automated event was run by WTG plugins. 
					    'csv2post_auto_actionsettings'   => array( 1,0,1, array()  ),// (array) User condfiguration for automated actions, overwriting defaults.			    
						'webtechglobal_autoadmin_lasttime'     => array( 1,1,1, time() ),// (array) The last time auto administration ran.
					);  
					
					return $webtechglobal_options;
					        	
        	    	break;	
        		case 'merged':
        	   
					$merged_options = array(  
					    'postdump'               => array( 'merged',1,1,1, false ),// (boolean) Switch in Developer menu for displaying $_POST data.
					    'getdump'                => array( 'merged',1,1,1, false ),// (boolean) Switch in Developer menu for displaying $_GET dump.
					    'debugtracedisplay'      => array( 'merged',1,1,1, false ),// (boolean) Switch in Developer menu for displaying trace for the current page load.
					    'debugtracelog'          => array( 'merged',1,1,1, false ),// (boolean) Switch in Developer menu for logging trace for the current page load.
					);
					
					return $merged_options; 
					       	
					break;
				case 'secure':
					return;
					break;
				case 'deprec':
					return;
					break;
        	}
	}

	/**
	* Install all options into the WordPress options table. 
	* Does not update, only adds and so this method is only suitable
	* for activation.
	* 
	* We focus on adding missing options when they are required after the
	* first time installation.
	* 
	* @version 1.1
	*/
	public function install_options() {
        $single_options = self::get_option_information( 'single', 'all' );
        $merged_options = self::get_option_information( 'merged', 'all' );
        $all_options = array_merge( $single_options, $merged_options );
        if( $all_options )
        {
			foreach( $all_options as $option_name => $option_information )
			{
				if( $option_information[0] === 1 )
				{
					add_option( $option_name, $option_information[3], $option_information[1] );	
				}	
			}
        }
	    return;
	}	

	/**
	* Deletes every option. Do not change. Create a new method
	* for any other approach to disable or uninstall a plugin please.
	* 
	* @version 1.0
	*/
	public function uninstall_options() {
      $single_options = self::get_option_information( 'single', 'all' );
        $merged_options = self::get_option_information( 'merged', 'all' );
        $all_options = array_merge( $single_options, $merged_options );
        if( $all_options )
        {
			foreach( $all_options as $option_name => $option_information )
			{
				if( $option_information[2] === 1 )
				{
					self::delete_option( $option_name );	
				}	
			}
        }
		return;
	}
	
    /**
    * Confirm that a required option or array of options
    * are valid by name.
    * 
    * Pass group if the option/s belong to a group and are not
    * stored as a seperate "non_compact" entry in the options table.
    * 
    * @param mixed $name
    * @param mixed $group
    * @return mixed
    */
    public static function is_valid( $name, $group = null ) {
        if ( is_array( $name ) ) {
            $compact_names = array();
            foreach ( array_keys( self::$grouped_options ) as $_group ) {
                $compact_names = array_merge( $compact_names, self::get_option_names( $_group ) );
            }

            $result = array_diff( $name, self::get_option_names( 'non_compact' ), $compact_names );

            return empty( $result );
        }

        if ( is_null( $group ) || 'non_compact' === $group ) {
            if ( in_array( $name, self::get_option_names( $group ) ) ) {
                return true;
            }
        }

        foreach ( array_keys( self::$grouped_options ) as $_group ) {
            if ( is_null( $group ) || $group === $_group ) {
                if ( in_array( $name, self::get_option_names( $_group ) ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the requested option.  Looks in csv2post_options group 
     * or csv2post_$name as appropriate.
     *
     * @version 1.2
     * @param string $name Option name
     * @param mixed $default (optional)
     * 
     * @todo how can we get a grouped option without giving the group?
     * If two groups have the same option name the returned value could be
     * wrong. Add some lines that compares all groups and raises a specific
     * error advising the developer to change the option name.
     */
    public static function get_option( $name, $default = false, $maybe_unserialize = true ) {
    	                            
    	// First check if the requested option is a non_compact one.
        if ( self::is_valid( $name, 'non_compact' ) ) {
            $option_value = get_option( "csv2post_$name", $default );
            if( $maybe_unserialize )
            {
				return maybe_unserialize( $option_value );
            }
        }

        // Must be a grouped option, loop through groups.
        foreach ( array_keys( self::$grouped_options ) as $group ) {
            if ( self::is_valid( $name, $group ) ) {
                return self::get_grouped_option( $group, $name, $default );
            }
        }

        trigger_error( sprintf( 'Invalid CSV 2 POST option name: %s', $name ), E_USER_WARNING );

        return $default;
    }

    /**
    * Update a giving grouped option. Will add the $value if it
    * does not already exist. The $name is the key.
    * 
    * @param mixed $group
    * @param mixed $name
    * @param mixed $value
    */                               
    private function update_grouped_option( $group, $name, $value ) {
        $options = get_option( self::$grouped_options[ $group ] );
        if ( ! is_array( $options ) ) {
            $options = array();
        }
        $options[ $name ] = $value;

        return update_option( self::$grouped_options[ $group ], $options );
    }

    /**
     * Updates the single given option.
     * Updates csv2post_options or jetpack_$name as appropriate.
     *
     * @param string $name Option name
     * @param mixed $value Option value
     * @param string $autoload If not compact option, allows specifying whether to autoload or not
     * 
     * @todo Check original functions use of do('pre_update_jetpack_option_
     * which requires add_action that calls the delete method in this class.
     * Why delete every option prior to update?
     */
    public function update_option( $name, $value, $autoload = null ) {
        if ( self::is_valid( $name, 'non_compact' ) ) {
            /**             
             * Allowing update_option to change autoload status only shipped in WordPress v4.2
             * @link https://github.com/WordPress/WordPress/commit/305cf8b95
             */
            if ( version_compare( $GLOBALS['wp_version'], '4.2', '>=' ) ) {
                return update_option( "csv2post_$name", $value, $autoload );
            }
            return update_option( "csv2post_$name", $value );
        }

        foreach ( array_keys( self::$grouped_options ) as $group ) {
            if ( self::is_valid( $name, $group ) ) {
                return self::update_grouped_option( $group, $name, $value );
            }
        }

        trigger_error( sprintf( 'Invalid CSV 2 POST option name: %s', $name ), E_USER_WARNING );

        return false;
    }

    /**
     * Updates the multiple given options.  Updates jetpack_options and/or 
     * jetpack_$name as appropriate.
     *
     * @param array $array array( option name => option value, ... )
     */
    public function update_options( $array ) {
        $names = array_keys( $array );

        foreach ( array_diff( $names, self::get_option_names(), self::get_option_names( 'non_compact' ), self::get_option_names( 'private' ) ) as $unknown_name ) {
            trigger_error( sprintf( 'Invalid CSV 2 POST option name: %s', $unknown_name ), E_USER_WARNING );
            unset( $array[ $unknown_name ] );
        }

        foreach ( $names as $name ) {
            self::update_option( $name, $array[ $name ] );
        }
    }

    /**
     * Deletes the given option.  May be passed multiple option names as an array.
     * Updates csv2post_options and/or deletes csv2post_$name as appropriate.
     *
     * @param string|array $names
     */
    public function delete_option( $names ) {
        $result = true;
        $names  = (array) $names;

        if ( ! self::is_valid( $names ) ) {
            trigger_error( sprintf( 'Invalid CSV 2 POST option names: %s', print_r( $names, 1 ) ), E_USER_WARNING );

            return false;
        }

        foreach ( array_intersect( $names, self::get_option_names( 'non_compact' ) ) as $name ) {
            if ( ! delete_option( "csv2post_$name" ) ) {
                $result = false;
            }
        }

        foreach ( array_keys( self::$grouped_options ) as $group ) {
            if ( ! self::delete_grouped_option( $group, $names ) ) {
                $result = false;
            }
        }

        return $result;
    }

    /**
    * Get one of many groups of options then return a value from within the
    * group.
    * 
    * @param string $group non_compact, private, compact 
    * @param mixed $name
    * @param mixed $default
    */
    private static function get_grouped_option( $group, $name, $default ) {
        $options = get_option( self::$grouped_options[ $group ] );
    	
    	// Does the group have the giving option name?
        if ( is_array( $options ) && isset( $options[ $name ] ) ) {
            return $options[ $name ];
        }

        return $default;
    }

    /**
    * Delete an option value from grouped options.
    * 
    * @param mixed $group
    * @param mixed $names
    */
    private function delete_grouped_option( $group, $names ) {
        $options = get_option( self::$grouped_options[ $group ], array() );

        $to_delete = array_intersect( $names, self::get_option_names( $group ), array_keys( $options ) );
        if ( $to_delete ) {
            foreach ( $to_delete as $name ) {
                unset( $options[ $name ] );
            }

            return update_option( self::$grouped_options[ $group ], $options );
        }

        return true;
    }

}
