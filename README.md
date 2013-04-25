Logging to Logentries with Php
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

Parameter Setup
---------------
Download this repo as a ZIP file and unzip it into your app's directroy.

Inside the `le_php-master` folder, open `logentries.php` as you need to fill in a parameter, `LOGENTRIES_TOKEN`.

`LOGENTRIES_TOKEN` is the token we copied earlier from the Logentries UI. It associates that logger with the log file on Logentries.


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
