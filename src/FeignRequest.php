<?php

namespace Nacosvel\Feign;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use Nacosvel\Feign\Annotation\Contracts\FeignClientInterface;
use Nacosvel\Feign\Annotation\Contracts\RequestAttributeInterface;
use Nacosvel\Feign\Annotation\Contracts\RequestMappingInterface;
use Nacosvel\Feign\Annotation\RequestAttribute;
use Nacosvel\Feign\Annotation\RequestMapping;
use Nacosvel\Feign\Contracts\ConfigurationInterface;
use Nacosvel\Feign\Contracts\FeignRequestInterface;
use Nacosvel\Feign\Contracts\RequestTemplateInterface;
use Nacosvel\Feign\Exception\FeignException;
use Nacosvel\Feign\Exception\FeignRuntimeException;
use Nacosvel\Feign\Middleware\FallbackMiddleware;
use Nacosvel\Feign\Middleware\UserAgentMiddleware;
use Nacosvel\Helper\Utils;
use Nacosvel\OpenHttp\Builder;
use Nacosvel\OpenHttp\Contracts\ChainableInterface;
use Psr\Http\Message\ResponseInterface;
use function Nacosvel\Container\Interop\application;

class FeignRequest implements FeignRequestInterface
{
    protected ChainableInterface     $client;
    protected ConfigurationInterface $configuration;

    public function __construct(
        protected RequestTemplateInterface $requestTemplate
    )
    {
        $this->client        = Builder::factory();
        $this->configuration = application(ConfigurationInterface::class);
    }

    /**
     * @throws FeignException
     */
    public function __invoke(): ResponseInterface
    {
        try {
            return $this->getClient()
                ->chain($this->buildPath())
                ->request($this->buildMethod(), $this->buildOptions());
        } catch (BadResponseException $exception) {
            throw new FeignException($exception->getResponse()->getBody()->getContents(), $exception->getCode(), $exception);
        } catch (Exception $exception) {
            throw new FeignException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return ChainableInterface
     */
    public function getClient(): ChainableInterface
    {
        $client = $this->getFeignClient()->getClient();
        $this->client->getClient()->setRequestClient(call_user_func($client));
        $handler = $this->client->getClient()->getConfig('handler');
        foreach ($this->requestTemplate->getRequestMiddlewares() as $middleware) {
            $handler->push($middleware, get_class($middleware));
        }
        $handler->push(new UserAgentMiddleware(), 'UserAgentMiddleware');
        foreach ($this->requestTemplate->getResponseMiddlewares() as $middleware) {
            $handler->push($middleware, get_class($middleware));
        }
        $handler->push(new FallbackMiddleware($this->getFeignClient()->getFallback()), 'FallbackMiddleware');
        foreach ($this->requestTemplate->getMiddlewares() as $middleware) {
            $handler->push($middleware, get_class($middleware));
        }
        return $this->client;
    }

    protected function getFeignClient(): FeignClientInterface
    {
        return $this->requestTemplate->getFeignClient();
    }

    protected function getFeignClientPath(): string
    {
        if (is_null($path = $this->getFeignClient()->getPath())) {
            $path = $this->requestTemplate->getPropertyName();
        }
        return $this->convertToPath($path);
    }

    protected function getRequestMapping(): RequestMappingInterface
    {
        $requestMapping = $this->requestTemplate->getRequestMapping() ?? new RequestMapping($this->requestTemplate->getMethodName());
        return $this->requestTemplate
            ->setRequestMapping($requestMapping)
            ->getRequestMapping();
    }

    protected function getRequestMappingPath(): string
    {
        if (is_null($path = $this->getRequestMapping()->getPath())) {
            $path = $this->requestTemplate->getMethodName();
        }
        return $this->convertToPath($path);
    }

    protected function getRequestAttribute(): RequestAttributeInterface
    {
        $requestAttribute = $this->requestTemplate->getRequestAttribute() ?? new RequestAttribute($this->configuration->consumer($this->buildMethod()));
        return $this->requestTemplate
            ->setRequestAttribute($requestAttribute)
            ->getRequestAttribute();
    }

    public function buildURI(): string
    {
        if (is_null($url = $this->getFeignClient()->getUrl())) {
            $url = $this->configuration->getService($this->getFeignClient()->getName());
        }
        if (is_null($url)) {
            throw new FeignRuntimeException('`baseUrl` is a required parameter');
        }
        return rtrim($this->getFeignClient()->setUrl($url)->getUrl(), '/');
    }

    public function buildPath(): string
    {
        return $this->buildURI() . $this->getFeignClientPath() . $this->getRequestMappingPath();
    }

    public function buildMethod(): string
    {
        return $this->getRequestMapping()
            ->setMethod($this->getRequestMapping()->getMethod() ?? $this->configuration->getDefaultMethod())
            ->getMethod();
    }

    public function buildOptions(): array
    {
        return array_merge(array_filter($body = $this->requestTemplate->getBody(), function ($key) {
            return !in_array(strtolower($key), [
                '_conditional',
                'auth', 'curl', 'decode_content', 'sink',
                'version',
            ]);
        }, ARRAY_FILTER_USE_KEY), [
            'headers'                                => $this->getRequestMapping()->getHeaders(),
            $this->getRequestAttribute()->getValue() => $body,
        ]);
    }

    protected function convertToPath(string $path): string
    {
        $paths = array_filter(explode('/', $path));
        $path  = implode('/', array_map(function ($path) {
            return Utils::camelToKebab($path);
        }, $paths));
        return count($paths) ? "/{$path}" : '';
    }

}
