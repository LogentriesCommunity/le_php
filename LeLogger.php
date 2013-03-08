<?php

/**
* Logging library for use with Logentries
*
* Usage:
* $log = LeLogger::getLogger('ad43g-dfd34-df3ed-3d3d3');
* $log->Info("I'm an informational message");
* $log->Warn("I'm a warning message");
*
* Design inspired by KLogger library which is available at 
*   https://github.com/katzgrau/KLogger.git
*
* @author Mark Lacomber <marklacomber@gmail.com>
* @version 1.3
*/

class LeLogger 
{
	//Some standard log levels
	const ERROR = 0;
	const WARN = 1;
	const NOTICE = 2;
	const INFO = 3;
	const DEBUG = 4;

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

	private $severity = self::DEBUG;

	private $connectionTimeout;

	private $persistent = true;

	private $use_ssl = false;
	
	private static $_timestampFormat = 'Y-m-d G:i:s';
	
	private static $m_instance;

	private $errno;
	
	private $errstr;

	public static function getLogger($token, $persistent, $ssl, $severity)
	{	
		if (!self::$m_instance)
		{
			self::$m_instance = new LeLogger($token, $persistent, $ssl, $severity);
		}

		return self::$m_instance;
	}

	private function __construct($token, $persistent, $use_ssl, $severity)
	{
		$this->_logToken = $token;		

		$this->persistent = $persistent;

		$this->use_ssl = $use_ssl;

		$this->severity = $severity;

		$this->connectionTimeout = (float) ini_get('default_socket_timeout');
	}

	public function __destruct()
	{
		$this->closeSocket();
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
		}else{
			return self::LE_PORT;
		}
	}

	public function getAddress()
	{
		if ($this->isTLS())
		{
			return self::LE_TLS_ADDRESS;
		}else{
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
		}else{
			$resource = $this->my_fsockopen($port, $address);
		}
		if (!$resource) {
			throw new \UnexpectedValueException("Failed to connect to Logentries ($this->errno: $this->errstr)");
		}
		$this->resource = $resource;
	}

	private function my_pfsockopen($port, $address)
	{
         return pfsockopen($address, $port, $this->errno, $this->errstr, $this->connectionTimeout);
	}

	private function my_fsockopen($port, $address)
	{
		return fsockopen($address, $port, $this->errno, $this->errstr, $this->connectionTimeout);
	}

	public function Debug($line)
	{
		$this->log($line, self::DEBUG);
	}

	public function Info($line)
	{
		$this->log($line, self::INFO);
	}

	public function Warn($line)
	{
		$this->log($line, self::WARN);
	}

	public function Error($line)
	{
		$this->log($line, self::ERROR);
	}

	public function Notice($line)
	{
		$this->log($line, self::NOTICE);
	}

	public function log($line, $curr_severity)
	{
		$this->connectIfNotConnected();

		if ($this->severity >= $curr_severity) {
			$prefix = $this->_getTime($curr_severity);

			$data = $prefix . $line . PHP_EOL;

			$this->writeToSocket($data);
		}
	}

	public function writeToSocket($line)
	{
		$finalLine = $this->_logToken . $line;
		if($this->isConnected())
		{
			fputs($this->resource, $finalLine);
		}
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
			case self::INFO:
				return "$time  INFO - ";
			case self::WARN:
				return "$time - WARN - ";
			case self::ERROR:
				return "$time - ERROR - ";
			case self::NOTICE:
				return "$time - NOTICE - ";
			case self::DEBUG:
				return "$time - DEBUG - ";
			default:
				return "$time - LOG - ";
		}
	}
}
?>
