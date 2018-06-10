<?php

	namespace Puggan\BankIDServer;

	require_once __DIR__ . '/vendor/autoload.php';

	use Puggan\BankIDServer\Data\Response;
	use Puggan\BankIDServer\Exceptions\Exception;

	require_once __DIR__ . '/src/Data/Request.php';
	require_once __DIR__ . '/src/Data/Response.php';
	require_once __DIR__ . '/src/Server.php';

	if($_SERVER['REQUEST_URI'] === '/')
	{
		$index_file_path = __DIR__ . '/index.html';
		if(is_file($index_file_path))
		{
			readfile($index_file_path);
			die();
		}
	}

	if(isset($_SERVER['SSL_CLIENT_CERT']))
	{
		$expected = file_get_contents(__DIR__ . '/cert/test.crt.pem');
		if($_SERVER['SSL_CLIENT_CERT'] !== $expected)
		{
			Response::make_error('Invalid client certificate', 500, 'bad certificate');
		}
	}

	$request_data = new Data\Request(
		[
			'ip' => $_SERVER['REMOTE_ADDR'],
			'port' => $_SERVER['REMOTE_PORT'],
			'ua' => $_SERVER['HTTP_USER_AGENT'],
			'url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			'method' => $_SERVER['REQUEST_METHOD'],
			'content_type' => $_SERVER["CONTENT_TYPE"],
			'data' => file_get_contents('php://input'),
		]
	);

	try
	{
		(new Server($request_data))->send_response();
	}
	catch(Exception $e)
	{
		$e->send_response();
	}
