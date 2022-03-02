<?php

class CommisionVoucherModel extends CI_Model
{
	protected $table = 'comm_voucher';
	protected $dbDigitalisasi = null;

	public function __construct()
	{
		$this->dbDigitalisasi = $this->load->database('digitalisasimarketing', TRUE);
	}

	public function commisionVoucherNotOnBudget()
	{
		return $this->dbDigitalisasi->select('nama_project_internal, id_comm_voucher, research_executive')->get_where($this->table, array('on_budget' => 0))->result();
	}

	public function dataUser($id)
	{
		return $this->dbDigitalisasi->get_where('data_user', array('id_user' => $id))->row();
	}

	public function projectSindikasi()
	{
		return $this->dbDigitalisasi->get('data_sindikasi')->result();
	}

	public function dataMethodology($id)
	{
		return $this->dbDigitalisasi->get_where('data_methodology', array('id_methodology' => $id))->row();
	}

}
