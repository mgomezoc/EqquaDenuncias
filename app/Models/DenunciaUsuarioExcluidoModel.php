<?php

namespace App\Models;

use CodeIgniter\Model;

class DenunciaUsuarioExcluidoModel extends Model
{
    protected $table            = 'denuncias_usuarios_excluidos';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    // La tabla ya trae created_at por DEFAULT CURRENT_TIMESTAMP
    protected $useTimestamps    = false;

    protected $allowedFields    = [
        'id_denuncia',
        'id_usuario',
        'created_at',
        'created_by',
        'motivo',
    ];

    /**
     * Reemplaza (set completo) los usuarios excluidos de una denuncia.
     * - Borra exclusiones actuales de la denuncia
     * - Inserta las nuevas (si hay)
     */
    public function reemplazarExclusionesDeDenuncia(int $idDenuncia, array $idsUsuariosExcluidos, ?int $idUsuarioQueExcluye = null, ?string $motivo = null): bool
    {
        $idsUsuariosExcluidos = $this->normalizarIdsUsuarios($idsUsuariosExcluidos);

        $db = $this->db;
        $db->transStart();

        $this->where('id_denuncia', $idDenuncia)->delete();

        if (!empty($idsUsuariosExcluidos)) {
            $filas = [];
            foreach ($idsUsuariosExcluidos as $idUsuario) {
                $filas[] = [
                    'id_denuncia' => $idDenuncia,
                    'id_usuario'  => $idUsuario,
                    'created_by'  => $idUsuarioQueExcluye,
                    'motivo'      => $motivo,
                ];
            }

            // insertBatch es eficiente; la UNIQUE evita duplicados por denuncia/usuario
            $this->insertBatch($filas);
        }

        $db->transComplete();

        return $db->transStatus();
    }

    /**
     * Devuelve los IDs de usuarios excluidos para una denuncia.
     */
    public function obtenerIdsUsuariosExcluidos(int $idDenuncia): array
    {
        $resultados = $this->select('id_usuario')
            ->where('id_denuncia', $idDenuncia)
            ->findAll();

        if (empty($resultados)) {
            return [];
        }

        return array_map(static fn($fila) => (int) $fila['id_usuario'], $resultados);
    }

    /**
     * Indica si un usuario está excluido de una denuncia.
     */
    public function usuarioEstaExcluido(int $idDenuncia, int $idUsuario): bool
    {
        return $this->where('id_denuncia', $idDenuncia)
            ->where('id_usuario', $idUsuario)
            ->countAllResults() > 0;
    }

    /**
     * Normaliza: enteros, sin repetidos, sin vacíos/ceros.
     */
    private function normalizarIdsUsuarios(array $idsUsuarios): array
    {
        $idsNormalizados = [];

        foreach ($idsUsuarios as $id) {
            if ($id === null || $id === '') {
                continue;
            }
            $idEntero = (int) $id;
            if ($idEntero <= 0) {
                continue;
            }
            $idsNormalizados[] = $idEntero;
        }

        $idsNormalizados = array_values(array_unique($idsNormalizados));

        return $idsNormalizados;
    }
}
