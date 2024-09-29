<?php

namespace Nacosvel\Feign\Contracts;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

interface RequestMiddlewareInterface extends MiddlewareInterface
{
    public function request(RequestInterface $request, array $options): RequestInterface|PromiseInterface;

}
