<?php

class ListController extends MY_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
	}

	public function index_get()
	{
		$this->load->model('PengajuanModel');
		$datas = $this->get_data();
		$this->response_api(200, true, "Success retrieve data", $datas);
	}

	private function get_data()
	{
		$this->load->model('BpuModel');

		$type = $this->input->get('type');
		$year = $this->input->get('year');

		$conditions = [
			"jenis" => $type,
			"tahun" => $year,
			"status !=" => "Belum Di Ajukan"
		];

		$dataPengajuan = $this->PengajuanModel->get_pengajuan($conditions);

		foreach ($dataPengajuan as $key => $value) {
			$totalNominalBpu = $this->BpuModel->sum_bpu('jumlah', ["waktu" => $value->waktu]);
			$totalRtp = $this->BpuModel->sum_bpu('jumlah', ["waktu" => $value->waktu, "persetujuan" => "Disetujui (Direksi)", "status" => "Belum Di Bayar"]);
			$totalUangKembali = $this->BpuModel->sum_bpu('uangkembali', ["waktu" => $value->waktu]);

			$value->total_nominal_bpu = (int)$totalNominalBpu->jumlah;
			$value->total_rtp_bpu = (int)$totalRtp->jumlah;
			$value->total_uang_kembali = (int)$totalUangKembali->uangkembali;

			$value->total_biaya_uang_muka = $value->total_nominal_bpu - $value->total_rtp_bpu;
			$value->sisa_budget = (int)$value->totalbudget - $value->total_biaya_uang_muka;
		}

		return $dataPengajuan;
	}
}
