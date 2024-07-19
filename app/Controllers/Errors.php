<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Errors extends Controller
{
    public function show404()
    {
        return view('errors/custom_404');
    }
}
