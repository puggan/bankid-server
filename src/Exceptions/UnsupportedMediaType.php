<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-06-09
	 * Time: 12:30
	 */

	namespace Puggan\BankIDServer\Exceptions;

	use Puggan\BankIDServer\Data\Response;

	class UnsupportedMediaType extends Exception
	{
		public function make_response() : Response
		{
			return Response::make_error('unsupportedMediaType', $this->getMessage(), 415)->linkException($this);
		}
	}