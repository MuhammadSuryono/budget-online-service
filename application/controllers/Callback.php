<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once (dirname(__FILE__) . "/Whatsapp.php");

class Callback extends MY_Controller
{
	private $dataInput = [];
	private $dbMri;
	private $dataTransfer;
	private $dataBpu;
	public function __construct()
	{
		parent::__construct();

		$this->set_data_input_json();
		$this->dbMri = $this->load->database('db_bridge', TRUE);
	}

	public function index_post()
	{
		$isSuccessTransfer = $this->is_success_process_transfer();
		if ($isSuccessTransfer) {
			$this->get_data_transfer();
			$this->get_data_bpu($this->dataTransfer->noid_bpu);
			$this->update_bpu();
			$this->send_callback();
//			$this->send_notification_whatsapp();

			$this->response_api(200, true, "Success update data BPU");
		} else {
			$this->response_api(400, false, "Gagal update data BPU");
		}
	}

	private function update_bpu()
	{
		$value = [
			"tglcair" => $this->dataTransfer->jadwal_transfer,
			"status" => 'Telah Di Bayar',
			"jumlahbayar" => $this->dataTransfer->jumlah,
			"novoucher" => "",
			"tanggalbayar" => $this->dataTransfer->jadwal_transfer,
		];

		$this->db->where("noid", $this->dataTransfer->noid_bpu)->update("bpu", $value);
	}

	private function set_data_input_json()
	{
		$input_data = json_decode($this->input->raw_input_stream, true);
		$this->dataInput = $input_data;
		$this->dataInput["response"] = json_decode($this->dataInput["response"]);
	}

	private function is_success_process_transfer()
	{
		log_message("info", json_encode($this->dataInput));
		return $this->dataInput['response']->TransactionID == $this->dataInput['transfer_req_id'];
	}

	private function get_data_transfer()
	{
		$this->dataTransfer = $this->dbMri->where('transfer_req_id', $this->dataInput['transfer_req_id'])->get('data_transfer')->row();
	}

	private function get_data_bpu($noidBpu)
	{
		$this->dataBpu = $this->db->where('noid', $noidBpu)->get('bpu')->row();
	}

	private function send_notification_whatsapp()
	{
		$wa = new Whatsapp();
		$dataNotifikasi = [
			"penerima" => $this->dataBpu->namapenerima,
			"msisdn" => $this->dataBpu->phone_number,
			"jenis_pembayaran" => $this->dataBpu->statusbpu,
			"pemilik_rekening" => $this->dataBpu->bank_account_name,
			"nomor_rekening" => $this->dataTransfer->norek,
			"bank" => $this->dataTransfer->bank,
			"jumlah" => $this->dataTransfer->jumlah,
			"jadwal_transfer" => $this->dataTransfer->jadwal_transfer,
			"project" => $this->dataTransfer->nm_project,
			"biaya_transfer" => $this->dataTransfer->biaya_trf,
			"term" => $this->dataBpu->term
		];

		$messageTransfer = $wa->message_success_transfer($dataNotifikasi);
		$wa->send_notification($dataNotifikasi['msisdn'], $messageTransfer);
	}

	private function generate_voucher_number($kodeBank = 'BRMIIDJA')
	{
		$tahun = date('y');
		$bulan = date('m');
		$lastVoucherId = $this->lastVoucherId($kodeBank);
		// Mandiri Format KKP12-210001
		// BCA Format KKP12-BCA-210001
		if ($kodeBank != "BRMIIDJA") {
			return "KKP" . $bulan . "-" . $this->name_short_bank($kodeBank) . "-" . $tahun . $lastVoucherId;
		}
		return "KKP" . $bulan . "-" . $tahun . $lastVoucherId;
	}

	private function lastVoucherId($kodeBank)
	{
		$dbBridge = $this->load->database('db_bridge', TRUE);
		$data = $dbBridge->select('transfer_req_id')->where('kode_bank', $kodeBank)->order_by('transfer_id',"desc")->limit(1)->get('data_transfer')->row();
		$lastId = (int)substr($data->transfer_req_id, -4);
		return sprintf('%04d', $lastId + 1);
	}

	private function name_short_bank($kodeBank)
	{
		$bank = [
			"CENAIDJA" => "BCA",
			"BNINIDJA" => "BNI",
			"BRINIDJA" => "BRI",
			"BTANIDJA" => "BTN",
		];

		return $bank[$kodeBank];
	}

	private function send_callback()
	{
		$apiKey = $this->dataBpu->api_key;
		$application = $this->db->get_where('application', ['api_key' => $apiKey])->row();
		$url = $application->url_callback;
		$this->dataBpu->tanggalbayar = $this->dataTransfer->jadwal_transfer;

		$req = $this->HTTPPost($url, ["is_success" => true, "message" => "", "data" => $this->dataBpu], "json");
		print_r($req);
	}
}
