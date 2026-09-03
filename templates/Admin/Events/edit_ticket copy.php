<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Editar Boleto'));

$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Editar Boleto']
]);
?>
<?php
$this->Html->script('/assets/js/tinycrop.min.js', ['block' => true]);
echo $this->Form->create($ticketConfiguration);
echo $this->Form->hidden('x', ['id' => 'x']);
echo $this->Form->hidden('y', ['id' => 'y']);
echo $this->Form->hidden('qr_size', ['id' => 'qr_size']);
echo $this->Form->submit(__('Guardar'), ['class' => 'btn btn-primary w-100']);
echo $this->Form->end()
?>

<div id='ticket'></div>

<script type="text/javascript">
    <?= $this->Html->scriptStart(['block' => true]); ?>
    var crop = tinycrop.create({
        parent: '#ticket',
        image: '<?= '/' . str_replace('\\', '/', $ticketConfiguration->ticket_dir) . $ticketConfiguration->ticket ?>',
        bounds: {
            //width: '100%',
            //height: '50%'
        },
        selection: {
            color: 'yellow',
            activeColor: 'green',
            aspectRatio: 1,
            minWidth: 50,
            minHeight: 50,
            width: <?= $ticketConfiguration->qr_size ?>,
            height: <?= $ticketConfiguration->qr_size ?>,
            x: <?= $ticketConfiguration->x ?>,
            y: <?= $ticketConfiguration->y ?>
        }
    });

    var x = document.getElementById('x');
    var y = document.getElementById('y');
    var width = document.getElementById('qr_size');

    function setInputsFromRegion(region) {
        console.log(region);
        x.value = region.x;
        y.value = region.y;
        width.value = region.width;
    }

    crop
        .on('start', function(region) {
            setInputsFromRegion(region)
        })
        .on('move', function(region) {
            setInputsFromRegion(region)
        })
        .on('resize', function(region) {
            setInputsFromRegion(region)
        })
        .on('change', function(region) {
            setInputsFromRegion(region)
        })
        .on('end', function(region) {
            setInputsFromRegion(region)
        })

    <?= $this->Html->scriptEnd(); ?>
</script>