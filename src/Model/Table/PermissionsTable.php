<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PermissionsTable extends Table
{

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('permissions');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');
        $this->addBehavior('Search.Search');
        $this->searchManager()
            ->value('active')
            ->add('q', 'Search.Like', [
                'before' => true,
                'after' => true,
                'fieldMode' => 'OR',
                'comparison' => 'LIKE',
                'wildcardAny' => '*',
                'wildcardOne' => '?',
                'fields' => ['name', 'description', 'prefix', 'controller', 'action'],
            ]);

        $this->belongsToMany('Roles', [
            'foreignKey' => 'permission_id',
            'targetForeignKey' => 'role_id',
            'joinTable' => 'permissions_roles',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('prefix')
            ->maxLength('prefix', 255)
            ->allowEmptyString('prefix');

        $validator
            ->scalar('controller')
            ->maxLength('controller', 255)
            ->allowEmptyString('controller');

        $validator
            ->scalar('action')
            ->maxLength('action', 255)
            ->allowEmptyString('action');

        $validator
            ->add('controller', 'uniqueRoute', [
                'rule' => function ($value, array $context): bool {
                    $data = $context['data'] ?? [];

                    if (empty($data['controller']) || empty($data['action'])) {
                        return true;
                    }

                    $conditions = [
                        'prefix IS' => $data['prefix'] ?: null,
                        'controller' => $data['controller'],
                        'action' => $data['action'],
                    ];

                    if (!empty($data['id'])) {
                        $conditions['id !='] = $data['id'];
                    }

                    return !$this->exists($conditions);
                },
                'message' => __('Ya existe un permiso para esta ruta.'),
            ]);

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);
        $rules->add($rules->isUnique(['name']), ['errorField' => 'name']);
        $rules->add($rules->isUnique(['prefix', 'controller', 'action']), ['errorField' => 'controller']);

        return $rules;
    }
}
