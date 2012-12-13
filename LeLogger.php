<?php

/**
* Logging library for use with Logentries
*
* Usage:
* $log = LeLogger::getLogger('mylogger', 'ad43g-dfd34-df3ed-3d3d3');
* $log->Info("I'm an informational message");
* $log->Warn("I'm a warning message");
*
* Design inspired by KLogger library which is available at 
*   https://github.com/katzgrau/KLogger.git
*
* @author Mark Lacomber <marklacomber@gmail.com>
* @version 1.2
*/

class LeLogger 
{
	//Some standard log levels
	const ERROR = 0;
	const WARN = 1;
	const NOTICE = 2;
	const INFO = 3;
	const DEBUG = 4;

	const STATUS_SOCKET_OPEN = 1;
	const STATUS_SOCKET_FAILED = 2;
	const STATUS_SOCKET_CLOSED = 3;

	// Logentries server address for receiving logs
	const LE_ADDRESS = 'api.logentries.com';
	// Logentries server port for receiving logs by token
	const LE_PORT = 10000;

	private $_socket = null;

	private $_socketStatus = self::STATUS_SOCKET_CLOSED;

	private $_defaultSeverity = self::DEBUG;

	private $_severityThreshold = self::INFO;

	private $_loggerName = null;

	private $_logToken = null;
	
	private static $_timestampFormat = 'Y-m-d G:i:s';
	
	private static $instances = array();

	public static function getLogger($loggerName, $token, $use_tcp=true, $severity=false)
	{
		if ($loggerName === "")
		{
			return;
		}

		if ($severity === false)
		{
			$severity = self::DEBUG;
		}
	
		if (in_array($loggerName, self::$instances)) {
			return self::$instances[$loggerName];
		}

		self::$instances[$loggerName] = new self($loggerName, $token, $use_tcp, $severity);

		return self::$instances[$loggerName];
	}

	private function __construct($loggerName, $token, $use_tcp, $severity)
	{
		$this->_logToken = $token;		

		$this->_severityThreshold = $severity;

		//Make socket
		try{
			$this->_createSocket($use_tcp);
		}catch(Exception $ex){
			echo "Error connecting to Logentries, reason: " . $ex->getMessage();
		}
	}

	public function __destruct()
	{
		if ($this->_socket != null) {
			socket_close($this->_socket);
			$this->_socketStatus = self::STATUS_SOCKET_CLOSED;
		}
	}

	public function _createSocket($use_tcp)
	{
		if ($use_tcp === true)
		{
			$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		}
		else{
			$this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		}
		
		if ($this->_socket === false)
		{
			echo "Could not create socket for Logentries Logger, reason: " . socket_strerror(socket_last_error()) . "\n";
			$this->_socketStatus = self::STATUS_SOCKET_FAILED;
			return;
		}

		$result = socket_connect($this->_socket, self::LE_ADDRESS, self::LE_PORT);

		if ($result === false)
		{
			echo "Could not connect to Logentries, reason: " . socket_strerror(socket_last_error()) . "\n";
			$this->_socketStatus = self::STATUS_SOCKET_FAILED;
			return;
		}
	
		socket_set_nonblock($this->_socket);

		$this->_socketStatus = self::STATUS_SOCKET_OPEN;
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

	public function log($line, $severity)
	{
		if ($this->_socket === null)
		{
			$this->_createSocket();
		}

		if ($this->_severityThreshold >= $severity) {
			$prefix = $this->_getTime($severity);

			$line = $prefix . $line;

			$this->writeToSocket($line . PHP_EOL);
		}
	}

	public function writeToSocket($line)
	{
		if ($this->_socketStatus == self::STATUS_SOCKET_OPEN)
		{
			$finalLine = $this->_logToken . $line;
			socket_write($this->_socket, $finalLine, strlen($finalLine));	
		}
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
