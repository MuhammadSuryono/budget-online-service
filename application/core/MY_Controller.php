<?php
require_once APPPATH . 'libraries/REST_Controller.php';

class MY_Controller extends REST_Controller
{
	public $requestInput;

	public $arrNamaB1 = ['Honor Jakarta', 'Honor Luar Kota', 'STKB Transaksi Jakarta', 'STKB Transaksi Luar Kota', 'STKB OPS', 'Honor Area Head Jakarta', 'Honor Area Head Luar Kota'];
	public $arrKotaB1 = ['Jabodetabek', 'Luar kota', 'Jabodetabek', 'Luar Kota', 'Jabodetabek dan Luar Kota', 'Jabodetabek', 'Luar Kota'];
	public $arrStatusB1 = ['Honor Jakarta', 'Honor Luar Kota', 'STKB TRK Jakarta', 'STKB TRK Luar Kota', 'STKB OPS', 'Honor Area Head', 'Honor Area Head'];
	public $arrPenerimaB1 = ['Shopper/PWT', 'Shopper/PWT', 'TLF', 'TLF', 'TLF', 'Area Head', 'Area Head'];

	public $arrNamaB2 = ['Respondent Gift', 'Honor Interviewer'];
	public $arrKotaB2 = ['Semua Kota', 'Semua Kota'];
	public $arrStatusB2 = ['UM', 'Honor Eksternal'];
	public $arrPenerimaB2 = ['Responden', 'Interviewer'];

	protected $exceptUri = ['auth/login'];
	protected $token;

	protected $dataToken;

	public function __construct($config = 'rest')
	{
		parent::__construct($config);

		if (!in_array(uri_string(), $this->exceptUri)) {
			$this->middleware();
		}
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

	public function validate(array $rules)
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$this->form_validation->set_data($request);

		foreach ($rules as $key => $value) {
			$this->form_validation->set_rules($key, $key, $value);
		}

		if ($this->form_validation->run() == FALSE) {
			$this->response_api(400, false, $this->form_validation->error_array());
		}
	}

	public function generate_token($data)
	{
		return base64_encode(json_encode($data));
	}

	public function decode_token($token)
	{
		return json_decode(base64_decode($token));
	}

	public function middleware()
	{
		$this->auth_check();
		$this->dataToken = $this->decode_token($this->token);
		$user = $this->db->get_where('tb_user', ['id_user' => $this->dataToken->id_user])->row();

		if ($user == null) {
			$this->response_api(400, false, 'Token tidak valid');
			exit();
		}
	}

	public function auth_check()
	{
		$authorization = $this->input->get_request_header('Authorization');
		if ($authorization == null)
		{
			$this->response_api(400, false, 'Token tidak valid');
			exit();
		}

		$formatBearer = explode(' ', $authorization);
		if ($formatBearer[0] != 'Bearer')
		{
			$this->response_api(401, false, 'Unauthorized');
			exit();
		}

		$this->token = $formatBearer[1];
	}

	public function auth_user()
	{
		return $this->dataToken;
	}


}
