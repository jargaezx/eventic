<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Utility\Text;

use App\Model\Entity\User;

class EventsTable extends Table
{

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('events');
        $this->setDisplayField('id');
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
                'fields' => ['name', 'description'],
            ]);

        $this->addBehavior('Josegonzalez/Upload.Upload', [
            'cover' => [
                'fields' => [
                    'dir' => 'cover_dir'
                ],
                //'path' => 'webroot{DS}files{DS}{model}{DS}{field}{DS}{time}{DS}',
                'nameCallback' => function ($table, $entity, $data, $field, $settings) {
                    $path_parts = pathinfo($data->getClientFilename());
                    return Text::slug($path_parts['filename']) . '.' . $path_parts['extension'];
                },
                'transformer' => function ($table, $entity, $data, $field, $settings, $filename) {
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    $tmp = tempnam(sys_get_temp_dir(), 'upload') . '.' . $extension;
                    $size = new \Imagine\Image\Box(40, 40);
                    $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;
                    $imagine = new \Imagine\Gd\Imagine();
                    $imagine->open($data->getStream()->getMetadata('uri'))
                        ->thumbnail($size, $mode)
                        ->save($tmp);
                    return [
                        $data->getStream()->getMetadata('uri') => $filename,
                        $tmp => 'thumbnail-' . $filename,
                    ];
                },
                'deleteCallback' => function ($path, $entity, $field, $settings) {
                    return [
                        $path . $entity->{$field},
                        $path . 'thumbnail-' . $entity->{$field},
                    ];
                },
                'keepFilesOnDelete' => false,
            ]
        ]);

        $this->belongsTo('Owners', [
            'className' => 'Users',
            'foreignKey' => 'owner_id',
        ]);

        $this->hasOne('TicketConfigurations', [
            'foreignKey' => 'event_id',
        ]);

        $this->hasMany('Tickets', [
            'foreignKey' => 'event_id',
        ]);

        $this->hasMany('Staffs', [
            'foreignKey' => 'event_id',
        ]);

        $this->belongsToMany('Users', [
            'through' => 'Staffs',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('owner_id')
            ->allowEmptyString('owner_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->allowEmptyString('name');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->dateTime('event_date')
            ->allowEmptyDateTime('event_date');

        $validator
            ->allowEmptyString('cover');

        $validator
            ->nonNegativeInteger('capacity')
            ->allowEmptyString('capacity');

        $validator
            ->nonNegativeInteger('ticket_count')
            ->allowEmptyString('ticket_count');

        $validator
            ->integer('ticket_attended_count')
            ->allowEmptyString('ticket_attended_count');

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

        $validator
            ->scalar('currency')
            ->maxLength('currency', 3)
            ->allowEmptyString('currency');

        $validator
            ->scalar('primary_color')
            ->maxLength('primary_color', 7)
            ->allowEmptyString('primary_color');

        $validator
            ->scalar('accent_color')
            ->maxLength('accent_color', 7)
            ->allowEmptyString('accent_color');

        $validator
            ->scalar('email_subject')
            ->maxLength('email_subject', 255)
            ->allowEmptyString('email_subject');

        $validator
            ->scalar('email_footer')
            ->allowEmptyString('email_footer');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);

        return $rules;
    }

    public function findOwnedBy(SelectQuery $query, User $user)
    {
        return $query->where(['owner_id' => $user->id]);
    }

    public function findMy(SelectQuery $query, User $user)
    {
        if ($user->is_superadmin) {
            return $query;
        }

        return $query->where([ 'OR' => ['owner_id' => $user->id, 'Staffs.user_id' => $user->id] ] )->leftJoinWith('Users', function ($q) use ($user) {
            return $q->where(['Staffs.user_id' => $user->id]);
        });
    }
}
