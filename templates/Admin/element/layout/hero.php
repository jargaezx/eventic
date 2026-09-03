<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold mb-1">
                    <?= $this->fetch('title') ?>
                </h1>
                <h2 class="fs-base lh-base fw-medium text-muted mb-0">
                    <?= $this->fetch('subtitle') ?>
                </h2>
            </div>
            <?php
                $this->Breadcrumbs->setTemplates([
                    'wrapper' => '<nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3"><ol{{attrs}}>{{content}}</ol></nav>',
                    'item' => '<li{{attrs}}><a href="{{url}}"{{innerAttrs}} class="link-fx">{{title}}</a></li>{{separator}}'
                ]);
                echo $this->Breadcrumbs->prepend('Inicio', '/users/home')
                ->render(
                    ['class' => 'breadcrumb-alt'],
                    [
                        'separator' => '<i class="fa fa-angle-right"></i>',
                    ]
                );
            ?>
        </div>
    </div>
</div>