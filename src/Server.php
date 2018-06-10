<?php

	namespace Puggan\BankIDServer;

	use Puggan\BankIDServer\Data\Request;
	use Puggan\BankIDServer\Data\Response;
	use Puggan\BankIDServer\Data\Token;
	use Puggan\BankIDServer\Exceptions\BadURL;
	use Puggan\BankIDServer\Exceptions\InternalError;
	use Puggan\BankIDServer\Exceptions\InvalidParameters;
	use Puggan\BankIDServer\Exceptions\UnsupportedMediaType;

	/**
	 * Class BankIDServer
	 * @package Puggan\BankIDServer
	 *
	 * @property int version
	 * @property string api_path
	 * @property string prefered_response
	 * @property int prefered_response_time
	 * @property Request request
	 */
	class Server
	{
		/**
		 * Server constructor.
		 *
		 * @param Request $request_data
		 *
		 * @throws BadURL
		 */
		public function __construct(Request $request_data)
		{
			$pattern = '@^/((?<response>[a-zA-Z]+)/)?((?<time>[0-9]+)/)?(rp/)?v(?<version>[0-9]+)(?<api_path>/.*)?$@';
			if(!preg_match($pattern, $request_data->url_path, $regexp_data))
			{
				throw new BadURL('Failed to parse ' . $request_data->url_path);
			}

			$this->prefered_response = $regexp_data['response'] ?? NULL ?: Token::STATUS_COMPLETE;
			$this->prefered_response_time = $regexp_data['time'] ?? NULL ?: 10;
			$this->version = $regexp_data['version'] ?? NULL;
			$this->api_path = $regexp_data['api_path'] ?? NULL;
			$this->request = $request_data;
			if($this->prefered_response === 'rp') $this->prefered_response = Token::STATUS_COMPLETE;
		}

		/**
		 * @return Response
		 * @throws BadURL
		 * @throws Exceptions\InternalError
		 * @throws InvalidParameters
		 * @throws UnsupportedMediaType
		 */
		public function handle() : Response
		{
			switch($this->version)
			{
				case 5:
					return $this->handle_v5();
			}

			throw new BadURL('Bad version in URL');
		}

		/**
		 * @return Response
		 * @throws BadURL
		 * @throws Exceptions\InternalError
		 * @throws InvalidParameters
		 * @throws UnsupportedMediaType
		 */
		private function handle_v5() : Response
		{
			if($this->request->content_type !== 'application/json')
			{
				throw new UnsupportedMediaType('Only accept content-type: "application/json", you used ' . json_encode($this->request->content_type));
			}

			switch($this->prefered_response)
			{
				case 'alreadyInProgress':
				case 'invalidParameters':
					return Response::make_error($this->prefered_response, 'You asked for it',400);
				case 'unauthorized':
					return Response::make_error($this->prefered_response, 'You asked for it',401);
				case 'notFound':
					return Response::make_error($this->prefered_response, 'You asked for it',404);
				case 'requestTimeout':
					sleep(30);
					return Response::make_error($this->prefered_response, 'You asked for it',408);
				case 'unsupportedMediaType':
					return Response::make_error($this->prefered_response, 'You asked for it',415);
				case 'internalError':
					return Response::make_error($this->prefered_response, 'You asked for it',500);
				case 'Maintenance':
					return Response::make_error($this->prefered_response, 'You asked for it',503);
			}

			switch($this->api_path)
			{
				case '/auth':
					$data = $this->request->validate(
						[
							'endUserIp',
						],
						[
							'personalNumber' => '',
							'requirement' => [],
						]
					);

					if(strlen($data['personalNumber']) !== 12)
					{
						throw new InvalidParameters('personalNumber should be 12 chars long');
					}

					$data['method'] = 'auth';

					$token = new Token(NULL, $data);

					return new Response(
						[
							'orderRef' => $token->token,
							'autoStartToken' => $token->auto_token,
						]
					);

				case '/sign':
					$data = $this->request->validate(
						[
							'endUserIp',
							'userVisibleData',
						],
						[
							'personalNumber' => '',
							'requirement' => [],
							'userNonVisibleData' => NULL,
						]
					);

					$data['method'] = 'sign';

					$token = new Token(NULL, $data);

					return new Response(
						[
							'orderRef' => $token->token,
							'autoStartToken' => $token->auto_token,
						]
					);

				case '/collect':
					$data = $this->request->validate(['orderRef'], []);

					$token = Token::find($data['orderRef']);

					if(!$token)
					{
						throw new InvalidParameters('Token not found');
					}

					$this->pretend($token);

					$response_data = [
						'orderRef' => $token->token,
						'status' => $token->status,
					];

					if($token->status === Token::STATUS_COMPLETE)
					{
						$response_data['completionData'] = [
							'user' => $token->user,
							'device' => $token->device,
							'cert' => $token->cert,
							'signature' => $token->signature,
							'ocspResponse' => $token->ocsp,
						];
						return new Response($response_data);
					}

					$response_data['hintCode'] = $token->hint;
					if($token->status === 'pending')
					{
						return new Response($response_data);
					}

					$response_data['status'] = 'failed';
					return new Response($response_data);

				case '/cancel':
					$data = $this->request->validate(['orderRef'], []);

					$token = Token::find($data['orderRef']);

					if(!$token)
					{
						throw new InvalidParameters('Token not found');
					}

					$token->cancel();

					return new Response('{}');
			}
			throw new BadURL('Unknown api-path: ' . json_encode($this->api_path));
		}

		/**
		 * @param Token $token
		 *
		 * @throws Exceptions\InternalError
		 * @throws InvalidParameters
		 * @throws BadURL
		 */
		private function pretend(Token $token)
		{
			if($token->status !== Token::STATUS_PENDING)
			{
				return;
			}

			// Pending statuses
			if($this->prefered_response === Token::STATUS_COMPLETE)
			{
				$time_limit_s = time() - $this->prefered_response_time;
				if($token->created_at > 1000 * $time_limit_s)
				{
					return;
				}

				$token->status = Token::STATUS_COMPLETE;
			}
			else if(\in_array(
				$this->prefered_response,
				[
					'outstandingTransaction',
					'noClient',
					'started',
					'userSign',

				]
			))
			{
				$token->hint = $this->prefered_response;
			}
			else if(\in_array(
				$this->prefered_response,
				[
					'expiredTransaction',
					'certificateErr',
					'userCancel',
					'cancelled',
					'startFailed',
				]
			))
			{
				$time_limit_s = time() - $this->prefered_response_time;
				if($token->created_at > 1000 * $time_limit_s)
				{
					return;
				}

				$token->status = Token::STATUS_FAILED;
				$token->hint = $this->prefered_response;
			}
			else
			{
				throw new BadURL('Unknown prefered_response: ' . json_encode($this->prefered_response));
			}
			$token->validate();
			$token->mark_fetched();
			$token->save();
		}

		/**
		 * @throws BadURL
		 * @throws Exceptions\InternalError
		 * @throws InvalidParameters
		 * @throws UnsupportedMediaType
		 */
		public function send_response() : void
		{
			$this->handle()->send();
		}
	}
