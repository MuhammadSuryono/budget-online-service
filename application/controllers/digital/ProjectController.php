<?php

class ProjectController extends MY_Controller
{
	protected $projects = [];
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->load->model('CommisionVoucherModel');
	}

	public function index_get($typeProject)
	{
		$this->get_project_voucher($typeProject);
		$this->get_project_sindikasi($typeProject);
		$this->response_api(self::HTTP_OK, true, "Success", $this->projects);
	}

	private function get_project_voucher($typeProject)
	{
		$project = $this->CommisionVoucherModel->commisionVoucherNotOnBudget();
		$this->check_user_research_executive($project, $typeProject, 'research_executive');
	}

	private function get_project_sindikasi($typeProject)
	{
		$project = $this->CommisionVoucherModel->projectSindikasi();

		$dataProjectWithMethodology = [];
		foreach ($project as $value) {
			$onBudget = $value->on_budget == "0" ? [] : unserialize($value->on_budget);
			$idMethodology = $value->id_methodology == "0" ? [] : unserialize($value->id_methodology);

			for ($i = 0; $i < count($idMethodology); $i++) {
				$singleMethode = $idMethodology[$i];
				if (@unserialize($value->on_budget)) {
					if (!in_array($singleMethode, $onBudget)) {
						$methodology = $this->CommisionVoucherModel->dataMethodology($singleMethode);
						$dataProjectWithMethodology[] = (object)[
							"nama_project_internal" => $value->nama_project . " - " . $methodology->methodology,
							"id_comm_voucher" => $value->id,
							"user_add" => $value->user_add,
						];
					}
				} else {
					$methodology = $this->CommisionVoucherModel->dataMethodology($singleMethode);
					$dataProjectWithMethodology[] = (object)[
						"nama_project_internal" => $value->nama_project . " - " . $methodology->methodology,
						"id_comm_voucher" => $value->id,
						"user_add" => $value->user_add,
					];
				}
			}
		}

		$this->check_user_research_executive($dataProjectWithMethodology, $typeProject, 'user_add');
	}

	private function check_user_research_executive($project, $typeProject, $column)
	{
		foreach ($project as $key => $value) {
			$user = $this->CommisionVoucherModel->dataUser($value->{$column});
			if ($user == null) continue;

			if ($typeProject == 'b1' && $user->dept == '76') {
				$this->projects[] = [
					"project_name" => $value->nama_project_internal,
					"id_project" => $value->id_comm_voucher,
					"from" => "comm_voucher",
				];
			}

			if ($typeProject == 'b2' && $user->dept != '76') {
				$this->projects[] = [
					"project_name" => $value->nama_project_internal,
					"id_project" => $value->id_comm_voucher,
					"from" => "data_sindikasi",
				];
			}
		}
	}
}
