<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSugerenciasIADenuncias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_denuncia' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'sugerencia_generada' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'tokens_utilizados' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'costo_estimado' => [
                'type' => 'DECIMAL',
                'constraint' => '8,6',
                'default' => 0.000000,
            ],
            'modelo_ia_usado' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'gpt-4o',
            ],
            'tiempo_generacion' => [
                'type' => 'DECIMAL',
                'constraint' => '5,3',
                'null' => true,
                'comment' => 'Tiempo en segundos'
            ],
            'estado_sugerencia' => [
                'type' => 'ENUM',
                'constraint' => ['generada', 'vista', 'evaluada'],
                'default' => 'generada',
            ],
            'evaluacion_usuario' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'comment' => 'Calificación del 1 al 5'
            ],
            'comentarios_usuario' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => '0000-00-00 00:00:00',
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => '0000-00-00 00:00:00',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('id_denuncia', 'denuncias', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey(['id_denuncia', 'created_at']);
        $this->forge->addKey('estado_sugerencia');

        $this->forge->createTable('sugerencias_ia_denuncias');
    }

    public function down()
    {
        $this->forge->dropTable('sugerencias_ia_denuncias');
    }
}
