<?php

	namespace Test\Exceptions;

	use Puggan\BankIDServer\Exceptions\InternalError;

	class InternalErrorTest extends ExceptionTest
	{
		public function setUp()
		{
			$this->e = new InternalError('text');
		}
	}
