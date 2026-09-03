<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TicketConfigurationsTable extends Table
{

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('ticket_configurations');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Josegonzalez/Upload.Upload', [
            'ticket' => [
                'fields' => [
                    'dir' => 'ticket_dir'
                ],
                'path' => 'webroot{DS}files{DS}{model}{DS}{field}{DS}{time}{DS}',
                'nameCallback' => function ($table, $entity, $data, $field, $settings) {
                    return strtolower($data->getClientFilename());
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

        $this->belongsTo('Events', [
            'foreignKey' => 'event_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('event_id')
            ->allowEmptyString('event_id');

        $validator
            ->nonNegativeInteger('qr_size')
            ->allowEmptyString('qr_size');

        $validator
            ->nonNegativeInteger('y')
            ->allowEmptyString('y');

        $validator
            ->nonNegativeInteger('x')
            ->allowEmptyString('x');

        $validator
            ->allowEmptyString('ticket');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);
        $rules->add($rules->isUnique(['event_id']), [
            'errorField' => 'event_id',
            'message' => __('El evento ya tiene una configuracion de boleto.'),
        ]);
        $rules->add($rules->existsIn(['event_id'], 'Events'), ['errorField' => 'event_id']);

        return $rules;
    }
}
