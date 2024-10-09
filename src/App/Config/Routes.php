<?php

declare(strict_types=1);

namespace App\Config;

use App\Controllers\AboutController;
use App\Controllers\HomeController;
use Framework\App;

function registerRoutes(App $app)
{
    $app->get('/', [HomeController::class, 'home']);
    $app->get('/about', [AboutController::class, 'about']);
}
