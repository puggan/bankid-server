<?php

	namespace Puggan\BankIDServer\Data;

	use Puggan\BankIDServer\Exceptions\Exception;

	/**
	 * Class Response
	 * @package Puggan\BankIDServer\Data
	 *
	 * @property int status
	 * @property string content_type
	 * @property string status_text
	 * @property string[]|string[][] headers
	 * @property mixed data
	 * @property Exception exception
	 */
	class Response
	{
		public $status = 200;
		public $status_text = NULL;
		public $content_type = 'application/json';
		public $headers = [];
		public $data = [];
		public $exception;

		/**
		 * Response constructor.
		 *
		 * @param mixed $data
		 * @param int $status_code
		 * @param string $status_text
		 * @param string[] $extra_headers
		 */
		public function __construct($data, $status_code = 200, $status_text = NULL, array $extra_headers = [])
		{
			$this->data = $data;
			$this->status = $status_code;
			$this->status_text = $status_text;
			$this->headers = $extra_headers;
		}

		/**
		 * @param mixed|null $data
		 */
		public function send($data = NULL)
		{
			header('HTTP/1.1 ' . $this->status . $this->status_text());
			header('Content-type: ' . $this->content_type);
			foreach($this->headers as $key => $value)
			{
				if(is_numeric($key))
				{
					header($value);
				}
				else if(is_array($value))
				{
					foreach($value as $row)
					{
						header($key . ': ' . $row);
					}
				}
				else
				{
					header($key . ': ' . $value);
				}
			}
			if(empty($data))
			{
				$data = $this->data;
			}

			if(empty($data))
			{
				die();
			}

			if(is_string($data))
			{
				echo $data;
				die();
			}

			echo json_encode($data, JSON_PRETTY_PRINT);
			die();
		}

		/**
		 * @return string
		 */
		public function status_text()
		{
			if($this->status_text)
			{
				return $this->status_text;
			}

			switch($this->status)
			{
				case 200:
					return 'OK';
				case 400:
					return 'Bad Request';
				default:
					return 'Error ' . $this->status;
			}
		}

		/**
		 * @param string $error_code
		 * @param string $details
		 * @param int $status_code
		 * @param string $status_text
		 *
		 * @return self
		 */
		public static function make_error($error_code, $details, $status_code = 400, $status_text = NULL) : self
		{
			return new self(['errorCode' => $error_code, 'details' => $details,], $status_code, $status_text);
		}

		/**
		 * @param Exception $exception
		 *
		 * @return $this
		 */
		public function linkException($exception)
		{
			$this->exception = $exception;
			return $this;
		}

		/**
		 * @param $new_url
		 *
		 * @return Response
		 */
		public static function make_redirect($new_url) : self
		{
			return new self(NULL, 302, 'Moved Temporarily', ['Location' => $new_url]);
		}
	}