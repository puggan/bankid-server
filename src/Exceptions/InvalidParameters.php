<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-06-09
	 * Time: 11:33
	 */

	namespace Puggan\BankIDServer\Exceptions;

	use Puggan\BankIDServer\Data\Response;

	class InvalidParameters extends Exception
	{
		public function make_response() : Response
		{
			return Response::make_error('invalidParameters', $this->getMessage())->linkException($this);
		}
	}