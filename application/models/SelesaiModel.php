<?php

class SelesaiModel extends CI_Model
{
	protected $table = 'selesai';

	public function create($data)
	{
		$this->db->insert($this->table, $data);
		return $this->db->insert_id();
	}

	public function read($id)
	{
		return $this->db->get_where($this->table, array('id' => $id))->row();
	}

	public function item_by_waktu($waktu)
	{
		return $this->db->get_where($this->table, array('waktu' => $waktu))->result();
	}

}
