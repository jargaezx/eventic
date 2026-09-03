<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

/**
 * User mailer.
 */
class UserMailer extends Mailer
{
    /**
     * Mailer's name.
     *
     * @var string
     */
    public static string $name = 'User';

    public function welcome($user)
    {
        $this->setTo($user->email)
            ->setSubject(__('Bienvenido'))
            ->setEmailFormat('html')
            ->setViewVars(['name'=>$user->names]);
    }


    public function activate($user)
    {
        $this->setTo($user->email)
            ->setSubject(__('Verificación de Cuenta'))
            ->setEmailFormat('html')
            ->setViewVars(['name'=>$user->names, 'token'=>$user->tokens[0]->token]);
    }

    public function forgotPassword($user)
    {
        $this->setTo($user->email)
            ->setSubject(__('Restablecimiento de Contraseña'))
            ->setEmailFormat('html')
            ->setViewVars(['name'=>$user->names, 'token'=>$user->tokens[0]->token]);
    }

    public function changePassword($user)
    {
        $this->setTo($user->email)
            ->setSubject(__('Cambio de Contraseña'))
            ->setEmailFormat('html')
            ->setViewVars(['name'=>$user->email]);
    }
}
