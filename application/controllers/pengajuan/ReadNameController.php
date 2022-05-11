<?php

class ReadNameController extends MY_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
	}

	public function index_get()
	{
		$name = $this->request()->get('name');
		if ($name == "") {
			$this->response_api(self::HTTP_BAD_REQUEST,false,"Parameter name undefined");
		} else {
			$explodeName = explode("|", $name);
			$data = $this->db->where_in('nama', $explodeName)->get('pengajuan')->row();
			if ($data) {
				$items = $this->db->get_where('selesai', ['waktu' => $data->waktu])->result();
				$data->items = $items;
				$this->response_api(self::HTTP_OK,true, "Success retrieve data", $data);
			} else {
				$this->response_api(self::HTTP_NOT_FOUND,false, "Project undefined in Budget. Please check again Your Budget");
			}
		}
	}
}
