<?php
declare(strict_types=1);

class PageController extends BaseController {
    public function home(): \P1\Response {
        return $this->render('home.php');
    }

    public function kontakt(): \P1\Response {
        return $this->render('kontakt.php');
    }
}
