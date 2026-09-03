<?php
    $this->assign('title', __('Restablecimiento de Contraseña'));
?>
<div class="block block-rounded mb-0">
     <div class="block-header block-header-default">
         <h3 class="block-title"><?= __('Restablecimiento de Contraseña') ?></h3>
         <div class="block-options">
            <?= $this->Html->link('<i class="fa fa-sign-in-alt"></i>', ['controller'=>'Users', 'action'=>'login'], ['class'=>'btn-block-option', 'escape'=>false]) ?>
         </div>
     </div>
     <div class="block-content">
         <div class="p-sm-3 px-lg-4 px-xxl-5 py-lg-5">
             <h1 class="h2 mb-1"><?= env('APP_NAME') ?></h1>
             <p class="fw-medium text-muted">
                 <?= __('Por favor ingrese y confirme su nueva contraseña.') ?>
             </p>
             <?php
                echo $this->Flash->render();
                echo $this->Form->create($user);
                echo $this->Form->control('password', [ 'label'=> ['text'=>__('Contraseña'), 'floating'=>true], 'value'=>'', 'class' => 'form-control-alt form-control-lg']);
                echo $this->Form->control('password_confirm', ['type'=>'password', 'label'=> ['text'=>__('Confirmar Contraseña'), 'floating'=>true], 'class' => 'form-control-alt form-control-lg']);
                echo $this->Form->submit(__('Restablecer Contraseña'), ['class' => 'w-100']);
                echo $this->Form->end();
            ?>
         </div>
     </div>
 </div>