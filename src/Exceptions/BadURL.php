<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-06-09
	 * Time: 09:18
	 */

	namespace Puggan\BankIDServer\Exceptions;

	use Puggan\BankIDServer\Data\Response;
	use Throwable;

	class BadURL extends Exception
	{
		public function make_response() : Response
		{
			return Response::make_error('notFound', $this->getMessage(), 404)->linkException($this);
		}
	}