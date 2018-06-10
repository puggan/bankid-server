<?php

	namespace Test;

	use PHPUnit\Framework\TestCase;
	use Puggan\BankIDServer\Data\Request;
	use Puggan\BankIDServer\Data\Token;
	use Puggan\BankIDServer\Exceptions\Exception;

	/**
	 * Class ServerTest
	 * @package Test
	 * @property string identification
	 */
	class ServerTest extends TestCase
	{
		private $identification = '201010101010';

		/**
		 * @param array $extra
		 * @param array $extra_data
		 *
		 * @return array
		 */
		private function request_array($extra = [], array $extra_data = [])
		{
			return $extra + [
				'ip' => '127.0.0.1',
				'port' => 1234,
				'ua' => 'PHP CLI',
				'url' => 'https://test/v5/auth',
				'method' => 'POST',
				'content_type' => 'application/json',
				'data' => json_encode($extra_data + $this->request_data())
			];
		}

		/**
		 * @return array
		 */
		private function request_data()
		{
			return [
				'personalNumber' => $this->identification,
				'endUserIp' => '194.168.2.25',
				'userVisibleData' => base64_encode('This is a sample text to be signed'),
			];
		}

		/**
		 * @throws Exception
		 */
		public function testNormal()
		{
			$a = (new Request($this->request_array()))->handle();
			$this->assertEquals(200, $a->status);
			$this->assertNotNull($a->data);
			$this->assertNotNull($a->data['orderRef']);
			$this->assertNotNull($a->data['autoStartToken']);

			$b = (new Request($this->request_array(['url' => 'https://test/v5/cancel'], ['orderRef' => $a->data['orderRef']])))->handle();
			$this->assertEquals(200, $b->status);

			$c = (new Request($this->request_array(['url' => 'https://test/v5/sign'])))->handle();
			$this->assertEquals(200, $c->status);
			$this->assertNotNull($c->data);
			$this->assertNotNull($c->data['orderRef']);
			$this->assertNotNull($c->data['autoStartToken']);

			$d = (new Request($this->request_array(['url' => 'https://test/complete/0/v5/collect'], ['orderRef' => $c->data['orderRef']])))->handle();
			$this->assertEquals(200, $d->status);
			$this->assertNotNull($d->data);
			$this->assertEquals($c->data['orderRef'], $d->data['orderRef']);
			$this->assertEquals(Token::STATUS_COMPLETE, $d->data['status']);

			$this->assertNotNull($d->data['completionData']);
			$this->assertNotNull($d->data['completionData']['user']);
			$this->assertNotNull($d->data['completionData']['user']['personalNumber']);
			$this->assertEquals($this->identification, $d->data['completionData']['user']['personalNumber']);
			$this->assertNotNull($d->data['completionData']['user']['name']);
			$this->assertNotNull($d->data['completionData']['user']['givenName']);
			$this->assertNotNull($d->data['completionData']['user']['surname']);
			$this->assertNotNull($d->data['completionData']['device']);
			$this->assertNotNull($d->data['completionData']['device']['ipAddress']);
			$this->assertNotNull($d->data['completionData']['cert']);
			$this->assertNotNull($d->data['completionData']['cert']['notBefore']);
			$this->assertNotNull($d->data['completionData']['cert']['notAfter']);
			$this->assertNotNull($d->data['completionData']['signature']);
			$this->assertNotNull(base64_decode($d->data['completionData']['signature']));
			$this->assertNotNull($d->data['completionData']['ocspResponse']);
			$this->assertNotNull(base64_decode($d->data['completionData']['ocspResponse']));
		}
	}
