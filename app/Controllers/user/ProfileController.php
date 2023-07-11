<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProfileController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    public function index()
    {
        $id = session()->get('user_id');
        $data = $this->userModel->where('id', $id)->first();
        return view('user/profil', ['user' => $data]);
    }

    public function updateProfile()
    {
        $userId = session()->get('user_id');
        $name = $this->request->getPost('name');
        $email = $this->request->getPost('email');

        $validation = $this->validate([
            'name' => 'required',
            'email' => [
                'rules' => 'required|valid_email|is_unique[pengguna.email]',
                'errors' => [
                    'required' => 'The name field is required.',
                    'valid_email' => 'Please enter a valid email address.',
                    'is_unique' => 'This email address is already taken.',
                ],
            ],
        ]);

        if (!$validation) {
            $errorMessages = [];
            foreach ($this->validator->getErrors() as $field => $error) {
                $errorMessages[] = $error;
            }
            return $this->response->setJSON(['is_valid' => false, 'message' => $errorMessages, 'data' => $errorMessages]);
        }


        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data user not found']);
        }

        $data = [
            'name' => $name,
            'email' => $email,
        ];

        $this->userModel->update($userId, $data);

        return $this->response->setJSON(['success' => true, 'message' => 'Data Updated', 'data' => $data]);

    }
    public function changePassword()
    {
        $userId = session()->get('user_id');

        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        // Retrieve the user from the database
        $user = $this->userModel->find($userId);

        // Verify if the current password matches the one stored in the database
        if (!password_verify($currentPassword, $user['password'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Current password is incorrect'])->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Verify if the new password and confirm password match
        if ($newPassword !== $confirmPassword) {
            return $this->response->setJSON(['success' => false, 'message' => 'New password and confirm password do not match'])->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Generate a new password hash
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the user's password in the database
        $data = [
            'password' => $newPasswordHash
        ];
        $this->userModel->update($userId, $data);

        return $this->response->setJSON(['success' => true, 'message' => 'Password changed successfully']);
    }
}
