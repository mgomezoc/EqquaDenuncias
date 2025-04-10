<?php

if (!function_exists('convertir_fecha')) {
    /**
     * Convierte una fecha de formato dd/mm/yyyy a yyyy-mm-dd (para MySQL)
     *
     * @param string|null $fecha
     * @return string|null
     */
    function convertir_fecha(?string $fecha): ?string
    {
        if (!$fecha) return null;

        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0]; // yyyy-mm-dd
        }

        return null;
    }
}
