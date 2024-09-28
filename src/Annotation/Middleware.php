<?php

namespace Nacosvel\Feign\Annotation;

use Attribute;
use Nacosvel\Feign\Contracts\MiddlewareInterface;
use Nacosvel\Feign\Contracts\RequestMiddlewareInterface;
use Nacosvel\Feign\Contracts\ResponseMiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware implements MiddlewareInterface
{
    private string $request  = '';
    private string $response = '';

    public function __construct(
        string $request,
        string $response,
    )
    {
        $this->setRequest($request)->setResponse($response);
    }

    /**
     * @return RequestMiddlewareInterface|null
     */
    public function getRequest(): RequestMiddlewareInterface|null
    {
        return is_subclass_of($this->request, RequestMiddlewareInterface::class) ? new $this->request : null;
    }

    /**
     * @param string $request
     *
     * @return static
     */
    public function setRequest(string $request): static
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return ResponseMiddlewareInterface|null
     */
    public function getResponse(): ResponseMiddlewareInterface|null
    {
        return is_subclass_of($this->response, ResponseMiddlewareInterface::class) ? new $this->response : null;
    }

    /**
     * @param string $response
     *
     * @return static
     */
    public function setResponse(string $response): static
    {
        $this->response = $response;
        return $this;
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $handler(call_user_func([$this->getRequest() ?? $this, 'request'], $request, $options), $options)->then(
                function (ResponseInterface $response) use ($request, $options) {
                    return call_user_func([$this->getResponse() ?? $this, 'response'], $request, $response, $options);
                }
            );
        };
    }

    public function request(RequestInterface $request, array $options): RequestInterface
    {
        return $request;
    }

    public function response(RequestInterface $request, ResponseInterface $response, array $options): ResponseInterface
    {
        return $response;
    }

}
