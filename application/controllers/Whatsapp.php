<?php

class Whatsapp
{
	private $host = "http://app.mkp-operation.com:8080/api/v1/whatsapp";

	public function __construct()
	{
	}

	public function send_message_transfer(array $data)
	{
		foreach ($data as $val) {
			$url = $this->host."/send-notification-message";
			if ($val['msisdn'] != "" && $val['penerima'] != '') {
				$message = $this->message_transfer($val);

				$explode = explode("-", $val['msisdn']);
				$val['msisdn'] = implode("", $explode);

				$body = ["msisdn" => $val['msisdn'], "message" => $message];

				$this->HTTPPost($url, $body);
			}
		}
	}

	/**
	 * @description Make HTTP-POST call
	 * @param       $url
	 * @param array $params
	 * @param string $type
	 * @return      HTTP-Response body or an empty string if the request fails or is empty
	 */
	private static function HTTPPost($url, array $params, $type = "form") {
		$query = http_build_query($params);
		if ($type == "json") {
			$query = json_encode($params);
		}

		$ch    = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	public function message_transfer(array $data)
	{
		$jadwal = explode(" ", $data['jadwal_transfer']);
		$msg = "Kepada " . $data['penerima'] . ", 

Berikut informasi status pembayaran yang akan Anda terima:

Nama Pembayaran : " . $data['jenis_pembayaran'] . "
Term Pembayaran : ". $data['term'] . "
Nama Project : ". $data['project'] . "
No. Rekening : " . $data['nomor_rekening'] . "
Bank : " . $data['bank'] . "
Nama Penerima : " . $data['pemilik_rekening'] . "
Jumlah Pembayaran : Rp. " . number_format($data['jumlah'] - $data['biaya_transfer'], 0, ',', '.') . "
Biaya Admin : Rp. " . number_format($data['biaya_transfer'], 0, '', '.') . "
Jumlah Dibayarkan : Rp. " . number_format($data['jumlah'], 0, '', '.') . "
Status : Dijadwalkan Pada,  Tanggal : " . $data['jadwal_transfer'] . "
Keterangan Pembayaran : " . $data['keterangan_pembayaran'] . "

Jika ada pertanyaan lebih lanjut, silahkan email Divisi Finance ke finance@mri-research-ind.com.
Hormat kami,
Finance Marketing Research Indonesia
";
		return $msg;
	}

	public function message_success_transfer(array $data)
	{
		$jadwal = explode(" ", $data['jadwal_transfer']);
		$msg = "Kepada " . $data['penerima'] . ", 

Berikut informasi status pembayaran yang Anda terima:

Nama Pembayaran : " . $data['jenis_pembayaran'] . "
Term Pembayaran : ". $data['term'] . "
Nama Project : ". $data['project'] . "
No. Rekening : " . $data['nomor_rekening'] . "
Bank : " . $data['bank'] . "
Nama Penerima : " . $data['pemilik_rekening'] . "
Jumlah : Rp. " . number_format($data['jumlah'] - $data['biaya_transfer'], 0, '', '.') . "
Biaya Transfer : Rp. " . number_format($data['biaya_transfer'], 0, '', '.') . "
Jumlah Dibayarkan : Rp. " . number_format($data['jumlah'], 0, '', '.') . "
Status : Dibayarkan Pada,  Tanggal : " . $jadwal[0] . "
Keterangan Pembayaran : " . $data['keterangan_pembayaran'] . "

Jika ada pertanyaan lebih lanjut, silahkan email Divisi Finance ke finance@mri-research-ind.com.
Hormat kami,
Finance Marketing Research Indonesia
";
		return $msg;
	}

	public function send_notification($msisdn, $message)
	{
		$url = $this->host."/send-notification-message";
		$body = ["msisdn" => $msisdn, "message" => $message];
		$this->HTTPPost($url, $body);
	}
}
