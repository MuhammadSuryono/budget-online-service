<?php

class ItemController extends MY_Controller
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
		$sumJumlahBpu = $this->BpuModel->sum_bpu('jumlah', ['waktu' => $item->waktu, 'no' => $item->no, 'status' => 'Telah Di Bayar']);

		$item->total_pembayaran = (int)$sumJumlahBpu->jumlah;
		$item->sisa_pembayaran = (int)$item->total - $item->total_pembayaran;
		$item->persentase_pembayaran = $item->total == 0 ? 0 : ceil(($item->total_pembayaran / $item->total) * 100);
		$this->response_api(200, true, "Success retrieve data", $item);
	}
}
