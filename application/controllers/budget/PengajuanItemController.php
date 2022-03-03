<?php

class PengajuanItemController extends MY_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
	}

	public function index_get($idPengajuan)
	{
		$this->load->model('PengajuanModel');
		$this->load->model('SelesaiModel');
		$this->load->model('BpuModel');

		$pengajuan = $this->PengajuanModel->read($idPengajuan);
		$items = $this->SelesaiModel->item_by_waktu($pengajuan->waktu);

		foreach ($items as $item) {
			$sumJumlahBpu = $this->BpuModel->sum_bpu('jumlah', ['waktu' => $item->waktu, 'no' => $item->no, 'status' => 'Telah Di Bayar']);
			$totalBpu = $this->BpuModel->list_bpu(['waktu' => $item->waktu, 'no' => $item->no]);
			$item->total_pembayaran = (int)$sumJumlahBpu->jumlah;
			$item->total_bpu = count($totalBpu);
		}

		$this->response_api(200, true, "Success retrieve items", $items);
	}
}
