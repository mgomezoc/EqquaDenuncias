<?php

use App\Models\AuditoriaModel;

function registrarAccion($id_usuario, $accion, $detalle)
{
    $auditoriaModel = new AuditoriaModel();
    $auditoriaModel->save([
        'id_usuario' => $id_usuario,
        'accion' => $accion,
        'detalle' => $detalle
    ]);
}
