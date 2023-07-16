<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CourseModel;
use App\Models\CourseXPModel;

class RegisterController extends BaseController
{

    public function __construct()
    {
        helper(['url', 'form']);
    }


    public function register()
    {
        return view('auth/register');
    }


    public function activate($link_here)
    {
        $user = new UserModel();
        $check_user_link = $user->where('link', $link_here)->findAll();

        if (count($check_user_link) > 0) {
            $temp_data['status'] = 1;
            $temp_data['activation_date'] = date('Y-m-d');
            $activate_user = $user->update($check_user_link[0]['user_id'], $temp_data);

            $course_list = new CourseModel();
            $data = $course_list->CourseIdDetails();

            foreach ($data as $row) {
                foreach ($row as $course_id) {
                    $user_new_xp = new CourseXPModel();
                    $user_id = $check_user_link[0]['user_id'];

                    $values = [
                        'user_id' => $user_id,
                        'course_id' => $course_id
                    ];

                    $query = $user_new_xp->insert($values);
                }
            }
            return  redirect()->to('login')->with('success', 'Account activated successfully. Please login.');
        } else {
            return  redirect()->to('login')->with('fail', 'Account not activated. Please activate account.');
        }
    }


    public function save()
    {
        $validation = $this->validate([
            'first_name' => [
                'rules'  => 'required',
                'errors' => [
                    'required' => 'First name is required',
                ],
            ],
            'last_name' => [
                'rules'  => 'required',
                'errors' => [
                    'required' => 'Last name is required',
                ],
            ],
            'dob' => [
                'rules'  => 'required|validate_age',
                'errors' => [
                    'required' => 'Date of birth is required',
                    'validate_age' => 'You must be at least 13 years to make an account',
                ],
            ],
            'email' => [
                'rules'  => 'required|valid_email|is_unique[tbl_users.email]',
                'errors' => [
                    'required' => 'Email is required',
                    'valid_email' => 'Email is not valid',
                    'is_unique' => 'Email already taken',
                ],
            ],
            'password' => [
                'rules'  => 'required|min_length[5]|max_length[30]',
                'errors' => [
                    'required' => 'Password is required.',
                    'min_length' => 'Password must have at least 5 characters in length',
                    'max_length' => 'Password must not have more than 30 characters in length',
                ],
            ],
            'confirmpassword' => [
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'Confirm password is required',
                    'matches' => 'Confirm Password must match Password',
                ],
            ],
        ]);

        if (!$validation) {
            return view('auth/register', ['validation' => $this->validator]);
        } else {
            //Register user in database
            $first_name = $this->request->getPost('first_name');
            $last_name = $this->request->getPost('last_name');
            $gender = $this->request->getPost('gender');
            $dob = $this->request->getPost('dob');
            $user_email = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            $link = uniqid();

            $values = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'gender' => $gender,
                'dob' => $dob,
                'email' => $user_email,
                'password' => password_hash("$password", PASSWORD_DEFAULT),
                'link' => $link
            ];

            $message = "Please activate your account. " . anchor('activate/' . $values['link'], 'Activate Now', '');

            $userModel = new UserModel();
            $query = $userModel->insert($values);

            $values = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'gender' => $gender,
                'dob' => $dob,
                'email' => $user_email,
                'password' => password_hash("$password", PASSWORD_DEFAULT),
                'link' => $link
            ];


            if (!$query) {
                return  redirect()->to('register')->with('fail', 'Something went wrong. Please try again.');
            } else {
                $email = \Config\Services::email();

                $email->setFrom('skillssmart5@gmail.com', 'Skill Smart');
                $email->setTo($user_email);

                $email->setSubject('Activate your account | Skillsmart');
                $email->setMessage($message);

                $email->send();

                $email->printDebugger(['headers']);

                return  redirect()->to('register')->with('success', 'Account created successfully. Please activate account using link sent to email.');
            }
        }
    }
}
