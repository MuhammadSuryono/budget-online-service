<?php

class RoleBpuModel extends CI_Model
{
	protected $table = 'tb_role_bpu';
	public function role_bpu_user($feature,$userId)
	{
		return $this->db->get_where($this->table, [$this->column_feature($feature) => $userId])->result();
	}

	private function column_feature($feature)
	{
		$data = [
			"createBpu" => "create_bpu",
			"validateBpu" => "validate_bpu",
			"approverBpu" => "approver_bpu",
			"knowledgeBpu" => "knowledge_bpu",
		];

		return $data[$feature];
	}
}
