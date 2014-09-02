<?php

	require_once('LeLogger.php');
	
/**********
*  BEGIN User - Defined Variables
***********/


	// put your Log's Logentries token inside the double quotes below.
 	$LOGENTRIES_TOKEN = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxxx";
//	$LOGENTRIES_TOKEN = "6710c1fb-2ed8-405a-8aee-90f7b3508dee";


/*
*	TO SEND LOG EVENTS TO YOUR DATAHUB CHANGE THE FOLLOWING VARIABLES
*		1. Change the $DATAHUB_ENABLED variable to true;	
*		2. IP Address of your datahub location  
*		3. Set the Port for communcating with Datahub (10000 default) 
*
*		NOTE: Datahub will ignore your Logentries log token as it is not required when using Datahub.
*/
	
	$DATAHUB_ENABLED= false;
	
	$DATAHUB_IP_ADDRESS = "54.77.200.13";
		
	// Default port is 10000, 
	// If you change this from port 10000, you will have to change your settings port on your datahub machine, namely the datahub local config file /etc/leproxy/leproxyLocal.config then restart leproxy 
	$DATAHUB_PORT = 10000;	
	
	
// ALLOW YOUR HOST NAME AND HOST ID TO BE PLACED DIRECTLY INTO YOUR LOG EVENTS
	// To give your Log events a Host_Name and Host_ID appear in your logs as Key Value Pairs change this value to 'true' (without quotes)
	$HOST_ID_NAME_ENABLED = true;

	// Enter a Host Name to appear in your Logs - If no host name is entered one will be assigned based on your own Host name for the local machine using the php function gethostname();
	$HOST_NAME = "MY_TEST_HOST";
 
	// Enter a Host ID to appear in your Logs (if $HOST_ID_NAME_ENABLE = true)  if this is set to false, no HOST_ID or HOST_NAME will appear in your logs.
	$HOST_ID = "12345";
	
/************
*  END User - Defined Variables
************/

	
	

	// Whether the socket is persistent or not
	$Persistent = true;

	// Whether the socket uses SSL/TLS or not
	$SSL = false;
	
	// Set the minimum severity of events to send
	$Severity = LOG_DEBUG;
	/*
	 *  END  User - Defined Variables
	 */

	// Ignore this, used for PaaS that support configuration variables
	$ENV_TOKEN = getenv('LOGENTRIES_TOKEN');
	
	// Check for environment variable first and override LOGENTRIES_TOKEN variable accordingly
	if ($ENV_TOKEN != false && $LOGENTRIES_TOKEN === "")
	{
		$LOGENTRIES_TOKEN = $ENV_TOKEN;
	}
	

	$log = LeLogger::getLogger($LOGENTRIES_TOKEN, $DATAHUB_IP_ADDRESS, $Persistent, $SSL, $Severity, $DATAHUB_ENABLED, $DATAHUB_PORT, $HOST_ID, $HOST_NAME, $HOST_ID_NAME_ENABLED);