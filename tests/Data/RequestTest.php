<?php

	namespace Test\Data;

	use PHPUnit\Framework\TestCase;
	use Puggan\BankIDServer\Data\Request;

	class RequestTest extends TestCase
	{
		function testNormal()
		{
			$o = new Request(
				[
					'url' => 'https://a:b@127.0.0.1:123/a/b?c=d&e=f',
					'data' => json_encode(['a' => 'b', 'c' => 0, 'd' => NULL]),
				]
			);

			$this->assertEquals('b', $o->json()->a);
			$this->assertEquals('b', $o->validate(['a'])['a']);
			$this->assertEquals('127.0.0.1', $o->url_host);
			$this->assertEquals(123, $o->url_port);
			$this->assertEquals('/a/b', $o->url_path);
			$this->assertEquals('https', $o->url_scheme);
			$this->assertEquals('c=d&e=f', $o->url_query);
		}
	}
