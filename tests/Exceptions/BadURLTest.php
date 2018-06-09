<?php

	namespace Test\Exceptions;

	use Puggan\BankIDServer\Exceptions\BadURL;

	class BadURLTest extends ExceptionTest
	{
		public function setUp()
		{
			$this->e = new BadURL('text');
		}
	}
