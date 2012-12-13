<?php

	require_once('LeLogger.php');
	
	$LOGENTRIES_TOKEN = "";
	
	/*
	*  LOGENTRIES_TOKEN must be set to a logfile token obtained from Logentries, if not using ENV_TOKEN
	*/
	
	// Ignore this, used for PaaS that support configuration variables
	$ENV_TOKEN = getenv('LOGENTRIES_TOKEN');
	
	// Check for environment variable first and override LOGENTRIES_TOKEN variable accordingly
	if ($ENV_TOKEN != false && $LOGENTRIES_TOKEN === "")
	{
		$LOGENTRIES_TOKEN = $ENV_TOKEN;
	}
	
	$log = LeLogger::getLogger('LOGGER_NAME', $LOGENTRIES_TOKEN);
?>
