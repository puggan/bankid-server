<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-06-09
	 * Time: 13:12
	 */

	namespace Puggan\BankIDServer\Exceptions;

	use Puggan\BankIDServer\Data\Response;

	class InternalError extends Exception
	{

		public function make_response() : Response
		{
			return Response::make_error('internalError', $this->getMessage(), 500)->linkException($this);
		}
	}
