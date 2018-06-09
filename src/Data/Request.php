<?php

	namespace Puggan\BankIDServer\Data;

	use Puggan\BankIDServer\Exceptions\BadURL;
	use Puggan\BankIDServer\Exceptions\InternalError;
	use Puggan\BankIDServer\Exceptions\InvalidParameters;
	use Puggan\BankIDServer\Exceptions\UnsupportedMediaType;
	use Puggan\BankIDServer\Server;

	/**
	 * Class Request
	 * @package Puggan
	 *
	 * @property string ip
	 * @property int port
	 * @property string ua
	 * @property string url
	 * @property string method
	 * @property string content_type
	 * @property string data
	 *
	 * @property string url_scheme
	 * @property string url_host
	 * @property string url_port
	 * @property string url_user
	 * @property string url_pass
	 * @property string url_path
	 * @property string url_query
	 */
	class Request
	{
		public $ip;
		public $port;
		public $ua;
		public $url;
		public $method;
		public $content_type;
		public $data;
		private $json;

		public $url_scheme;
		public $url_host;
		public $url_port;
		public $url_user;
		public $url_pass;
		public $url_path;
		public $url_query;

		/**
		 * Request constructor.
		 *
		 * @param mixed[] $properties
		 */
		public function __construct(Array $properties = [])
		{
			foreach($properties as $key => $value)
			{
				$this->{$key} = $value;
			}
			$this->parse_url();
		}

		/**
		 * Parse $this->url in to $this->url_*
		 */
		function parse_url()
		{
			foreach(parse_url($this->url) as $key => $value)
			{
				$url_key = 'url_' . $key;
				$this->{$url_key} = $value;

			}
		}

		/**
		 * Parse data as json
		 * @return mixed
		 */
		function json()
		{
			if($this->json === NULL)
			{
				$this->json = json_decode($this->data);
			}

			return $this->json;
		}

		/**
		 * Validate that required fields exists,
		 * and return only the wanted fields
		 *
		 * @param string[] $required
		 * @param mixed[] $optional_default
		 *
		 * @return mixed[]
		 * @throws InvalidParameters
		 */
		function validate($required, $optional_default = [])
		{
			$json = $this->json();
			$data = [];
			$missing = [];
			foreach($required as $key)
			{
				if(isset($json->$key))
				{
					$data[$key] = $json->$key;
				}
				else
				{
					$missing[] = $key;
				}
			}
			foreach($optional_default as $key => $default_value)
			{
				if(isset($json->$key))
				{
					$data[$key] = $json->$key;
				}
				else
				{
					$data[$key] = $default_value;
				}
			}
			if($missing)
			{
				throw new InvalidParameters('Missing parameters: ' . implode(', ', $missing));
			}
			return $data;
		}

		/**
		 * @return Response
		 *
		 * @throws InvalidParameters
		 * @throws BadURL
		 * @throws InternalError
		 * @throws UnsupportedMediaType
		 */
		function handle()
		{
			return (new Server($this))->handle();
		}

		/**
		 * @throws BadURL
		 * @throws InternalError
		 * @throws InvalidParameters
		 * @throws UnsupportedMediaType
		 */
		function send_response()
		{
			$this->handle()->send();
		}
	}
