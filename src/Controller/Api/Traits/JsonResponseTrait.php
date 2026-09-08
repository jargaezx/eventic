<?php

namespace App\Controller\Api\Traits;

trait JsonResponseTrait
{
    protected function response($data, $status=200)
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode($data));
    }

    protected function responseOK($data = [])
    {
        $code = 200;
        $data = array_merge([
            'code'  => $code,
            'message' => __('La operación fue realizada con éxito.')
        ], $data);
        return $this->response($data, $code);
    }

    protected function responseBad($data = [])
    {
        $code = 400;
        $data = array_merge([
            'code'  => $code,
            'message' => __('Lo sentimos, la operación solicitada no fue realizada con éxito, por favor intentelo nuevamente.')
        ], $data);
        return $this->response($data, $code);
    }

    protected function responseStatus(int $code, array $data = [])
    {
        $data = array_merge([
            'code' => $code,
        ], $data);

        return $this->response($data, $code);
    }
}
