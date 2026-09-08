<?php
declare( strict_types = 1 );

namespace App\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\MissingIdentityException;
use Authorization\Middleware\UnauthorizedHandler\RedirectHandler;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CustomRedirectHandler extends RedirectHandler {
    public function handle( Exception $exception, ServerRequestInterface $request, array $options = [] ): ResponseInterface {
        $loginUrl = $request->getParam('prefix') === 'Staff' ? '/staff/login' : '/admin/login';
        $fallbackUrl = $request->getParam('prefix') === 'Staff' ? '/staff/events' : '/admin/login';

        if ($exception instanceof MissingIdentityException) {
            $target = $request->getUri()->getPath();
            if ($target === '' || str_contains($target, '/login')) {
                $target = $fallbackUrl;
            }

            return (new Response())
                ->withStatus(302)
                ->withHeader('Location', $loginUrl . '?redirectUrl=' . rawurlencode($target));
        }

        if (!$request->getSession()->check('Flash.flash')) {
            $request->getFlash()->error(__('No tienes permisos para acceder a esta seccion.'));
        }

        return (new Response())
            ->withStatus(302)
            ->withHeader('Location', $fallbackUrl);
    }
}
