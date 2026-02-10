<?php
declare(strict_types=1);

class BaseController extends \P1\Controller {
    protected function render(string $template, array $data = []): \P1\Response {
        $data['is_logged_in'] = P1::isLoggedIn();
        $data['is_admin'] = P1::isAdmin();
        $data['current_user'] = P1::currentUser();

        return parent::render($template, $data);
    }
}
