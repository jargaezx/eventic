<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

class TicketTypesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('ticket_types');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Events', [
            'foreignKey' => 'event_id',
        ]);
        $this->hasMany('TicketRates', [
            'foreignKey' => 'ticket_type_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'sort' => ['TicketRates.sort_order' => 'ASC', 'TicketRates.name' => 'ASC'],
        ]);
        $this->hasMany('Tickets', [
            'foreignKey' => 'ticket_type_id',
        ]);
        $this->hasMany('StaffTicketTypeLimits', [
            'foreignKey' => 'ticket_type_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('event_id')
            ->allowEmptyString('event_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 120)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->decimal('price')
            ->greaterThanOrEqual('price', 0)
            ->allowEmptyString('price');

        $validator
            ->scalar('currency')
            ->maxLength('currency', 3)
            ->allowEmptyString('currency');

        $validator
            ->nonNegativeInteger('capacity')
            ->notEmptyString('capacity', __('Indica cuantos boletos corresponden a este tipo.'));

        $validator
            ->integer('sort_order')
            ->allowEmptyString('sort_order');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['event_id'], 'Events'), ['errorField' => 'event_id']);

        return $rules;
    }

    public function beforeSave(EventInterface $event, $entity, $options): void
    {
        if ($entity->isNew() && !$entity->id) {
            $entity->id = Text::uuid();
        }
    }
}
