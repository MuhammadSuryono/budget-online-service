<?php

class ItemRequestModel extends CI_Model
{
	protected $table = 'selesai_request';
	public function create($data)
	{
		return $this->db->insert($this->table, $data);
	}

	public function readItem($id)
	{
		return $this->db->get_where($this->table, array('id' => $id))->result();
	}
}
