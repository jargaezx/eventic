<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

use Cake\I18n\DateTime;
use Cake\Utility\Security;

class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');
        $this->addBehavior('Search.Search');
        $this->searchManager()
            ->value('role_id')
            ->value('active')
            ->add('q', 'Search.Like', [
                'before' => true,
                'after' => true,
                'fieldMode' => 'OR',
                'comparison' => 'LIKE',
                'wildcardAny' => '*',
                'wildcardOne' => '?',
                'fields' => ['email', 'names', 'first_surname', 'second_surname'],
            ]);

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
        ]);

        $this->hasMany('Events', [
            'foreignKey' => 'owner_id',
        ]);

        $this->hasMany('Tokens', [
            'foreignKey' => 'user_id',
        ]);

    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('role_id')
            ->allowEmptyString('role_id');

        $validator
            ->email('email', false, __('El valor proporcionado no corresponde a un correo electrónico.'))
            ->requirePresence('email', 'create', __('El correo electrónico es obligatorio.'))
            ->notEmptyString('email', __('El correo electrónico no puede quedarse vacío.'))
            ->add('email', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('El correo proporcionado ya fue registrado en el sistema.')
            ]);

        $validator
            ->minLength('password', 8, __('La longitud mínima de la contrasela es de 8 caracteres.'))
            ->requirePresence('password', 'create', __('La contraseña es obligatoria.'))
            ->notEmptyString('password', __('La contraseña no puede quedarse vacía.'))
            ->add('password', 'custom', [
                'rule' => function($value, $context){
                    return (bool)preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}/", $value);
                },
                'message' => __('La contraseña debe contener al menos un caracter de cada uno de los siguientes grupos (Letras Minúsculas, Letras Mayúsculas y Dígitos).')
            ]);

        $validator
            ->equalToField('password_confirm', 'password', __('Las contraseñas no coinciden'));

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

        $validator
            ->boolean('is_superadmin')
            ->allowEmptyString('is_superadmin');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);
        $rules->add($rules->existsIn(['role_id'], 'Roles'), ['errorField' => 'rol_id']);

        return $rules;
    }

    public function generateToken(&$user){
        $token = $this->Tokens->newEmptyEntity();
        $token->token = Security::randomString(32);
        $token->expiration = new DateTime('+2 hours');
        $user->tokens = [$token];
    }

    public function findAuth($query, array $options)
    {
        return $query->contain(['Roles.Permissions'])->where(['Users.active' => true]);
    }

}
