<?php

	class LeLoggerTest extends PHPUnit_Framework_TestCase
	{
		/**
		 *	@expectedException PHPUnit_Framework_Error_Warning
		 */
		public function testOneParameter()
		{
			$this->assertNotInstanceOf('LeLogger.php', LeLogger::getLogger('token'));
		}

		/**
		 *  @expectedException PHPUnit_Framework_Error_Warning
		 */
		public function testTwoParameter()
		{
			$this->assertNotInstanceOf('LeLogger', LeLogger::getLogger('token', false));
		}

		/**
		 *  @expectedException PHPUnit_Framework_Error_Warning
		 */
		public function testThreeParameter()
		{
			$this->assertNotInstanceOf('LeLogger', LeLogger::getLogger('token', false, false));
		}

		public function testAllParameters()
		{
			$this->assertInstanceOf('LeLogger', LeLogger::getLogger('token', false, false, LOG_DEBUG, false, "", 10000, "", "", false, true));

		}

		public function testMultiplyConnections()
		{
			$logFirst = LeLogger::getLogger('token1', false, false, LOG_DEBUG, false, "", 10000, "", "", false, true);
			$logSecond = LeLogger::getLogger('token2', false, false, LOG_DEBUG, false, "", 10000, "", "", false, true);
			$logThird = LeLogger::getLogger('token3', false, false, LOG_DEBUG, false, "", 10000, "", "", false, true);

			$this->assertNotEquals('token1', $logSecond->getToken());
			$this->assertNotEquals('token2', $logThird->getToken());

			$this->assertEquals('token1', $logFirst->getToken());
			$this->assertEquals('token2', $logSecond->getToken());
			$this->assertEquals('token3', $logThird->getToken());
		}

		public function testIsPersistent()
		{
			$log = LeLogger::getLogger('token', false, true, LOG_DEBUG, false, "", 10000, "", "", false, true);

			$this->assertFalse($log->isPersistent());

			$this->tearDown();

			$log = LeLogger::getLogger('token', true, true, LOG_DEBUG, false, "", 10000, "", "", false, true);

			$this->assertTrue($log->isPersistent());
		}

		public function testIsTLS()
		{
			$log = LeLogger::getLogger('token',false,false, LOG_DEBUG, false, "", 10000, "", "", false, true);

			$this->assertFalse($log->isTLS());

			$this->tearDown();

			$log = LeLogger::getLogger('token', true, true, LOG_DEBUG, false, "", 10000, "", "", false, true);

			$this->assertTrue($log->isTLS());
		}

		public function testGetPort()
		{

			$log = LeLogger::getLogger('token', true, false, LOG_DEBUG, false, "",  10000, "", "", false, true);


			$this->assertEquals(10000, $log->getPort());

			$this->tearDown();

			$log = LeLogger::getLogger('token', true, true, LOG_DEBUG, false, "", 0, "", "", false, true);

			$this->assertEquals(20000, $log->getPort());
		}

		public function testGetAddress()
		{
			$log = LeLogger::getLogger('token', true, false, LOG_DEBUG, false, "", 10000, "", "", false, true);

			$this->assertEquals('tcp://data.logentries.com', $log->getAddress());

			$this->tearDown();
			$log = LeLogger::getLogger('token', true, true, LOG_DEBUG, false, "", 10000, "", "", false, true);


			$this->assertEquals('tls://api.logentries.com', $log->getAddress());
		}

		public function tearDown()
		{
			LeLogger::tearDown();
		}
	}	
?>
