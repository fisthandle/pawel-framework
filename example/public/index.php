<?php
declare(strict_types=1);

// Load framework (direct require, no Composer needed)
require dirname(__DIR__, 2) . '/src/P1.php';

// Load project classes
require dirname(__DIR__) . '/app/P1.php';
require dirname(__DIR__) . '/controllers/BaseController.php';
require dirname(__DIR__) . '/controllers/PageController.php';
require dirname(__DIR__) . '/controllers/AuthController.php';
require dirname(__DIR__) . '/controllers/AdminController.php';

// Bootstrap
$app = new \P1\App();
$app->loadConfig(dirname(__DIR__) . '/config/app.php');

// Database
$db = $app->db();

// Create sessions table if not exists (SQLite)
$db->exec('CREATE TABLE IF NOT EXISTS sessions (
    session_id VARCHAR(128) NOT NULL PRIMARY KEY,
    data TEXT NOT NULL DEFAULT "",
    ip VARCHAR(45) NOT NULL DEFAULT "",
    agent VARCHAR(5000) NOT NULL DEFAULT "",
    stamp INT NOT NULL DEFAULT 0
)');

// DB session handler
$session = new \P1\Session($db, advisory: false);
$session->register();
session_start();

// Init logging
\P1\Log::init((string) $app->config('log_path'), 3);

// Routes
$app->get('/', PageController::class, 'home', name: 'home');
$app->get('/kontakt', PageController::class, 'kontakt', name: 'kontakt');
$app->get('/login', AuthController::class, 'loginForm', name: 'login');
$app->post('/login', AuthController::class, 'login');
$app->post('/logout', AuthController::class, 'logout', name: 'logout');
$app->get('/admin', AdminController::class, 'dashboard', name: 'admin');

// Run
$app->run();
