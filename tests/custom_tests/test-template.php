<?php
error_reporting( E_ERROR & ~E_DEPRECATED & ~E_STRICT );

class PostmediaTest extends WP_UnitTestCase {

	function setUp() {
		#setup code
		parent::setUp();
	}

	# Additional functions for tests need to be prefixed with 'test' i.e.:
	# function testSomeFunctionality(){ }

	function testSample() {
		// replace this with some actual testing code note this causes failure until you add a real test case do not simply cheat and change this to true
		$this->assertTrue( false );
	}

	function tearDown() {
		# tear down code
		parent::tearDown();
	}
}

