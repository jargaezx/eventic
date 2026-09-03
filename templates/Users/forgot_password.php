<?php
    $this->assign('title', __('Recuperación de Contraseña'));
?>

<div class="block block-rounded mb-0">
     <div class="block-header block-header-default">
         <h3 class="block-title"><?= __('Recuperación de Contraseña') ?></h3>
         <div class="block-options">
            <?= $this->Html->link('<i class="fa fa-sign-in-alt"></i>', ['controller'=>'Users', 'action'=>'login'], ['class'=>'btn-block-option', 'escape'=>false]) ?>
         </div>
     </div>
     <div class="block-content">
         <div class="p-sm-3 px-lg-4 px-xxl-5 py-lg-5">
             <h1 class="h2 mb-1"><?= env('APP_NAME') ?></h1>
             <p class="fw-medium text-muted">
                 <?= __('Por favor ingresa tu correo electrónico o nombre de usuario para poder inicar el proceso de restauración de contraseña.') ?>
             </p>
             <?php
                echo $this->Form->create();
                echo $this->Form->control('email', ['label' => ['text' => __('Correo electrónico'), 'floating' => true], 'class' => 'form-control-alt form-control-lg']);
                echo $this->Form->submit(__('Recuperar Contraseña'), ['class' => 'w-100']);
                echo $this->Form->end();
            ?>
         </div>
     </div>
 </div>