<?php

class BpuModel extends CI_Model
{
	protected $table = 'bpu';

	public function sum_bpu($column,$condition)
	{
		$this->db->select_sum($column);
		$this->db->where($condition);
		$query = $this->db->get($this->table);
		return $query->row();
	}

	public function list_bpu($condition)
	{
		return $this->db->get_where($this->table,$condition)->result();
	}

}
