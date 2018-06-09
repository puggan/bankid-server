<?php

	namespace Puggan\BankIDServer;

	require_once __DIR__ . '/vendor/autoload.php';

	use Puggan\BankIDServer\Exceptions\Exception;

	$request_data = new Data\Request(
		[
			'ip' => '127.0.0.1',
			'port' => 1234,
			'ua' => 'PHP CLI',
			'url' => 'https://test/v5/auth',
			'method' => 'POST',
			'content_type' => 'application/json',
			'data' => json_encode(
				[
					'personalNumber' => '201010101010',
					'endUserIp' => '194.168.2.25',
					'userVisibleData' => base64_encode('This is a sample text to be signed'),
				]
			)
		]
	);

	try
	{
		$r = $request_data->handle();
	}
	catch(Exception $e)
	{
		$r = $e->make_response();
	}

	print_r($r);
	die(PHP_EOL);
