<?php
declare(strict_types=1);

class AuthController extends BaseController {
    public function loginForm(): \P1\Response {
        if (P1::isLoggedIn()) {
            return $this->redirect('/');
        }
        return $this->render('login.php');
    }

    public function login(): \P1\Response {
        $this->validateCsrf();

        $data = $this->postData(['username', 'password']);
        $username = trimS($data['username']);
        $password = (string) ($data['password'] ?? '');

        $expectedUser = (string) P1::config('admin_user');
        $expectedPass = (string) P1::config('admin_pass');

        if ($username !== $expectedUser || !password_verify($password, $expectedPass)) {
            $this->flash('error', 'Nieprawidłowy login lub hasło.');
            \P1\Log::info('Login failed', ['user' => $username, 'ip' => $this->request->ip]);
            return $this->redirect('/login');
        }

        $_SESSION['user'] = [
            'id' => 1,
            'name' => $username,
            'role' => P1::ROLE_ADMIN,
        ];

        $this->flash('success', 'Zalogowano!');
        \P1\Log::info('Login OK', ['user' => $username]);
        return $this->redirect('/admin');
    }

    public function logout(): \P1\Response {
        $this->validateCsrf();
        $this->flash('info', 'Wylogowano.');
        $_SESSION = [];
        session_regenerate_id(true);
        return $this->redirect('/');
    }
}
