# Nacosvel Feign HTTP Client

This is a PHP implementation inspired by the Feign client used in microservices architecture. It provides a lightweight
HTTP client that integrates declarative web service calling with easy annotation-based APIs.

[![GitHub Tag](https://img.shields.io/github/v/tag/nacosvel/feign)](https://github.com/nacosvel/feign/tags)
[![Total Downloads](https://img.shields.io/packagist/dt/nacosvel/feign?style=flat-square)](https://packagist.org/packages/nacosvel/feign)
[![Packagist Version](https://img.shields.io/packagist/v/nacosvel/feign)](https://packagist.org/packages/nacosvel/feign)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/nacosvel/feign)](https://github.com/nacosvel/feign)
[![Packagist License](https://img.shields.io/github/license/nacosvel/feign)](https://github.com/nacosvel/feign)

## Features

- Declarative HTTP client interface
- Annotation-driven request handling
- Custom middleware for requests and responses
- Extensible with various mapping and middleware interfaces
- Support for microservices with FeignClient annotations

## Development Plan

- [x] Supports `#[EnableFeignClients]` annotation
- [x] Supports `#[Autowired]` annotation
- [x] Supports `#[FeignClient]` annotation
- [x] Supports `#[RequestMapping]` annotation
- [x] Supports `#[RequestAttribute]` annotation
- [x] Supports `#[Middleware]` annotation
- [x] Support custom response type
- [ ] Implement service discovery
- [x] Support Without Interface Dependency

## Installation

You can install the package via [Composer](https://getcomposer.org/):

```bash
composer require nacosvel/feign
```

## Quick Start

It is super easy to get started with your first project.
> Your project must support `PSR-11`.

### Step 1 : Build a [FeignRegistrar](src/FeignRegistrar.php) instance in the service provider of your project's container.

```php
use Nacosvel\Feign\FeignRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FeignRegistrar::builder();
    }

}
```

### Step 2 : Use [`#[Autowired]`](src/Annotation/Autowired.php) to inject properties.

```php
use Nacosvel\Feign\Annotation\Autowired;
use Nacosvel\Feign\Contracts\AutowiredInterface;

class PostController implements AutowiredInterface
{
    #[Autowired]
    protected PostInterface|AutowiredInterface $post;

    public function index(): string
    {
        return $this->post->detail(name: 'nacosvel/feign', version: 'v1.0.0')->getRawContents();
    }

}
```

### Step 3 : Define interfaces using [`#[FeignClient]`](src/Annotation/FeignClient.php) and [`#[RequestMapping]`](src/Annotation/RequestMapping.php).

```php
use Nacosvel\Feign\Annotation\FeignClient;
use Nacosvel\Feign\Annotation\RequestGetMapping;
use Nacosvel\Feign\Contracts\ServiceInterface;
use Nacosvel\Feign\Contracts\TransformationInterface;

#[FeignClient(name: 'debug', path: '/')]
interface PostInterface
{
    #[RequestGetMapping(path: '/get')]
    public function detail(string $name = '', string $version = ''): Post|ServiceInterface|TransformationInterface;

}
```

## Advanced Usage

### Step 1 : Construct a [FeignRegistrar](src/FeignRegistrar.php) instance and set a custom [Configuration](src/Configuration/Configuration.php) class.

```php
use Nacosvel\Feign\Annotation\EnableFeignClients;
use Nacosvel\Feign\FeignConfiguration;
use Nacosvel\Feign\FeignRegistrar;

#[EnableFeignClients(FeignConfiguration::class)]
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FeignRegistrar::builder($this->app);
    }

}
```

### Step 2 : Inject properties and add [Middleware](src/Annotation/Middleware.php).

```php
use Nacosvel\Feign\Annotation\Autowired;
use Nacosvel\Feign\Annotation\Middleware;
use Nacosvel\Feign\Annotation\RequestMiddleware;
use Nacosvel\Feign\Annotation\ResponseMiddleware;
use Nacosvel\Feign\Contracts\AutowiredInterface;

class PostController implements AutowiredInterface
{
    #[Autowired]
    #[Middleware(Request::class, Response::class)]
    #[RequestMiddleware(Request::class)]
    #[ResponseMiddleware(Response::class)]
    protected PostInterface|AutowiredInterface $post;

    public function index(): string
    {
        return $this->post->detail(name: 'nacosvel/feign', version: 'v1.0.0')->getRawContents();
    }

}
```

### Step 3 : More configurations for defining interfaces.

```php
use Nacosvel\Feign\Annotation\FeignClient;
use Nacosvel\Feign\Annotation\RequestAttribute;
use Nacosvel\Feign\Annotation\RequestGetMapping;
use Nacosvel\Feign\Annotation\Middleware;
use Nacosvel\Feign\Annotation\RequestMiddleware;
use Nacosvel\Feign\Annotation\ResponseMiddleware;
use Nacosvel\Feign\Configuration\Client;
use Nacosvel\Feign\Configuration\Configuration;
use Nacosvel\Feign\Configuration\Fallback;
use Nacosvel\Feign\Support\Service;

#[FeignClient(
    name: 'debug',
    url: 'https://httpbin.org/',
    path: '/',
    configuration: Configuration::class,
    fallback: Fallback::class,
    client: Client::class
)]
#[Middleware(Request::class, Response::class)]
#[RequestMiddleware(Request::class)]
#[ResponseMiddleware(Response::class)]
interface PostInterface
{
    #[RequestGetMapping(path: '/get?id={id}', params: 'id=123456')]
    #[RequestAttribute(value: RequestAttribute::QUERY)]
    #[Middleware(Request::class, Response::class)]
    #[RequestMiddleware(Request::class)]
    #[ResponseMiddleware(Response::class)]
    public function detail(string $name = '', string $version = ''): Post|Service;

}
```

- To add custom [Configuration](src/Configuration/Configuration.php), [Fallback](src/Configuration/Fallback.php),
  and [Client](src/Configuration/Client.php) settings for FeignClient;
- using [Middleware](src/Annotation/Middleware.php)
- and specify [request data attributes](src/Annotation/RequestAttribute.php)

## Advanced Example

This implementation enhances Feign to support remote microservice calls without relying on defined interfaces. This
feature allows for more flexible remote service invocation where you don't need to pre-define interfaces for each
service.

```php
use Nacosvel\Feign\Annotation\Autowired;
use Nacosvel\Feign\Annotation\RequestGetMapping;
use Nacosvel\Feign\Contracts\AutowiredInterface;
use Nacosvel\Feign\Contracts\ReflectiveInterface;

class PostController implements AutowiredInterface
{
    #[Autowired]
    #[FeignClient(name: 'debug', url: 'https://httpbin.org/', path: '/')]
    #[RequestGetMapping(path: '/uuid')]
    protected ReflectiveInterface $post;

    public function index(): string
    {
        return $this->post->uuid()->getRawContents();
    }

}
```

## Documentation

### Configuration Reference

```php
public function getDefaultMethod(): string;
```

Get the default request method, which takes effect when not specified.

```php
public function converters(): array;
```

Overriding this method can achieve custom response types.

```php
public function getService(?string $name = null): string|array|null;
```

Randomly select a service from multiple service addresses.

### Fallback Reference

```php
public function __invoke(
        RequestInterface  $request,
        ResponseInterface $response,
        array             $options = [],
        Throwable         $previous = null
    ): ResponseInterface;
```

Exception responses can be uniformly handled here.

### Client Reference

```php
public function __invoke(): ClientInterface;
```

For a custom request instance, you need to return an instance of `GuzzleHttp\ClientInterface`.

## License

Nacosvel Feign is made available under the MIT License (MIT). Please see [License File](LICENSE) for more information.
