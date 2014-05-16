<?php

	require_once('LeLogger.php');
	
	/*
	 *  User - Defined Variables
	 */

	$LOGENTRIES_TOKEN = "";

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
	
	$log = LeLogger::getLogger($LOGENTRIES_TOKEN, $Persistent, $SSL, $Severity);
