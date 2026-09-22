<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Exception\HttpException;
use Cake\Http\Response;
use Cake\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiErrorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getParam('prefix') !== 'Api') {
            return $handler->handle($request);
        }
        try {
            return $handler->handle($request);
        } catch (\Throwable $exception) {
            $code = $exception instanceof HttpException ? $exception->getCode() : 500;
            $code = $code >= 400 && $code <= 599 ? $code : 500;
            Log::error('API ' . $request->getUri()->getPath() . ': ' . $exception->getMessage());

            return (new Response())->withStatus($code)->withType('application/json')
                ->withHeader('Cache-Control', 'no-store')
                ->withStringBody(json_encode([
                    'code' => $code,
                    'status' => 'error',
                    'message' => $code >= 500
                        ? __('No se pudo confirmar el acceso. Vuelve a escanear el pase antes de autorizar la entrada.')
                        : __('No se pudo completar la solicitud. Revisa tu sesión y vuelve a intentarlo.'),
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        }
    }
}
