<?php

class AuthController extends MY_Controller
{
	protected $roleBpuUser = [];
	public function index_post()
	{
		$this->validate([
			'username' => 'required|string',
			'password' => 'required|min:5'
		]);

		$this->load->model('UserModel');
		$username = $this->requestInput->username;
		$password = $this->requestInput->password;

		$check = $this->UserModel->check_auth($username, md5($password));
		if (isset($check)) {
			$this->get_role_bpu_user($check);
			$check['role'] = $this->roleBpuUser;
			$check['token'] = $this->generate_token($check);

			$this->response_api(200, true, 'Login success', $check);
		} else{
			$this->response_api(401, false, 'Username atau password salah');
		}
	}

	protected function get_role_bpu_user($user)
	{
		$this->load->model('RoleBpuModel');
		$feature = ["createBpu","validateBpu","approverBpu","knowledgeBpu"];
		foreach ($feature as $key => $value) {
			$roles = $this->RoleBpuModel->role_bpu_user($value,$user['id_user']);
			foreach ($roles as $key => $valRole) {
				$this->roleBpuUser[$valRole->folder_name][$valRole->bpu]['role'][] = $value;
				$this->roleBpuUser[$valRole->folder_name][$valRole->bpu]['condition'][$value] = [
					"key" => $valRole->condition,
					"value" => $valRole->value_condition
				];
			}
		}
	}
}
