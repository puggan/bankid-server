<?php

	namespace Test\Exceptions;

	use Puggan\BankIDServer\Exceptions\InvalidParameters;

	class InvalidParametersTest extends ExceptionTest
	{
		public function setUp()
		{
			$this->e = new InvalidParameters('text');
		}
	}
