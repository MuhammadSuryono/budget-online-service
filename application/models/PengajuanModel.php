<?php

class PengajuanModel extends CI_Model
{
	protected $table = "pengajuan";

	public function create($data)
	{
		$this->db->insert($this->table, $data);
		return $this->db->insert_id();
	}

	public function read($id)
	{
		return $this->db->get_where($this->table, ["noid" => $id])->row();
	}

	public function last_pengajuan_type($type)
	{
		return $this->db->get_where($this->table, ["type" => $type])->order_by('noid', 'desc')->row();
	}
}
