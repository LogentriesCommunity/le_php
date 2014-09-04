Logging to Logentries with Php [![Build Status](https://travis-ci.org/logentries/le_php.png)](https://travis-ci.org/logentries/le_php)
=======================================

With these simple steps you can send your Php application logs to Logentries.

Firsly you must register an account on Logentries.com, this only takes a few seconds.

Logentries Setup
----------------

When you have made your account on Logentries. Log in and create a new host with a name that best represents your app.

Then, click on your new host and inside that, create a new log file with a name that represents what you are logging,

example:  'myerrors'. Bear in mind, these names are purely for your own benefit. Under source type, select Token TCP

and click Register. You will notice a token appear beside the name of the log, these is a unique identifier that the logging

library will use to access that logfile. You can copy and paste this now or later.

Download
--------
This repo can be found on packagist here: https://packagist.org/packages/logentries/logentries

Parameter Setup
---------------
Inside the `le_php-master` folder, open `logentries.php` as you need to fill in a parameter, `LOGENTRIES_TOKEN`.

`LOGENTRIES_TOKEN` is the token we copied earlier from the Logentries UI. It associates that logger with the log file on Logentries.



Adding a Custom Host Name and Host ID sent in your PHP log events
---------------
To set a custom Host Name that will appear in your PHP log events as Key / Value pairs:

Inside the `le_php-master` folder, open `logentries.php` and fill in the parameters as follows:

	$HOST_NAME_ENABLED = true;

	$HOST_NAME = "Custom_host_name_here";

	$HOST_ID = "Custom_ID_here_12345";

The $HOST_NAME constant can be left as an empty string, and the library will automatically attempt to assign a host name from 
your local host machine and use that as the custom host name.

To set a custom Host ID that will appear in your PHP log events as Key / Value pairs:
Enter a value instead of the empty string in $HOST_ID = "";
If no $HOST_ID is set and the empty string is left unaltered, no Host ID or Key / Value pairing will appear in your PHP logs.



Sending your PHP Log Events To DataHub 
---------------

You can send your PHP log events to your Logentries DataHub.  
To do this you must set three user defined constants in the logentries.php file

	1. Change the $DATAHUB_ENABLED constant to true as in $DATAHUB_ENABLED = true;	
	2. Set the IP Address of your DataHub machine in $DATAHUB_IP_ADDRESS = "";
	3. Set the Port for communicating with DataHub (10000 default) in $DATAHUB_PORT = 10000;	

If you change the $DATAHUB_PORT from port 10000, you will have to change your settings port on your DataHub machine, 
specifically in the DataHub local config file in /etc/leproxy/leproxyLocal.config then restart leproxy - sudo service leproxy restart


Code Setup
----------

Now you need to download the library from the Downloads Tab, unzip and place the folder in your apps directory.

To use it in your code, enter the following lines, making changes accordingly if you place it in a different location.

	require dirname(__FILE__) . '/le_php-master/logentries.php';
	
	// The following levels are available
	$log->Debug(" ");
	$log->Info(" ");
	$log->Notice(" ");
	$log->Warn(" ");
	$log->Crit(" ");
	$log->Error(" ");
	$log->Alert(" ");
	$log->Emerg(" ");
	
	
updated 2014-09-03 11:55
