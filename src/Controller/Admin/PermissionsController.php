<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;

class PermissionsController extends AppController
{

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Search.Search', [
            'actions' => ['index', 'lookup'],
        ]);
    }

    public function index()
    {
        $query = $this->Permissions->find('search',
            search: $this->request->getQueryParams(),
            contain: ['Roles'],
        );
        $permissions= $this->paginate($query);

        $this->set(compact('permissions'));
    }

    public function view($id = null)
    {
        $permission = $this->Permissions->get($id, contain: ['Roles']);
        $this->set(compact('permission'));
    }

    public function add()
    {
        $permission = $this->Permissions->newEmptyEntity();

        if ($this->request->is('post')) {

            $permission = $this->Permissions->patchEntity($permission, $this->request->getData());

            if ($this->Permissions->save($permission)) {
                $this->Flash->success(__('El permiso ha sido creado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('El permiso no pudo ser creado. Por favor, intenta de nuevo.'));
        }
        $this->set(compact('permission'));
    }

    public function edit($id = null)
    {
        $permission = $this->Permissions->get($id, contain: []);

        if ($this->request->is(['patch', 'post', 'put'])) {

            $permission = $this->Permissions->patchEntity($permission, $this->request->getData());

            if ($this->Permissions->save($permission)) {
                $this->Flash->success(__('El permiso ha sido editado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('El permiso no pudo ser editado. Por favor, intenta de nuevo.'));
        }

        $this->set(compact('permission'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $permission = $this->Permissions->get($id);

        if ($this->Permissions->trash($permission)) {
            $this->Flash->success(__('El permiso ha sido eliminado.'));
        } else {
            $this->Flash->error(__('El permiso no pudo ser eliminado. Por favor, intente nuevamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
