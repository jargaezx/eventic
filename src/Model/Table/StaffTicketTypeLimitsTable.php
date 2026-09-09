<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

class StaffTicketTypeLimitsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('staff_ticket_type_limits');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Staffs', [
            'foreignKey' => 'staff_id',
        ]);
        $this->belongsTo('Events', [
            'foreignKey' => 'event_id',
        ]);
        $this->belongsTo('TicketTypes', [
            'foreignKey' => 'ticket_type_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach (['staff_id', 'event_id', 'ticket_type_id'] as $field) {
            $validator
                ->uuid($field)
                ->allowEmptyString($field);
        }

        $validator
            ->nonNegativeInteger('sales_limit')
            ->allowEmptyString('sales_limit');

        $validator
            ->nonNegativeInteger('sales_count')
            ->allowEmptyString('sales_count');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['staff_id', 'ticket_type_id']), [
            'errorField' => 'ticket_type_id',
        ]);
        $rules->add($rules->existsIn(['staff_id'], 'Staffs'), ['errorField' => 'staff_id']);
        $rules->add($rules->existsIn(['event_id'], 'Events'), ['errorField' => 'event_id']);
        $rules->add($rules->existsIn(['ticket_type_id'], 'TicketTypes'), ['errorField' => 'ticket_type_id']);

        return $rules;
    }

    public function beforeSave(EventInterface $event, $entity, $options): void
    {
        if ($entity->isNew() && !$entity->id) {
            $entity->id = Text::uuid();
        }
    }
}
