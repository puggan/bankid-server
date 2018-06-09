<?php

	namespace Test\Exceptions;

	use PHPUnit\Framework\TestCase;
	use Puggan\BankIDServer\Data\Response;
	use Puggan\BankIDServer\Exceptions\Exception;

	/**
	 * Class ExceptionTest
	 * @property Exception $e
	 */
	class ExceptionTest extends TestCase
	{
		public $e;

		public function setUp()
		{
			$this->e = new ExempleException('text');
		}

		public function testConstructor()
		{
			$this->assertInstanceOf(\Throwable::class, $this->e);
			$this->assertInstanceOf(\Exception::class, $this->e);
			$this->assertInstanceOf(Exception::class, $this->e);
		}

		public function testResponse()
		{
			$r = $this->e->make_response();
			$this->assertInstanceOf(Response::class, $r);
			$this->assertEquals($this->e, $r->exception);
		}
	}

	class ExempleException extends Exception
	{
		public function make_response() : Response
		{
			return Response::make_error($this->getMessage(), 'Exemple')->linkException($this);
		}
	}
