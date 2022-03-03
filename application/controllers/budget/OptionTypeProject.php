<?php

class OptionTypeProject extends MY_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
	}

	public function index_get()
	{
		$divisi = strtolower($this->auth_user()->divisi);
		$level = strtolower($this->auth_user()->level);

		if (!in_array($divisi, array('direksi', 'finance')) && !in_array($level, array('manager', 'senior manager'))) {
			$this->response_api(400, false, "You don't have permission to access this data", null);
			exit();
		}

		$dataOptions = $this->option_types()[$divisi];
		$this->response_api(200, true, 'Success', $dataOptions);
	}

	public function option_types()
	{
		return [
			"direksi" => [
				[
					"label" => "B1",
					"value" => "B1"
				],
				[
					"label" => "B2",
					"value" => "B2"
				],
				[
					"label" => "Rutin",
					"value" => "Rutin"
				],
				[
					"label" => "Non Rutin",
					"value" => "Non Rutin"
				],
				[
					"label" => "Lainnya",
					"value" => "Lainnya"
				]
			],
			"finance" => [
				[
					"label" => "Rutin",
					"value" => "Rutin"
				],
				[
					"label" => "Non Rutin",
					"value" => "Non Rutin"
				],
			],
			"b1" => [
				[
					"label" => "B1",
					"value" => "B1"
				],
			],
			"b2" => [
				[
					"label" => "B2",
					"value" => "B2"
				],
			]
		];
	}
}
