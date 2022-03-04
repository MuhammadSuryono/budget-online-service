<?php

class PengajuanController extends MY_Controller
{
	protected $user = null;
	protected $idPengajuanRequest = 0;
	protected $payloadPengajuan = null;
	protected $functions = [];

	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->register_methode();
	}

	public function index_get($noid)
	{
		$this->load->model('PengajuanModel');
		$this->load->model('BpuModel');

		$data = $this->PengajuanModel->read($noid);
		$totalNominalBpu = $this->BpuModel->sum_bpu('jumlah', ["waktu" => $data->waktu]);
		$totalRtp = $this->BpuModel->sum_bpu('jumlah', ["waktu" => $data->waktu, "persetujuan" => "Disetujui (Direksi)", "status" => "Belum Di Bayar"]);
		$totalUangKembali = $this->BpuModel->sum_bpu('uangkembali', ["waktu" => $data->waktu]);

		$data->total_nominal_bpu = (int)$totalNominalBpu->jumlah;
		$data->total_rtp_bpu = (int)$totalRtp->jumlah;
		$data->total_uang_kembali = (int)$totalUangKembali->uangkembali;

		$data->total_biaya_uang_muka = $data->total_nominal_bpu - $data->total_rtp_bpu;
		$data->sisa_budget = (int)$data->totalbudget - $data->total_biaya_uang_muka;
		$data->total_pembayaran = (int)$data->totalbudget - $data->sisa_budget;
		$data->persentase_pembayaran = ceil(($data->total_pembayaran / $data->totalbudget) * 100);

		$this->response_api(200, true, "Success retrieve data", $data);
	}

	public function index_post()
	{
		$this->validate([
			'jenis' => 'required',
			'project' => 'trim|required|integer',
			'nama' => 'required',
			'idUser' => 'trim|required|integer',
			'table' => 'required',
			'tahun' => 'trim|required|integer|regex_match[/^(201)\d{1}$/]',
			'pic' => 'required',
		]);

		$this->set_user_creator_project();
		$this->input_pengajuan();
		call_user_func_array([$this, $this->functions[$this->requestInput->jenis]], array());

	}

	private function set_user_creator_project()
	{
		$this->load->model('UserModel');
		$this->user = $this->UserModel->read_user($this->requestInput->idUser);
	}

	private function input_pengajuan()
	{
		$this->load->model('PengajuanRequestModel');
		$body = [
			"jenis" => $this->requestInput->jenis,
			"nama" => $this->requestInput->nama,
			'pembuat' => $this->auth_user()->nama_user,
			"pengaju" => $this->user->nama_user,
			"divisi" => $this->auth_user()->divisi,
			"tahun" => $this->requestInput->tahun,
			"totalbudget" => 0,
			"status_request" => "Belum Di Ajukan",
			"kode_project" => "",
			"on_revision_status" => 1
		];

		$insert = $this->PengajuanRequestModel->create($body);
		$this->idPengajuanRequest = $insert['id'];
		$this->payloadPengajuan = $insert['data'];
	}

	private function insert_item_b1()
	{
		$this->load->model('ItemRequestModel');
		foreach ($this->arrNamaB1 as $key => $value) {
			$body = [
				"urutan" => $key + 1,
				"id_pengajuan_request" => $this->idPengajuanRequest,
				"rincian" => $this->arrNamaB1[$key],
				"kota" => $this->arrKotaB1[$key],
				"status" => $this->arrStatusB1[$key],
				"penerima" => $this->arrPenerimaB1[$key],
				"harga" => 0,
				"quantity" => 0,
				"total" => 0,
				"pengaju" => $this->auth_user()->nama_user,
				"divisi" => $this->auth_user()->divisi,
				"waktu" => $this->payloadPengajuan->waktu
			];

			$this->ItemRequestModel->create($body);
		}
	}

	private function insert_item_b2()
	{
		$this->load->model('ItemRequestModel');
		foreach ($this->arrNamaB2 as $key => $value) {
			$body = [
				"urutan" => $key + 1,
				"id_pengajuan_request" => $this->idPengajuanRequest,
				"rincian" => $this->arrNamaB2[$key],
				"kota" => $this->arrKotaB2[$key],
				"status" => $this->arrStatusB2[$key],
				"penerima" => $this->arrPenerimaB2[$key],
				"harga" => 0,
				"quantity" => 0,
				"total" => 0,
				"pengaju" => $this->auth_user()->nama_user,
				"divisi" => $this->auth_user()->divisi,
				"waktu" => $this->payloadPengajuan->waktu
			];

			$this->ItemRequestModel->create($body);
		}
	}


	private function insert_item_rutin()
	{
		$this->load->model('ItemRequestModel');
		$this->load->model('PengajuanModel');
		$this->load->model('SelesaiModel');

		$lastPengajuanRutin = $this->PengajuanModel->last_pengajuan_type('Rutin');
		$dataItems = $this->SelesaiModel->item_by_waktu($lastPengajuanRutin->waktu);

		foreach ($dataItems as $key => $value) {
			$body = [
				"urutan" => $value->no,
				"id_pengajuan_request" => $this->idPengajuanRequest,
				"rincian" => $value->rincian,
				"kota" => $value->kota,
				"status" => $value->status,
				"penerima" => $value->penerima,
				"harga" => $value->harga,
				"quantity" => $value->quantity,
				"total" => $value->total,
				"pengaju" => $this->auth_user()->nama_user,
				"divisi" => $this->auth_user()->divisi,
				"waktu" => $this->payloadPengajuan->waktu
			];

			$this->ItemRequestModel->create($body);
		}
	}

	private function register_methode()
	{
		$this->functions = [
			'B1' => 'insert_item_b1',
			'B2' => 'insert_item_b2',
			'Rutin' => 'insert_item_rutin',
			'Non Rutin' => '',
			'data_sindikasi' => 'update_data_sindikasi'
		];
	}

	private function update_data_sindikasi()
	{
		$namaProjectSplit = explode("-", $this->requestInput->nama);
		$namaProject = trim($namaProjectSplit[0]);
		$idMethodology = trim($namaProjectSplit[count($namaProjectSplit) - 1]);
	}
}
