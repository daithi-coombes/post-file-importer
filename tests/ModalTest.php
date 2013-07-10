<?php

class ModalTest extends WP_UnitTestCase{

	protected $obj;

	function setUp(){
	}

	function test_foo(){
		$foo = true;
		$this->assertEquals(true, $foo);
	}
}