<?php

	namespace Test\Exceptions;

	use Puggan\BankIDServer\Exceptions\UnsupportedMediaType;

	class UnsupportedMediaTypeTest extends ExceptionTest
	{
		public function setUp()
		{
			$this->e = new UnsupportedMediaType('text');
		}
	}
