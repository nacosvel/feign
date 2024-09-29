<?php

namespace Nacosvel\Feign\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Nacosvel\Feign\Contracts\RequestMiddlewareInterface;
use Psr\Http\Message\RequestInterface;

class RequestMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function request(RequestInterface $request, array $options): RequestInterface|PromiseInterface
    {
        return $request;
    }

}
