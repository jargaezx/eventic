<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;


class RolesController extends AppController
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
        $query = $this->Roles->find('search',
            search: $this->request->getQueryParams(),
            contain: ['Permissions'],
        );
        $roles = $this->paginate($query);

        $this->set(compact('roles'));
    }

    public function view($id = null)
    {
        $rol = $this->Roles->get($id, contain: ['Permissions']);
        $this->set(compact('rol'));
    }

    public function add()
    {
        $rol = $this->Roles->newEmptyEntity();

        if ($this->request->is('post')) {

            $rol = $this->Roles->patchEntity($rol, $this->request->getData(), [
                'associated' => ['Permissions' => ['onlyIds' => true]],
            ]);

            if ($this->Roles->save($rol)) {
                $this->Flash->success(__('El rol ha sido creado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('El rol no pudo ser creado. Por favor, intenta de nuevo.'));
        }

        $permissionsByModule = $this->permissionsByModule();
        $this->set(compact('rol', 'permissionsByModule'));
    }

    public function edit($id = null)
    {
        $rol = $this->Roles->get($id, contain: ['Permissions']);

        if ($this->request->is(['patch', 'post', 'put'])) {

            $rol = $this->Roles->patchEntity($rol, $this->request->getData(), [
                'associated' => ['Permissions' => ['onlyIds' => true]],
            ]);

            if ($this->Roles->save($rol)) {
                $this->Flash->success(__('El rol ha sido editado correctamente.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('El rol no pudo ser editado. Por favor, intenta de nuevo.'));
        }

        $permissionsByModule = $this->permissionsByModule();
        $this->set(compact('rol', 'permissionsByModule'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $rol = $this->Roles->get($id);

        if ($this->Roles->trash($rol)) {
            $this->Flash->success(__('El rol ha sido eliminado.'));
        } else {
            $this->Flash->error(__('El rol no pudo ser eliminado. Por favor, intente nuevamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    private function permissionsByModule(): array
    {
        $permissions = $this->Roles->Permissions->find()
            ->where(['Permissions.active' => true])
            ->orderBy(['Permissions.prefix' => 'ASC', 'Permissions.controller' => 'ASC', 'Permissions.name' => 'ASC'])
            ->all();

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = trim(($permission->prefix ?: 'App') . ' / ' . $permission->controller);
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }
}
