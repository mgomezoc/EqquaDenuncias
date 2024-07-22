<?php foreach ($auditorias as $auditoria) : ?>
    <tr>
        <td><?= $auditoria['id_usuario']; ?></td>
        <td><?= $auditoria['accion']; ?></td>
        <td><?= $auditoria['detalle']; ?></td>
        <td><?= $auditoria['fecha']; ?></td>
    </tr>
<?php endforeach; ?>