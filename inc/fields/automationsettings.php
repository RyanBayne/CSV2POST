<?php

$intro = __( 'Allow or disallow plugins to be included in the WTG automation
system. The WTG automation system allows all integrated plugin automation to be
balanced. This form allows you to include a plugin and select individual actions.
Actions can be setup in the class-schedule.php file and they can do anything you
need them to. The default ones are the most common examples only. Each function
has default settings. Use the Create New Event form to use any action but with
your own specified settings.', 'csv2post' );

$this->UI->postbox_content_header( 
    $box['title'], 
    $box['args']['formid'],
    $intro, 
    false 
);        

$this->FORMS->form_start( $formid, $formid, $box['title'] );
?>
        
<table class="form-table">

<?php 
// Global switch for WebTechGlobal automation class.
$autoswitch_current = get_option( 'csv2post_auto_switch', 'csv2post' );
$this->FORMS->boolean_basic( 
	$formid, 
	'automationswitch', 
	'automationswitch', 
	__( 'Automation Switch', 'csv2post' ), 
	0, 
	$autoswitch_current, 
	false 
);

// Plugin switch for CSV 2 POST administrator triggered automation.
$adminauto_current = get_option( 'csv2post_adm_trig_auto', 'csv2post' );
$this->FORMS->boolean_basic( 
	$formid, 
	'adminautotrigswitch', 
	'adminautotrigswitch', 
	__( 'Administration Triggered Automation', 'csv2post' ), 
	0, 
	$adminauto_current, 
	false 
);

// TODO: add check boxes for individual admin triggered auto actions. See administrator_triggered_automation().

// Display a list of the plugins that have been added to the automation system.
$auto_plugins = get_option( 'csv2post_auto_plugins' );

// Build array of methods from class-schedule.php which are setup as auto actions.
$auto_actions = array();// populate directly from schedule-class.php
$items_array = array();
$current_values_array = array();

if( !$auto_plugins )
{
	$this->FORMS->input_subline( 
		__( 'No plugins have been registered for automation. 
		You must submit the Register Plugins Automation form
		in each plugin. Once registered they will be included
		in the schedule and automation system.', 'csv2post' ), 
		__( 'No Automated Plugins', 'csv2post' ) 
	);	
}   
else
{ 
	// Loop through plugins registered for automation only.
	foreach( $auto_plugins as $pluginname => $plugindetails )
	{                                                  
	    $items_array[ $pluginname ] = $plugindetails['title'];
	    
	    if( $plugindetails['status'] === true )
	    {	
	        $current_values_array[ $pluginname ] = true;
		}                  
		 
		// Get auto_ methods from schedule array for this plugin.
		$this->AUTO = new CSV2POST_Automation();
		$new_actions = $this->AUTO->get_plugins_schedule_methods( $pluginname, $plugindetails );
		if( !empty( $new_actions ) ) 
		{
			foreach( $new_actions as $key => $method_name )
			{
				// Build an ID including the Class because we may work with multiple classes in future.
				$item_id = 'CSV2POST_Schedule_' . $method_name;
				$auto_actions[ $item_id ] = $method_name;
			}
		}                                                            
		
		unset($this->AUTO);
	}
	
	// Display check list of plugins registered for automation.
	if( !$items_array )
	{
		$this->FORMS->input_subline( 
			__( 'No plugins have been registered. Please visit each plugins mains
			settings view to intiate the plugin for the first time.', 'csv2post' ), 
			__( 'Automated Plugins List', 'csv2post' ) 
		);	
	}   
	else
	{             
		$this->FORMS->checkboxesgrouped_basic( 
			$box['args']['formid'], 
			'autopluginslist', 
			'autopluginslist', 
			__( 'Automated Plugins List', 'csv2post' ), 
			$items_array, 
			$current_values_array,/* Current value array. */ 
			false,
			array()
		);
	}
	
	// Display list of registered actions for all registered plugins.		           
	if( !is_array( $auto_actions ) || empty( $auto_actions ) ) 
	{
		$message = __( 'No automated actions were found. A developer can add methods to the
		schedule-class.php file and that will setup new automated actions.', 'csv2post' );	
		
		$this->FORMS->input_subline(
		    $message,
		    __( 'Automated Actions List', 'csv2post')     
		);			
	}
	else 
	{
		// Get actions settings. Actions need to be initialized by user to trigger.
		$actionsettings = get_option( 'csv2post_auto_actionsettings' );

		// Preparing the current values array requires building an item ID which includes class and method.
		$current_values_array = array();
		if( $actionsettings )
		{
			foreach( $actionsettings as $plugin => $classes )
			{
				foreach( $classes as $class => $actions )
				{
					foreach( $actions as $method => $actions_settings )
					{           
						$id = $class . '_' . $method;
						$current_values_array[ $id ] = $actions_settings['status'];	
					}	
				}	
			}
		}

	    // A basic list of actions, a more detailed list will be added elsewhere.
		$this->FORMS->checkboxesgrouped_basic( 
		    $box['args']['formid'], 
		    'autoactionslist', 
		    'autoactionslist', 
		    __( 'Automated Actions List', 'csv2post' ), 
		    $auto_actions, 
		    $current_values_array,/* current values array */ 
		    false, 
		    array()
		); 
	}		
}  
?>
</table>   