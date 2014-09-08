<?php

	require_once('LeLogger.php');
	
/**********
*  BEGIN - User - Defined Variables
***********/


	// put your Logentries Log Token inside the double quotes in the $LOGENTRIES_TOKEN constant below.
  	
  	$LOGENTRIES_TOKEN = "";



/*  
*	To Send Log Events To Your DataHub, Change The Following Variables
*		1. Change the $DATAHUB_ENABLED variable to true;	
*		2. IP Address of your datahub location  
*		3. Set the Port for communicating with Datahub (10000 default) 
*
*		NOTE: If $DATAHUB_ENABLED = true, Datahub will ignore your Logentries log token as it is not required when using Datahub.
*/
	
	$DATAHUB_ENABLED = false;
	
	
	// Your DataHub IP Address MUST be specified if $DATAHUB_ENABLED = true
 	
 	$DATAHUB_IP_ADDRESS = "";
	
		
	//	  Default port for DataHub is 10000, 
	//    If you change this from port 10000, you will have to change your settings port on your datahub machine, 
	//	  specifically in the datahub local config file in /etc/leproxy/leproxyLocal.config then restart leproxy - sudo service leproxy restart
	
	$DATAHUB_PORT = 10000;	
	
	
	// Allow Your Host Name To Be Printed To Your Log Events As Key / Value Pairs.
	// To give your Log events a Host_Name which will appear in your logs as Key Value Pairs, change this value to 'true' (without quotes)

	$HOST_NAME_ENABLED = false;

	
	// Enter a Customized Host Name to appear in your Logs - If no host name is entered one will be assigned based on your own Host name for the local machine using the php function gethostname();

	$HOST_NAME = "";
 
	
	
	// Enter a Host ID to appear in your Log events
	// if $HOST_ID is empty "", it wil not print to your log events.  This value will only print to your log events if there is a value below as in $HOST_ID="12345".
	
	$HOST_ID = "";
	
	
	
/************
*  END  -  User - Defined Variables
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
	

	$log = LeLogger::getLogger($LOGENTRIES_TOKEN, $Persistent, $SSL, $Severity, $DATAHUB_ENABLED, $DATAHUB_IP_ADDRESS, $DATAHUB_PORT, $HOST_ID, $HOST_NAME, $HOST_NAME_ENABLED);