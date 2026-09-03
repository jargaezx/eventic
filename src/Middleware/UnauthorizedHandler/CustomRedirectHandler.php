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
        $options['url'] = $exception instanceof MissingIdentityException
            ? '/users/login'
            : ($request->referer() ?: '/admin/users/dashboard');
        $response = parent::handle( $exception, $request, $options );
        $request->getFlash()->error(__('No tienes permisos para acceder a esta seccion.'));
        return $response;
    }
}
