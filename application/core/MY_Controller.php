<?php
require_once APPPATH . 'libraries/REST_Controller.php';

class MY_Controller extends REST_Controller
{
	public $requestInput;
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->set_request();
	}

	public function response_api($code = 200, $status = true, $message = "", $data = NULL)
	{
		$response = array(
			'status' => $status,
			'message' => $message,
			'data' => $data
		);

		$this->response($response, $code);
	}

	public function print_pretty($data)
	{
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}

	public function set_request()
	{
		$this->requestInput = (object)json_decode(file_get_contents('php://input'), true);
	}
}
