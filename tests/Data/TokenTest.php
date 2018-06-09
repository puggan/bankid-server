<?php

	namespace Test\Data;

	use PHPUnit\Framework\TestCase;
	use Puggan\BankIDServer\Data\Token;
	use Puggan\BankIDServer\Exceptions\InternalError;
	use Puggan\BankIDServer\Exceptions\InvalidParameters;

	/**
	 * Class TokenTest
	 * @package Test\Data
	 *
	 * @property string db_file
	 */
	class TokenTest extends TestCase
	{
		public $db_file;

		public function setUp()
		{
			$this->db_file = tempnam(sys_get_temp_dir(), 'test_') . '.db';
			Token::$db_file = $this->db_file;
		}

		public function tearDown()
		{
			if(file_exists($this->db_file))
			{
				unlink($this->db_file);
			}
		}

		/**
		 * @throws InternalError
		 * @throws InvalidParameters
		 */
		public function testUnique()
		{
			$a = new Token(NULL, ['endUserIp' => '127.0.0.1']);
			$b = new Token(NULL, ['endUserIp' => '127.0.0.1']);

			$this->assertNotEquals($a->token, $b->token);
		}

		/**
		 * @throws InternalError
		 * @throws InvalidParameters
		 */
		public function testNormal()
		{
			$new_token = new Token(NULL, ['endUserIp' => '127.0.0.1']);
			$this->assertInstanceOf(Token::class, $new_token);
			$this->assertEquals(Token::STATUS_PENDING, $new_token->status);
			$this->assertNull($new_token->user);

			$loaded_token = Token::find($new_token->token);
			$this->assertEquals($new_token->created_at, $loaded_token->created_at);
			$this->assertEquals($new_token->auto_token, $loaded_token->auto_token);

			$new_token->status = Token::STATUS_COMPLETE;
			$new_token->validate();
			$this->assertNotNull($new_token->user);
		}

		/**
		 * @throws InternalError
		 * @throws InvalidParameters
		 */
		public function testBadDate()
		{
			$this->expectException(InvalidParameters::class);
			new Token(NULL, ['endUserIp' => '127.0.0.1', 'personalNumber' => '190000000000']);
		}

		/**
		 * @throws InternalError
		 * @throws InvalidParameters
		 */
		public function testBadLength()
		{
			$this->expectException(InvalidParameters::class);
			$new_token = new Token(NULL, ['endUserIp' => '127.0.0.1', 'personalNumber' => '1900000000000']);
		}
	}
