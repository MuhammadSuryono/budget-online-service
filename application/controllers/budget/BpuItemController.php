<?php

class BpuItemController extends MY_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
	}

	public function index_get($idItem)
	{
		$this->load->model('SelesaiModel');
		$this->load->model('BpuModel');

		$item = $this->SelesaiModel->read($idItem);
		$bpu = $this->BpuModel->list_bpu(['waktu' => $item->waktu, 'no' => $item->no]);

		$this->response_api(200, true, 'Data BPU', $bpu);
	}
}
