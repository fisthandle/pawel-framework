<?php
declare(strict_types=1);

class AdminController extends BaseController {
    public function beforeRoute(): \P1\Response|null {
        if (!P1::isLoggedIn()) {
            $this->flash('warning', 'Musisz się zalogować.');
            return $this->redirect('/login');
        }
        if (!P1::isAdmin()) {
            $this->flash('error', 'Brak dostępu.');
            return $this->redirect('/');
        }
        return null;
    }

    public function dashboard(): \P1\Response {
        return $this->render('admin/dashboard.php');
    }
}
