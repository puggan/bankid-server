<?php

	namespace Test\Data;

	use PHPUnit\Framework\TestCase;
	use Puggan\BankIDServer\Data\Response;

	class ResponseTest extends TestCase
	{
		public function testRedirect()
		{
			$r = Response::make_redirect('/other');
			$this->assertInstanceOf(Response::class, $r);
			$this->assertEquals('/other', $r->headers['Location']);
			$this->assertEquals(302, $r->status);
			$this->assertNull($r->data);
		}

		public function testErrorResponse()
		{
			$r = Response::make_error('error_type', 'error description');
			$this->assertInstanceOf(Response::class, $r);
			$this->assertEquals(0, count($r->headers));
			$this->assertEquals(2, count($r->data));
			$this->assertEquals(400, $r->status);
			$this->assertEquals('error_type', $r->data['errorCode']);
			$this->assertEquals('error description', $r->data['details']);
		}

		public function testNormal()
		{
			$r = new Response(['a' => 'b']);
			$this->assertInstanceOf(Response::class, $r);
			$this->assertEquals(0, count($r->headers));
			$this->assertEquals(1, count($r->data));
			$this->assertEquals(200, $r->status);
			$this->assertEquals('b', $r->data['a']);
		}
	}
