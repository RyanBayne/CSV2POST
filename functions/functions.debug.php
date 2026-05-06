<?php
/**
* Maintain a short guide to debugging here. 
* 
* @version 1.0
*/
function csv2post_debug_guide() {          
    $debugging_guide = '
    <h2>Error Functions</h2>
    <p>trigger_error( sprintf( Invalid name: %s, $name ), E_USER_WARNING )</p>
    
	<h2>Debugging Functions</h2>
    <h3>The debug_backtrace() Function</h3>
    <p>debug_backtrace() generates a PHP backtrace.</p>     	
    
    <h3>The debug_print_backtrace() Function</h3>
    <p>debug_print_backtrace() prints a PHP backtrace. It prints the 
    function calls, included/required files and eval()ed stuff.</p> 
    	    
    <h3>The error_clear_last() Function</h3>
    <p>Clears the most recent errors, making it unable to be 
    retrieved with error_get_last().</p> 
    
    <h3>The error_get_last() Function</h3>
    <p>Gets information about the last error that occurred.</p> 
    
    <h3>The error_log() Function</h3>
    <p>Sends an error message to the web server\'s error log or to a file.</p> 
    ';
	return;
}               

/**
* Dump requests for $_POST or/and $_GET. An administrator
* with developer capability must activate these individually from
* the Developer Menu.
* 
* @version 1.0
*/
function csv2post_dump_request() {
    if( !current_user_can( 'activate_plugins') )
    {
		return false;
    }

    // Display $_POST dump if user has activated it via developer menu.    
    if( CSV2POST_Options::get_option('postdump') )
    {
		echo '<h1>$_POST</h1>';
		echo '<pre>';
		var_dump( $_POST );
		echo '</pre>';
	}

	// Display $_GET dump if user has activated it via developer menu.
    if( CSV2POST_Options::get_option('getdump') )
    {	
		echo '<h1>$_GET</h1>';
		echo '<pre>';
		var_dump( $_GET );
		echo '</pre>';
	}
	     
	return;
}

/**
* Log an error with extra information.
* 
* Feel free to use error_log() on its own however keep in mind that
* 
* @version 1.0
* 
* @param string $message
* @param int $message_type 0=PHP logger|1=Email|2=Depreciated|3=Append to file|4=SAPI logging handler
* @param string $destination
* @param string $extra_headers
* @param mixed $line
* @param mixed $function
* @param mixed $class
* @param mixed $time
*/
function csv2post_error( $message, $message_type = 0, $destination = null, $extra_headers = null, $line = null, $function = null, $class = null, $time = null ) {
    // TODO: Build the $message value using all parameters.
    
    $message = 'CSV 2 POST: ';
    $message .= $message;
    $message .= ' (get help@webtechglobal.co.uk)';
    
    return error_log( $message, $message_type, $destination, $extra_headers );
}

/**
* Generates a user-level error/warning/notice message.
* 
* Used to trigger a user error condition, it can be used in conjunction 
* with the built-in error handler, or with a user defined function that 
* has been set as the new error handler (set_error_handler()).
*
* This function is useful when you need to generate a particular response 
* to an exception at runtime. 
*
* @param string $error_msg 1024 characters long message.
* @param mixed $error_type please use the E_USER family of constants.
* 
* TODO: list $error_type here from the E_USER family of constants.
*/
function csv2post_error_trigger( $error_msg, $error_type = E_USER_NOTICE ) {
	return trigger_error( $error_msg, $error_type );
}		

/**
* user_error() is an alias of trigger_error().
*/
function csv2post_users_error() {
    return user_error();
}

/**
* Retrieve all error codes. Access public, 
* returns array List of error codes, if available.
* 
* @version 1.0
*/
function csv2post_get_error_codes( $classobject ) {
	return $classobject->get_error_codes();
}

/**
* Retrieve first error code available. Access public, 
* returns string, int or Empty if there is no error codes
* 
* @version 1.0
*/
function csv2post_get_error_code( $classobject ) {
    return $classobject->get_error_code();
}

/**
* Retrieve all error messages or error messages matching code. 
* Access public, returns an array of error strings on success, or 
* empty array on failure (if using code parameter)
* 
* @version 1.0
*/
function csv2post_get_error_messages( $classobject, $code ) {
    return $classobject->get_error_messages( $code );
}		

/**
* Get single error message. This will get the first message available 
* for the code. If no code is given then the first code available will 
* be used. Returns an error string.
* 
* @version 1.0
*/
function csv2post_get_error_message( $classobject, $code ) {
    return $classobject->get_error_messages( $code );
}		

/**
* Retrieve error data for error code. Returns mixed or null, if no errors.
* 
* @version 1.0
*/
function csv2post_get_error_data( $classobject, $code ) {
    return $classobject->get_error_data( $code );
}		

/**
* Append more error messages to list of error messages. No return.
* 
* @version 1.1
*/
function csv2post_error_append( $classobject, $code, $message, $data ) {   
    return $classobject->add( $code, $message, $data );
}		

/**
* Add data for error code. The error code can 
* only contain one error data. No return.
* 
* @version 1.1
*/
function csv2post_error_add_data( $classobject, $data, $code ) {
    return $classobject->add_data( $data, $code );
}		

/**
* Remove any messages and data associated with an error code. No return.
* 
* @version 1.1
*/
function csv2post_error_remove( $classobject, $code ) {
    return $classobject->remove( $code );
}	
	
/**
* Sets a user-defined error handler function.
* 
* This function can be used for defining your own way of handling errors 
* during runtime, for example in applications in which you need to do 
* cleanup of data/files when a critical error happens, or when you need 
* to trigger an error under certain conditions (using trigger_error()).
* 
* The following error types cannot be handled with a user defined function: 
* E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, 
* E_COMPILE_WARNING, and most of E_STRICT raised in the file where 
* set_error_handler() is called.
* 
* @link http://php.net/manual/en/function.set-error-handler.php
* 
* @param mixed $error_handler callable function/method that handles errors.
* @param mixed $error_types E_ALL|E_STRICT
*/
function csv2post_error_handler_set( $error_handler, $error_types = E_ALL ) {
    return set_error_handler ( $error_handler, $error_types );
}

/**
* Restore error handler to the previous/server configured one.
* 
* @version 1.0
*/
function csv2post_error_handler_restore() {
	return restore_error_handler();
}

/**
* restore_exception_handler — Restores the previously defined 
* exception handler function.
* 
* @version 1.0
*/
function csv2post_exception_handler_restore () {
    restore_exception_handler();
	return;
}	

/**
* set_exception_handler — Sets a user-defined exception handler function. 
* 
* @version 1.0
*/
function csv2post_exception_handler_set() {
    set_exception_handler();
	return;
}

/**
* Display and log traces. One function that can do both or either. The idea is
* to setup extensive traces then control all of them with global settings.
* 
* @version 1.0
* 
* @param string $code pass __FUNCTION__ or a unique code i.e. 22JAN2016-ryanbayne
* @param string $message
* @param int $message_type 0=PHP logger|1=Email|2=Depreciated|3=Append to file|4=SAPI logging handler
* @param string $destination
* @param string $extra_headers
* @param mixed $line
* @param mixed $function
* @param mixed $class
* @param mixed $time
*/
function csv2post_trace_primary( $code, $message, $atts = array(), $errorlog = false, $wperror = false, $adminnotice = false ) {

    $trace_display = CSV2POST_Options::get_option( 'debugtracedisplay' );
    $trace_log = CSV2POST_Options::get_option( 'debugtracelog' );
    if( !$trace_display && !$trace_log ){ return; }
    
    // Start with some debug_backtrace() values.
    $debug_backtrace = debug_backtrace();
	$file_one = '';
	$line_one = '';
	$func_one = '';
	$args_one = '';
	$file_two = '';
	$file_two = '';
	$file_two = '';
	$file_two = '';
    if( $debug_backtrace )
    {
		$file_one = $debug_backtrace[0]['file'];
		$line_one = $debug_backtrace[0]['line'];
		$func_one = $debug_backtrace[0]['function'];
		$args_one = $debug_backtrace[0]['args'];
		
		// TODO: these values appear as arrays in the notice.
		$file_two = $debug_backtrace[1]['file'];
		$file_two = $debug_backtrace[1]['line'];
		$file_two = $debug_backtrace[1]['function'];
		$file_two = $debug_backtrace[1]['args'];
    }
    
    // Extract $atts which will be used in each of the methods below.
	$args = shortcode_atts( 
	    array(
	        'message_type'   => 0,// error_log() parameter
	        'destination'	 => null,// error_log() parameter
	        'extra_headers'	 => null,// error_log() parameter
	    ), 
	    $atts
	);
	

    // Display the trace in a notice with formatting.
    // TODO: consider being able to add information to existing trace notice much like in WP_Error().
    if( $adminnotice )
    {   
    	$notice_message = '';

		// Default message acts as the traces description.
		$notice_message .= '<h4>Main Information</h4>';
    	$notice_message .= $message;
    	
    	// Add extra information for the developer debugging.
    	$notice_message .= '<h4>Backtrace Information (debug_backtrace())</h4>';    	
    	$notice_message .= '<ul>';
    	$notice_message .= '<li>File One: '. $file_one .'</li>';
    	$notice_message .= '<li>Line One: '. $line_one .'</li>';
    	$notice_message .= '<li>Function One: '. $func_one .'</li>';
    	$notice_message .= '<li>File Two: '. $file_two .'</li>';
    	$notice_message .= '<li>Line Two: '. $line_two .'</li>';
    	$notice_message .= '<li>Function Two: '. $function_two .'</li>';
    	$notice_message .= '</ul>';
    	
    	// Create the notice which is stored before being displayed.
    	$title = __( 'Trace Results Code: ' . $code, 'csv2post' );
    	// TODO: improve notice to allow specific trade to be disabled, by code
    	// may require a list of trace to undo the action of hiding specific trace.
		CSV2POST_UI::create_notice( $notice_message,'info','Large',$title );	
    }
    
    // Create new WP_Error().
    if( $wperror )
    {
    	new WP_Error( $code, $message, array(/* array */)); 		
    }
    
    // Default server logging method using error_log().
    if( $errorlog )
    {
	    $message = 'CSV 2 POST: ';
	    $message .= $message;
	    $message .= ' (get help@webtechglobal.co.uk)';
	    
	    return error_log( 
	    	$message, 
	    	$args['message_type'], 
	    	$args['destination'], 
	    	$args['extra_headers'] 
	    );

	}
}
?>
