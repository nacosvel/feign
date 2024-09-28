<?php

namespace Nacosvel\Feign\Configuration;

use Nacosvel\Feign\Contracts\ConfigurationInterface;
use Nacosvel\Feign\Support\RequestMethod;
use Nacosvel\Container\Interop\Contracts\ApplicationInterface;
use Nacosvel\Helper\Utils;

abstract class Configuration implements ConfigurationInterface
{
    protected string $defaultMethod = RequestMethod::POST;

    protected array $services = [
        'debug' => [
            'https://httpbin.ceshiren.com/',
            'https://httpbin.org/',
        ],
        'rooter-server' => [
            'http://127.0.0.1:8086',
        ],
    ];

    protected array $consumes = [
        RequestMethod::GET     => 'query',
        RequestMethod::POST    => 'form_params',// multipart, json, form_params
        RequestMethod::PUT     => 'form_params',// json, body, form_params
        RequestMethod::DELETE  => 'query',// json, body, query
        RequestMethod::PATCH   => 'form_params',// json, body, form_params
        RequestMethod::OPTIONS => 'query',
        RequestMethod::HEAD    => 'query',
    ];

    /**
     * Bootstrap any application services.
     *
     * @param ApplicationInterface $factory
     *
     * @return void
     */
    public function boot(ApplicationInterface $factory): void
    {
        //
    }

    /**
     * Attempt to convert $key to $value based on return type
     *
     * @template T of object
     * @template S of T
     *
     * @return array<class-string<T>, S>
     */
    public function converters(): array
    {
        return [
            // Model::class      => new Post(),
            // Collection::class => new Collection(),
            // '*'               => new Transformation(),
        ];
    }

    /**
     * @return string
     */
    public function getDefaultMethod(): string
    {
        return $this->defaultMethod;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    public function consumer(string $method): string
    {
        return array_key_exists($method, $this->consumes) ? $this->consumes[$method] : $this->consumes[$this->getDefaultMethod()];
    }

    /**
     * @param string|null $name
     *
     * @return string|array|null
     */
    public function getService(?string $name = null): string|array|null
    {
        if (is_null($name)) {
            return $this->services;
        }
        if (false === array_key_exists($name, $this->services)) {
            return null;
        }
        return Utils::with($this->services[$name], function ($services) {
            return $services[array_rand($services)];
        });
    }

    /**
     * @param string|array $name
     * @param array        $services
     *
     * @return static
     */
    public function setServices(string|array $name = [], array $services = []): static
    {
        if (is_string($name)) {
            $this->services[$name] = $services;
            return $this;
        }
        $this->services = $services;
        return $this;
    }

}
