<?php
declare( strict_types = 1 );

namespace App\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\MissingIdentityException;
use Authorization\Middleware\UnauthorizedHandler\RedirectHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CustomRedirectHandler extends RedirectHandler {
    public function handle( Exception $exception, ServerRequestInterface $request, array $options = [] ): ResponseInterface {
        $loginUrl = $request->getParam('prefix') === 'Staff' ? '/staff/login' : '/admin/login';
        $fallbackUrl = $request->getParam('prefix') === 'Staff' ? '/staff/events' : '/admin/users/dashboard';
        $options['url'] = $exception instanceof MissingIdentityException
            ? $loginUrl
            : ($request->referer() ?: $fallbackUrl);
        $response = parent::handle( $exception, $request, $options );
        if (!$exception instanceof MissingIdentityException) {
            $request->getFlash()->error(__('No tienes permisos para acceder a esta seccion.'));
        }

        return $response;
    }
}
