# P1 Framework Example

Port ~10% projektu [ProwadzacyStrzelanie.eu](https://prowadzacystrzelanie.eu) z Fat-Free Framework na P1.

## Uruchomienie

```bash
cd example/public
php -S localhost:8088
```

Otwórz http://localhost:8088

## Demo login

- Login: `admin`
- Hasło: `admin123`

## Co demonstruje

| Feature | Route | Plik |
|---------|-------|------|
| Static page + layout | GET /kontakt | PageController::kontakt |
| Login form + CSRF | GET/POST /login | AuthController::loginForm/login |
| Flash messages | redirect po login/logout | Flash + _flash.php partial |
| Auth guard (beforeRoute) | GET /admin | AdminController::beforeRoute |
| Logout (POST+CSRF) | POST /logout | AuthController::logout |
| DB sessions (SQLite) | automatyczne | P1\Session + data/app.db |
| Project facade | P1::isLoggedIn() etc | app/P1.php extends \P1\Base |

## Struktura

```
example/
├── public/index.php        # bootstrap, routes, server entry
├── config/app.php          # config (returns array)
├── app/P1.php              # class P1 extends \P1\Base
├── controllers/
│   ├── BaseController.php  # layout rendering, auth data
│   ├── PageController.php  # home, kontakt
│   ├── AuthController.php  # login, logout
│   └── AdminController.php # beforeRoute guard, dashboard
├── templates/
│   ├── layout.php          # HTML wrapper
│   ├── _flash.php          # flash messages partial
│   ├── home.php
│   ├── kontakt.php
│   ├── login.php
│   └── admin/dashboard.php
└── data/                   # SQLite DB (gitignored)
```
