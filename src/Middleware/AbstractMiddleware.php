<?php

namespace Nacosvel\Feign\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Nacosvel\Feign\Contracts\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = call_user_func([$this, 'request'], $request, $options);
            if ($request instanceof PromiseInterface) {
                return $request;
            }
            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $options) {
                    return call_user_func([$this, 'response'], $request, $response, $options);
                }
            );
        };
    }

    public function request(RequestInterface $request, array $options): RequestInterface|PromiseInterface
    {
        return $request;
    }

    public function response(RequestInterface $request, ResponseInterface $response, array $options): ResponseInterface
    {
        return $response;
    }

}
