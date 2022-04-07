<?php

include_once (dirname(__FILE__) . "/../Whatsapp.php");
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

	public function index_post()
	{
		if ($this->get_budget_api_key() == null) {
			$this->response_api(401, false, 'Unauthorized, Budget Api Key Not Found');
			return;
		}

		if ($this->invalid_api_key()) {
			$this->response_api(401, false, 'Unauthorized, Budget Api Key Invalid');
			return;
		}

		$this->load->model('PengajuanModel');
		$this->load->model('SelesaiModel');
		$this->load->model('BpuModel');
		$sourceAccountBank = $this->source_account_bank();
		$maxTransfer = $this->max_transfer();
		$metodePembayaran = $this->metode_pembayaran($this->requestInput->jumlah, $maxTransfer);
		$bank = $this->bank($this->requestInput->kode_bank);

		if ($bank == null) {
			$this->response_api(400, false, "Bank dengan swift kode " . $this->requestInput->kode_bank . " tidak ditemukan");
			return;
		}

		if ($this->requestInput->bank_account_name == "") {
			$this->response_api(400, false, "Bank account name is null");
			return;
		}

		$dataPengajuan = $this->PengajuanModel->get_pengajuan(["kodeproject" => $this->requestInput->kode_project]);
		if (! isset($dataPengajuan[0])) {
			$this->response_api(400, false, "Pengajuan tidak ditemukan");
			return;
		}

		$dataItem = $this->SelesaiModel->read_other_condition(["waktu" => $dataPengajuan[0]->waktu, "status" => $this->requestInput->status]);
		if ($dataItem == null) {
			$this->response_api(400, false, "Item dengan status " . $this->requestInput->status . " tidak ditemukan");
			return;
		}

		// BPU untuk honor dibuat 1 bpu multiple receiver
		$dateNow = date("Y-m-d");
		$term = $this->BpuModel->max_term(["waktu" => $dataPengajuan[0]->waktu, "no" => $dataItem->no]);
		$lastBpuToday = $this->get_last_bpu_by_day($dateNow);
		if ($lastBpuToday == null) {
			$term = $term->max_term + 1;
		} else {
			$term = $term->max_term;
		}

		$payloadBpu = $this->set_payload_bpu($dataItem, $term, $metodePembayaran);
		$this->insert_bpu($payloadBpu);

		$dataMriPal = [];
		if ($metodePembayaran == "MRI PAL") {
			$noIdBpu = $this->getLastDataNoidBpu();
			$dataMriPal = $this->push_to_mri_transfer($bank, $dataPengajuan[0], $noIdBpu, $sourceAccountBank);
		}

		$wa = new Whatsapp();
		$dataNotifikasi = [
			"penerima" => $payloadBpu["namapenerima"],
			"msisdn" => $payloadBpu["phone_number"],
			"jenis_pembayaran" => $payloadBpu['statusbpu'],
			"pemilik_rekening" => $payloadBpu["bank_account_name"],
			"nomor_rekening" => $payloadBpu["norek"],
			"bank" => $bank->namabank,
			"jumlah" => $this->requestInput->jumlah,
			"jadwal_transfer" => $this->requestInput->tanggal_bayar,
			"project" => $this->requestInput->nama_project,
			"term" => $payloadBpu["term"],
			"metode_pembayaran" => $payloadBpu["metode_pembayaran"],
			"keterangan_pembayaran" => $payloadBpu["ket_pembayaran"],
			"biaya_transfer" => 0,
		];

		if ($metodePembayaran == "MRI PAL") {
			$dataNotifikasi["biaya_transfer"] = $this->setBiayaTransfer($this->requestInput->kode_bank);
		}

		$messageTransfer = $wa->message_transfer($dataNotifikasi);
		$wa->send_notification($dataNotifikasi['msisdn'], $messageTransfer);

		$this->response_api(200, true, "Success create BPU", ["data_bpu" => $payloadBpu, "data_transfer" => $dataMriPal]);
	}

	private function source_account_bank()
	{
		$dbDevelop = $this->load->database('db_develop', TRUE);
		$queryDbKas = $dbDevelop->query("SELECT rekening FROM kas WHERE label_kas = 'Kas Project'")->row_array();

		return $queryDbKas['rekening'] == null ? 0 : $queryDbKas['rekening'];
	}

	private function max_transfer()
	{
		$dbMri = $this->load->database('db_mritransfer', TRUE);
		$jenisPembayaran = $dbMri->query("SELECT max_transfer FROM jenis_pembayaran WHERE jenispembayaran = 'Honor SHP'")->row_array();
		return $jenisPembayaran['max_transfer'] == null ? 0 : $jenisPembayaran['max_transfer'];
	}

	private function metode_pembayaran($jumlah, $maxTransfer)
	{
		if ($jumlah > $maxTransfer || $jumlah < 0) return "MRI KAS";
		return "MRI PAL";
	}

	private function set_payload_bpu($dataItem, $term, $metodePembayaran)
	{
		return [
			"no" => $dataItem->no,
			"statusbpu" => $this->requestInput->status,
			"jumlah" => $this->requestInput->jumlah,
			"tglcair" => '0000-00-00',
			"namabank" => $this->requestInput->kode_bank,
			"norek" => $this->requestInput->nomor_rekening,
			"namapenerima" => $this->requestInput->nama_penerima,
			"emailpenerima" => $this->requestInput->email_penerima,
			"pengaju" => $this->requestInput->pengaju,
			"divisi" => $this->requestInput->divisi,
			"waktu" => $dataItem->waktu,
			"status" => 'Belum Di Bayar',
			"persetujuan" => 'Disetujui (Direksi)',
			"jumlahbayar" => $this->requestInput->jumlah,
			"novoucher" => $this->requestInput->kode_voucher,
			"tanggalbayar" => $this->requestInput->tanggal_bayar,
			"pembayar" => $this->requestInput->pembayar,
			"divpemb" => $this->requestInput->divisi_pembayar,
			"term" => $term,
			"metode_pembayaran" => $metodePembayaran,
			"bank_account_name" => $this->requestInput->bank_account_name,
			"ket_pembayaran" => $this->requestInput->ket_pembayaran,
			"id_rtp_application" => $this->requestInput->num_honor,
			"phone_number" => $this->requestInput->nomor_hp,
			"api_key" => $this->get_budget_api_key(),
			"created_date" => date("Y-m-d"),
		];
	}

	private function insert_bpu($data)
	{
		$this->db->insert('bpu', $data);
	}

	private function get_last_bpu_by_day($date)
	{
		return $this->db->get_where("bpu", ["created_date" => $date])->row();
	}

	private function getLastDataNoidBpu()
	{
		$data = $this->db->select('noid')->order_by('noid',"desc")->limit(1)->get('bpu')->row();
		return $data->noid;
	}

	private function push_to_mri_transfer($bank, $pengajuan, $noIdBpu, $rekeningSumber)
	{
		$trasnferRequestId = $this->lastRequestTransferId();
		$biayaTrf = $this->setBiayaTransfer($this->requestInput->kode_bank);
		$timeNow = date('Y-m-d H:i:s');

		$norek = $this->correct_bank_account_number($this->requestInput->nomor_rekening);

		$dataInsert = [
			"transfer_req_id" => $trasnferRequestId,
			"transfer_type" => 3,
			"jenis_pembayaran_id" => 9,
			"keterangan" => $this->requestInput->status,
			"norek" => $norek,
			"pemilik_rekening" => $this->requestInput->bank_account_name,
			"email_pemilik_rekening" => $this->requestInput->email_penerima,
			"bank" => $bank->namabank,
			"kode_bank" => $this->requestInput->kode_bank,
			"berita_transfer" => $this->requestInput->ket_pembayaran,
			"jumlah" => $this->requestInput->jumlah - $biayaTrf,
			"ket_transfer" => "Antri",
			"nm_pembuat" => $this->requestInput->pembayar,
			"nm_otorisasi" => $this->requestInput->pembayar,
			"nm_manual" => "",
			"jenis_project" => $pengajuan->jenis,
			"nm_project" => $this->requestInput->nama_project,
			"noid_bpu" => $noIdBpu,
			"rekening_sumber" => $rekeningSumber,
			"waktu_request" => $timeNow,
			"jadwal_transfer" => $this->requestInput->tanggal_bayar,
			"biaya_trf" => $biayaTrf,
			"terotorisasi" => 2,
			"hasil_transfer" => 1,
			"nm_validasi" => "Sistem",
			"url_callback" => base_url("api/transfer/callback")
		];
		$dbBridge = $this->load->database('db_bridge', TRUE);
		$err = $dbBridge->insert('data_transfer', $dataInsert);

		return $dataInsert;
	}

	private function lastRequestTransferId()
	{
		$date = date('my');
		$dbBridge = $this->load->database('db_bridge', TRUE);
		$data = $dbBridge->select('transfer_req_id')->where('transfer_req_id LIKE', $date."%")->order_by('transfer_req_id',"desc")->limit(1)->get('data_transfer')->row();
		$lastId = (int)substr($data->transfer_req_id, -4);

		$formatId = $date . sprintf('%04d', $lastId + 1);
		return $formatId;
	}

	private function setBiayaTransfer($kodeBank = 'CENAIDJA')
	{
		$biayaTransfer = 0;
		if ($kodeBank != "CENAIDJA") {
			$biayaTransfer = 2900;
		}
		return $biayaTransfer;
	}

	private function correct_bank_account_number($string)
	{
		$param = [".", "-"];

		$newNorek = $string;
		foreach ($param as $key => $value) {
			$split = explode($value, $newNorek);
			$newNorek = implode("", $split);
		}

		return $newNorek;
	}

	private function bank($kodeBank)
	{
		return $this->db->get_where("bank", ["kodebank" => $kodeBank])->row();
	}

}
