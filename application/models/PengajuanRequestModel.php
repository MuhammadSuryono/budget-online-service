<?php

class PengajuanRequestModel extends CI_Model
{
	protected $table = "pengajuan_request";
	public function create($data)
	{
		$this->db->insert($this->table, $data);
		$id = $this->db->insert_id();
		return ["id" => $id, "data" => $this->readPengajuanRequest($id)];
	}

	public function readPengajuanRequest($id)
	{
		return $this->db->get_where($this->table, ["id" => $id])->row();
	}
}
