<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;
use Cake\Mailer\MailerAwareTrait;

class UsersController extends AppController
{

    use MailerAwareTrait;

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login', 'logout', 'verify', 'forgotPassword', 'resetPassword']);
        $this->loadComponent('Search.Search', [
            'actions' => ['index', 'lookup'],
        ]);
    }

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $access = $this->request->getParam('access') === 'staff' || $this->request->getQuery('access') === 'staff'
            ? 'staff'
            : 'admin';
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            $identity = $this->Authentication->getIdentity();
            if (!$this->identityCanAccess($identity, $access)) {
                $this->Authentication->logout();
                $this->Flash->error(__('Tu sesion no tiene acceso a esta area. Ingresa con una cuenta autorizada.'));
                $this->set(compact('access'));

                return null;
            }

            $redirect = $this->resolveLoginRedirect($access, $identity);

            return $this->redirect($redirect);
        }
        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error(__('Usuario y/o contraseña incorrectos'));
        }

        $this->set(compact('access'));
    }

    private function identityCanAccess($identity, string $access): bool
    {
        if (!$identity) {
            return false;
        }

        if ($access === 'admin') {
            if ((bool)($identity->get('is_superadmin') ?? false)) {
                return true;
            }

            foreach (($identity->getOriginalData()->role->permissions ?? []) as $permission) {
                if ($permission->prefix === 'Admin') {
                    return true;
                }
            }

            return $this->Users->Staffs->find()
                ->where([
                    'Staffs.user_id' => $identity->id,
                    'Staffs.active' => true,
                    'OR' => [
                        'Staffs.can_manage_event' => true,
                        'Staffs.can_manage_staff' => true,
                        'Staffs.can_register' => true,
                        'Staffs.can_view_reports' => true,
                    ],
                ])
                ->count() > 0;
        }

        return true;
    }

    private function resolveLoginRedirect(string $access, $identity = null): array|string
    {
        $fallback = $access === 'staff'
            ? ['prefix' => 'Staff', 'controller' => 'Events', 'action' => 'index']
            : (
                $identity && !$identity->get('is_superadmin')
                    ? ['prefix'=>'Admin', 'controller' => 'Events', 'action' => 'index']
                    : ['prefix'=>'Admin', 'controller' => 'Users', 'action' => 'dashboard']
            );

        $redirect = $this->request->getData('redirectUrl') ?: $this->request->getQuery('redirectUrl') ?: $this->request->getQuery('redirect');
        if (!is_string($redirect) || $redirect === '') {
            return $fallback;
        }

        $path = parse_url($redirect, PHP_URL_PATH) ?: '';
        if ($path === '' || !str_starts_with($path, '/') || str_contains($path, '/login')) {
            return $fallback;
        }

        return $path;
    }

    public function logout()
    {
        $this->Authentication->logout();
        return $this->redirect(['prefix'=>NULL, 'controller'=>'Users', 'action'=>'login']);
    }

    public function dashboard()
    {

    }

    public function forgotPassword()
    {
        if ($this->request->is('post'))
        {
            $email = $this->request->getData('email');
            $user = $this->Users->findByEmailAndActive($email, true)->first();
            if(!$user)
            {
                $this->Flash->error(__('Lo sentimos, el correo proporcionado no se encuentra registrado en el sistema.'));
                return null;
            }

            $this->Users->generateToken($user);
            if($this->Users->save($user))
            {
                $this->getMailer('User')->send('forgotPassword', [$user]);
                $this->Flash->success(__('Se te envió un correo electrónico con las instrucciones para la recuperación de tu contraseña.'));
            }else
            {
                $this->Flash->error(__('Lo sentimos, no se puso realizar el proceso de recuperación de contraseña, por favor intentelo nuevamente.'));
            }

            return null;
        }
    }

    public function resetPassword($token=null)
    {
        if(!$token)
        {
            $this->Flash->error(__('Lo sentimos, no se ha proporcionado ningun token de activación.'));
            return $this->redirect(['action' => 'login']);
        }

        $token = $this->Users->Tokens->find()->where(['token ='=>$token, 'expiration >=' => DateTime::now()])->first();
        if(!$token)
        {
            $this->Flash->error(__('Lo sentimos, el token proporcionado no es válido o ha caducado.'));
            return $this->redirect(['action' => 'login']);
        }

        $user = $this->Users->findByIdAndActive($token->user_id, true)->first();
        if(!$user)
        {
            $this->Flash->error(__('Lo sentimos, el usuario no esta activo.'));
            return $this->redirect(['action' => 'login']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
                $user = $this->Users->patchEntity($user, $this->request->getData());
                if ($this->Users->save($user))
                {
                    $this->Users->Tokens->delete($token);
                    $this->getMailer('User')->send('changePassword', [$user]);
                    $this->Flash->success(__('La contraseña fue restablecida con éxito.'));
                    return $this->redirect(['action' => 'login']);
                }
                $this->Flash->error(__('La contraseña no puedo ser restrablecida con éxito. Por favior, intentelo nuevamente.'));
        }
        $this->set(compact('user'));
    }

    public function verify($token = NULL){
        $token = $this->Users->Tokens->find('all',
            [
                'conditions' => [
                    'Tokens.token' => $token,
                    'Tokens.expiration >=' => new DateTime('now')
                ]
            ]
        )->first();

        if(!$token){
            $this->Flash->error(__('Lo sentimos, el token proporcionado no es válido o ha caducado.'));
            return $this->redirect(['action' => 'login']);
        }

        $user = $this->Users->get($token->user_id);
        $user->verified = new DateTime('now');
        $user->active = 1;

        if($this->Users->save($user)){
            $this->Users->Tokens->delete($token);
            $this->Flash->success(__('La cuenta fue verificada con éxito.'));
        }else
            $this->Flash->error(__('Lo sentimos, su cuenta no pudo ser verificada correctamente, por favor, intente nuevamente.'));
        return $this->redirect(['action' => 'login']);
    }

}
