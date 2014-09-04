<?php

/**
* Logging library for use with Logentries
*
* Usage:
* $log = LeLogger::getLogger('ad43g-dfd34-df3ed-3d3d3');
* $log->Info("I'm an informational message");
* $log->Warn("I'm a warning message");
* $log->Warning("I'm also a warning message");
*
* Design inspired by KLogger library which is available at 
*   https://github.com/katzgrau/KLogger.git
*
* @author Mark Lacomber <marklacomber@gmail.com>
*
* @version 1.6
*/

class LeLogger 
{
	//BSD syslog log levels
	/*
	 *  Emergency
	 *  Alert
	 *  Critical
	 *  Error
 	 *  Warning
	 *  Notice
	 *  Info
	 *  Debug
	 */

	// Logentries server address for receiving logs
	const LE_ADDRESS = 'tcp://api.logentries.com';
	// Logentries server address for receiving logs via TLS
	const LE_TLS_ADDRESS = 'tls://api.logentries.com';
	// Logentries server port for receiving logs by token
	const LE_PORT = 10000;
	// Logentries server port for receiving logs with TLS by token
	const LE_TLS_PORT = 20000;

	private $resource = null;

	private $_logToken = null;
	
	private $_datahubIPAddress = "";
	private $use_datahub = false;
	private $_datahubPort = 10000;

	private $use_host_name = false;
	private $_host_name = "";
	private $_host_id = "";

	private $severity = LOG_DEBUG;

	private $connectionTimeout;

	private $persistent = true;

	private $use_ssl = false;
	
	private static $_timestampFormat = 'Y-m-d G:i:s';
	
	private static $m_instance;

	private $errno;
	
	private $errstr;

	public static function getLogger($token, $persistent, $ssl, $severity, $datahubEnabled, $datahubIPAddress, $datahubPort, $host_id, $host_name, $host_name_enabled)
	{	
		if (!self::$m_instance)
		{
			self::$m_instance = new LeLogger($token, $persistent, $ssl, $severity, $datahubEnabled, $datahubIPAddress, $datahubPort, $host_id, $host_name, $host_name_enabled);
		}

		return self::$m_instance;
	}
	
	
	
	// Destroy singleton instance, used in PHPUnit tests
	public static function tearDown()
	{	
		self::$m_instance = NULL;
	}

	private function __construct($token, $persistent, $ssl, $severity, $datahubEnabled, $datahubIPAddress, $datahubPort, $host_id, $host_name, $host_name_enabled)
	{

		if ($datahubEnabled===true)
		{

			// Check if a DataHub IP Address has been entered	
			$this->validateDataHubIP($datahubIPAddress);	
			
			// set Datahub variable values			
			$this->_datahubIPAddress = $datahubIPAddress;
			$this->use_datahub = $datahubEnabled;
			$this->_datahubPort = $datahubPort;	
		
		
			// if datahub is being used the logToken should be set to null
			$this->_logToken = null;	
		}
		else   	// only validate the token when user is not using Datahub
		{
			$this->validateToken($token);	
 			$this->_logToken = $token;
		}	

		if ($host_name_enabled===true)
		{
			$this->use_host_name = $host_name_enabled;
		
				// check host name exist.  If no host name has been specified, get the host name from the local machine, use Key value pairing.		
			if ($host_name ==="")
			{
				$this->_host_name = "host_name=".gethostname();
			}
			else
			{
				$this->_host_name = "host_name=".$host_name;	
			}
			
		}
		else     // no host name desired to appear in logs
		{  
			$this->use_host_name = $host_name_enabled;
			$this->_host_name= "";
			
		}
		
		// check $host_id, if it is empty "", don't leave empty, otherwise modify it as a key value pair for printing to the log event.
		if ($host_id==="")
		{
			$this->_host_id = "";
		}
		else 
		{
		$this->_host_id = "host_ID=".$host_id;
		}		
		
		
		$this->persistent = $persistent;

//**** possible problem here with $ssl not sending.
		$this->use_ssl = $ssl;

//		$this->use_ssl = $use_ssl;

		$this->severity = $severity;

		$this->connectionTimeout = (float) ini_get('default_socket_timeout');
	}


	public function __destruct()
	{
		$this->closeSocket();
	}


	public function validateToken($token){
	
	if (empty($token) ) {
			throw new InvalidArgumentException('Logentries Token was not provided in logentries.php');
		}
	}

	public function validateDataHubIP($datahubIPAddress)
	{
	if (empty($datahubIPAddress) ) {
			throw new InvalidArgumentException('Logentries Datahub IP Address was not provided in logentries.php');
		}
	}

	public function closeSocket()
	{
		if (is_resource($this->resource)){
			fclose($this->resource);
			$this->resource = null;
		}
	}

	public function isPersistent()
	{
		return $this->persistent;
	}

	public function isTLS()
	{
		return $this->use_ssl;
	}



	public function getPort()
	{
		if ($this->isTLS())
		{
			return self::LE_TLS_PORT;
		}
		elseif ($this->isDatahub() )
		{
		 	return $this->_datahubPort;
		}
		else
		{
			return self::LE_PORT;
		}
	}
	
	
		// check if datahub is enabled
	public function isDatahub()
	{
		return $this->use_datahub;
	}
	
	
	public function isHostNameEnabled()
	{
	return $this->use_host_name;  
	}
	

	public function getAddress()
	{
		if ($this->isTLS() && !$this->isDatahub() )
		{
			return self::LE_TLS_ADDRESS;

		}
		elseif ($this->isDatahub() )
		{
			return $this->_datahubIPAddress;
		}
		else
		{
			return self::LE_ADDRESS;
		}
	}

	public function isConnected()
	{
		return is_resource($this->resource) && !feof($this->resource);
	}

	private function createSocket()
	{
		$port = $this->getPort();
		
		$address = $this->getAddress();
		
		if ($this->isPersistent())
		{
			$resource = $this->my_pfsockopen($port, $address);
		}
		else
		{
			$resource = $this->my_fsockopen($port, $address);
		}
		
		if (is_resource($resource) && !feof($resource)) 
		{
			$this->resource = $resource;
		}
	}
	

	private function my_pfsockopen($port, $address)
	{
         return @pfsockopen($address, $port, $this->errno, $this->errstr, $this->connectionTimeout);
	}

	private function my_fsockopen($port, $address)
	{
		return @fsockopen($address, $port, $this->errno, $this->errstr, $this->connectionTimeout);
	}

	public function Debug($line)
	{
		$this->log($line, LOG_DEBUG);
	}

	public function Info($line)
	{
		$this->log($line, LOG_INFO);
	}

	public function Notice($line)
	{
		$this->log($line, LOG_NOTICE);
	}

	public function Warning($line)
	{
		$this->log($line, LOG_WARNING);
	}

	public function Warn($line)
	{
		$this->Warning($line);
	}

	public function Error($line)
	{
		$this->log($line, LOG_ERR);
	}

	public function Err($line)
	{
		$this->Error($line);
	}

	public function Critical($line)
	{
		$this->log($line, LOG_CRIT);
	}

	public function Crit($line)
	{
		$this->Critical($line);
	}

	public function Alert($line)
	{
		$this->log($line, LOG_ALERT);
	}

	public function Emergency($line)
	{
		$this->log($line, LOG_EMERG);
	}

	public function Emerg($line)
	{
		$this->Emergency($line);
	}

	public function log($line, $curr_severity)
	{
		$this->connectIfNotConnected();

		if ($this->severity >= $curr_severity) {
			$prefix = $this->_getTime($curr_severity);

			$multiline = $this->substituteNewline($line);

			$data = $prefix . $multiline . PHP_EOL;

			$this->writeToSocket($data);
		}
	}


public function writeToSocket($line)
	{

		if ($this->isHostNameEnabled())
		{
			$finalLine = $this->_logToken . " " . $this->_host_id . " " . $this->_host_name . " " . $line;
		}
		else
		{
			$finalLine = $this->_logToken . $this->_host_id . " " . $line;
		}
		
		
		if($this->isConnected())
		{
			fputs($this->resource, $finalLine);
		}
	}


	private function substituteNewline($line)
	{
		$unicodeChar = chr(13);

		$newLine = str_replace(PHP_EOL,$unicodeChar, $line);
				 
		return $newLine;
	}

	private function connectIfNotConnected()
	{
		if ($this->isConnected()){
			return;
		}
		$this->connect();
	}

	private function connect()
	{
		$this->createSocket();
	}

	private function _getTime($level)
	{

		$time = date(self::$_timestampFormat);

		switch ($level) {
			case LOG_DEBUG:
				return "$time - DEBUG - ";
			case LOG_INFO:
				return "$time - INFO - ";
			case LOG_NOTICE:
				return "$time - NOTICE - ";
			case LOG_WARNING:
				return "$time - WARN - ";
			case LOG_ERR:
				return "$time - ERROR - ";
			case LOG_CRIT:
				return "$time - CRITICAL - ";
			case LOG_ALERT:
				return "$time - ALERT - ";
			case LOG_EMERG:
				return "$time - EMERGENCY - ";
			default:
				return "$time - LOG - ";
		}
	}
}
