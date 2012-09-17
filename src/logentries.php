<?php

	require_once('LeLogger.php');

	//Example:  
	//$log = LeLogger::getLogger('mylogger', 'abcde3-edutd-3ec5gt-jye3c3', false, LeLogger::WARN)

	//LOGENTRIES_TOKEN must be filled in with a logfile token obtained from Logentries UI
	$log = LeLogger::getLogger('LOGGER_NAME', 'LOGENTRIES_TOKEN');
?>
