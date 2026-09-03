<?php
use Cake\Utility\Hash;

$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Staff'));
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Staff'],
]);
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Equipo operativo') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Asigna permisos de registro y validacion para el personal en sitio.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->Html->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="eventic-card">
        <?= $this->Form->create(null) ?>
        <div class="eventic-table-wrap">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= __('Usuario') ?></th>
                        <th><?= __('Asignado') ?></th>
                        <th><?= __('Registro') ?></th>
                        <th><?= __('Escaneo') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 0; ?>
                    <?php foreach ($users as $id => $user): ?>
                        <?php $staff = Hash::extract($event->users, "{n}[id={$id}]._joinData"); ?>
                        <tr>
                            <td><strong><?= h($user) ?></strong></td>
                            <td><?= $this->Form->control("users.{$i}.id", ['type' => 'checkbox', 'value' => $id, 'label' => false, 'checked' => !empty($staff), 'hiddenField' => false]) ?></td>
                            <td><?= $this->Form->control("users.{$i}._joinData.register", ['type' => 'checkbox', 'label' => false, 'checked' => Hash::get($staff, '0.register', false), 'hiddenField' => false]) ?></td>
                            <td><?= $this->Form->control("users.{$i}._joinData.scan", ['type' => 'checkbox', 'label' => false, 'checked' => Hash::get($staff, '0.scan', false), 'hiddenField' => false]) ?></td>
                        </tr>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $this->Form->button(__('{0} Guardar staff', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100 mt-3', 'escapeTitle' => false]) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
