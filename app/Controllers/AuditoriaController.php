<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;

class AuditoriaController extends BaseController
{
    public function index()
    {
        $auditoriaModel = new AuditoriaModel();
        $data['auditorias'] = $auditoriaModel->orderBy('fecha', 'DESC')->findAll();

        return view('auditoria/index', $data);
    }
}
