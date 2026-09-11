<?php
/**
 * @var \App\View\AppView $this
 * @var int $index
 * @var string $id
 * @var string $user
 * @var \App\Model\Entity\Staff|null $staff
 * @var bool $isAssigned
 * @var string $selectedRole
 * @var array $roleOptions
 * @var array $permissionLabels
 * @var array $defaults
 * @var bool $customPermissions
 * @var bool $canSell
 * @var array $ticketTypes
 */
$limitsByType = [];
foreach ($staff ? ($staff->staff_ticket_type_limits ?? []) : [] as $limit) {
    $limitsByType[$limit->ticket_type_id] = $limit;
}
?>
<article
    class="eventic-staff-assignment <?= $isAssigned ? 'is-assigned' : '' ?>"
    data-staff-assignment
    data-staff-name="<?= h(mb_strtolower((string)$user)) ?>"
    data-staff-assigned="<?= $isAssigned ? '1' : '0' ?>"
    data-staff-persisted="<?= $isAssigned ? '1' : '0' ?>"
    data-staff-role="<?= h($selectedRole) ?>"
>
    <div class="eventic-staff-assignment-head">
        <div>
            <strong><?= h($user) ?></strong>
            <span data-staff-role-summary><?= $isAssigned ? h($staff->role_label ?: $roleOptions[$selectedRole]) : __('Disponible para asignar') ?></span>
        </div>
        <div class="eventic-staff-card-actions">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-staff-edit>
                <?= $this->FontAwesome->icon('fas', 'pen') ?>
                <?= __('Editar') ?>
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-staff-remove>
                <?= $this->FontAwesome->icon('fas', 'trash-alt') ?>
                <?= __('Quitar') ?>
            </button>
            <label class="eventic-switch eventic-staff-toggle-wrap">
                <?= $this->Form->checkbox("users.{$index}.id", [
                    'value' => $id,
                    'checked' => $isAssigned,
                    'hiddenField' => false,
                    'data-staff-toggle' => true,
                ]) ?>
                <span data-staff-toggle-label><?= $isAssigned ? __('Asignado') : __('Agregar') ?></span>
            </label>
        </div>
    </div>

    <div class="eventic-staff-assignment-body" data-staff-body <?= $isAssigned ? 'hidden' : 'hidden' ?>>
        <div class="eventic-staff-editor-head">
            <div>
                <span class="eventic-eyebrow"><?= __('Integrante del equipo') ?></span>
                <h2><?= h($user) ?></h2>
                <p><?= __('Define su rol operativo. Las cuotas solo aparecen cuando el rol puede emitir pases.') ?></p>
            </div>
            <button type="button" class="eventic-drawer-close" data-staff-editor-close aria-label="<?= h(__('Cerrar')) ?>">
                <?= $this->FontAwesome->icon('fas', 'times') ?>
            </button>
        </div>
        <div class="eventic-staff-assignment-grid">
            <div>
                <?= $this->Form->control("users.{$index}._joinData.role", [
                    'label' => __('Rol operativo'),
                    'options' => $roleOptions,
                    'value' => $selectedRole,
                    'class' => 'eventic-role-select',
                ]) ?>
            </div>
            <div class="eventic-sales-settings" data-sales-settings <?= $canSell ? '' : 'hidden' ?>>
                <?= $this->Form->control("users.{$index}._joinData.sales_limit", [
                    'label' => __('Limite global de venta'),
                    'type' => 'number',
                    'min' => 0,
                    'value' => $staff->sales_limit ?? null,
                    'placeholder' => __('Sin limite'),
                    'help' => __('Opcional. Aplica solo a usuarios que registran o venden pases.'),
                ]) ?>
            </div>
            <div>
                <label class="eventic-switch eventic-custom-permissions">
                    <?= $this->Form->checkbox("users.{$index}._joinData.custom_permissions", [
                        'checked' => $customPermissions,
                        'hiddenField' => false,
                    ]) ?>
                    <span><?= __('Permisos avanzados') ?></span>
                </label>
            </div>
        </div>

        <div class="eventic-permission-grid" data-permissions <?= $customPermissions ? '' : 'hidden' ?>>
            <?php foreach ($permissionLabels as $field => $label): ?>
                <label class="eventic-permission-chip">
                    <?= $this->Form->checkbox("users.{$index}._joinData.{$field}", [
                        'checked' => (bool)($staff->{$field} ?? $defaults[$field]),
                        'hiddenField' => false,
                        'data-permission' => $field,
                    ]) ?>
                    <span><?= h($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <?php if ($ticketTypes): ?>
            <div class="eventic-staff-type-limits" data-sales-settings <?= $canSell ? '' : 'hidden' ?>>
                <div>
                    <span class="eventic-eyebrow"><?= __('Distribucion por tipo') ?></span>
                    <p><?= __('Opcional. Si defines limite global y cuotas por tipo, la suma por tipo no puede superar el limite global.') ?></p>
                </div>
                <div class="eventic-staff-type-limit-grid">
                    <?php foreach ($ticketTypes as $type): ?>
                        <?php $limit = $limitsByType[$type->id] ?? null; ?>
                        <label>
                            <span>
                                <strong><?= h($type->name) ?></strong>
                                <small><?= __('{0} boletos del tipo', (int)$type->capacity) ?></small>
                            </span>
                            <?= $this->Form->number("users.{$index}._joinData.ticket_type_limits.{$type->id}.sales_limit", [
                                'min' => 0,
                                'max' => (int)$type->capacity,
                                'value' => $limit && $limit->active ? $limit->sales_limit : null,
                                'placeholder' => __('Sin limite'),
                                'class' => 'form-control',
                                'data-type-limit' => true,
                                'data-type-capacity' => (int)$type->capacity,
                            ]) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="eventic-staff-editor-error" data-staff-editor-error hidden></div>
        <div class="eventic-staff-editor-footer">
            <button type="button" class="btn btn-outline-secondary" data-staff-editor-close>
                <?= __('Cancelar') ?>
            </button>
            <button type="button" class="btn btn-primary" data-staff-editor-apply>
                <?= $this->FontAwesome->icon('fas', 'check') ?>
                <?= __('Guardar cambios') ?>
            </button>
        </div>
    </div>
</article>
