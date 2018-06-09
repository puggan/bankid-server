<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-06-09
	 * Time: 09:13
	 */

	namespace Puggan\BankIDServer\Exceptions;

	use Puggan\BankIDServer\Data\Response;

	abstract class Exception extends \Exception
	{
		abstract public function make_response() : Response;

		public function send_response() : void
		{
			$this->make_response()->send();
		}
	}