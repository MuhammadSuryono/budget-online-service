<?php

class UserModel extends CI_Model
{
	protected $table = 'tb_user';
	public function check_auth($username, $password)
	{
		return $this->db->get_where($this->table, array('id_user' => $username, 'password' => $password))->row_array();
	}
}
