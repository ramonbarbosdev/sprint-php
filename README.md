# SprintPHP

**SprintPHP** é um micro-framework PHP moderno, inspirado em **Spring Boot, Laravel e NestJS**, focado em:

* Alta produtividade
* Arquitetura modular
* Uso de Attributes (anotações)
* Middleware por rota
* Auto-discovery de controllers
* Geração automática de documentação (OpenAPI / Swagger)

---

# Instalação

Via Composer:

```bash
composer require ramoncode/sprint-php
```

---

# Estrutura recomendada

```bash
project/
│
├── app/
│   └── Api/
│       ├── Controller/
│       ├── Kernel/
│       │   └── ApiKernel.php
│       └── Docs/
│
├── config/
│   └── database.php
│
├── api.php
│   
│
├── vendor/
└── composer.json
```

---

# Inicialização

## `api.php`

```php
use SprintPHP\Core\Application;
use App\Api\Kernel\ApiKernel;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->useKernel(new ApiKernel());

$app->run();
```

---

# Kernel (ponto central da aplicação)

## `ApiKernel.php`

```php
namespace App\Api\Kernel;

use SprintPHP\Core\BaseKernel;

class ApiKernel extends BaseKernel
{
    protected function bootstrap(): void
    {
        require_once __DIR__ . '/../../../config/database.php';
    }

    protected function registerControllers(): void
    {
        $this->scanControllers(__DIR__ . '/../Controller');
    }

    protected function registerMiddlewares(): void
    {
        // Middlewares globais (opcional)
        // $this->app->use(ApiAuthMiddleware::class);
    }
}
```

---

# Controllers

## Exemplo básico

```php
use SprintPHP\Attributes\Controller;
use SprintPHP\Attributes\Get;

#[Controller('/test')]
class TestController
{
    #[Get('')]
    public function index()
    {
        return ["ok" => true];
    }
}
```

---

# Middleware

## Interface

```php
namespace SprintPHP\Contracts;

interface MiddlewareInterface
{
    public function handle(): void;
}
```

---

## Exemplo

```php
use SprintPHP\Contracts\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        // valida token
    }
}
```

---

## Uso por rota

```php
use SprintPHP\Attributes\Middleware;

#[Get('/secure')]
#[Middleware(AuthMiddleware::class)]
public function secure()
{
    return ["secure" => true];
}
```

---

# Rotas públicas

Para ignorar middleware global:

```php
use SprintPHP\Attributes\PublicRoute;

#[Get('/docs')]
#[PublicRoute]
public function docs()
{
    return [...];
}
```

---

# Injeção de dados (Request Binding)

SprintPHP faz binding automático de:

| Tipo  | Fonte   |
| ----- | ------- |
| Param | URL     |
| Query | ?param= |
| Body  | JSON    |

---

## Exemplo

```php
#[Get('/user/{id}')]
public function show(#[Param] int $id)
{
    return ["id" => $id];
}
```

---

# Validação automática

Suporte a:

```php
#[Min(1)]
#[Max(100)]
#[Required]
```

Exemplo:

```php
public function index(#[Min(1)] int $page)
```

---

# Swagger / OpenAPI

## Endpoint

```php
#[Controller('/docs')]
class SwaggerController
{
    #[Get('')]
    #[PublicRoute]
    public function docs()
    {
        return OpenApiGenerator::generate(...);
    }
}
```

---

## IMPORTANTE

Swagger exige resposta **RAW (sem wrapper)**:

```php
Response::raw(OpenApiGenerator::generate(...));
```

---

# Response

## Padrão

```php
return ["data" => "ok"];
```

Retorna:

```json
{
  "success": true,
  "data": {
    "data": "ok"
  }
}
```

---

## RAW (para Swagger / arquivos)

```php
Response::raw($data);
```

---

# Fluxo da aplicação

```text
Request
 → Application
 → Kernel (boot)
 → Router
 → Middleware
 → Controller
 → Response
```

---

# Recursos principais

* ✔ Attribute Routing
* ✔ Middleware por rota
* ✔ Middleware global
* ✔ DTO Binding automático
* ✔ Validação automática
* ✔ OpenAPI Generator
* ✔ Arquitetura modular
* ✔ Compatível com Eloquent ORM

---

# Roadmap

* [ ] Dependency Injection (DI Container)
* [ ] Service Providers
* [ ] Modules (como Spring)
* [ ] Cache Layer
* [ ] Event System
* [ ] CLI (artisan-like)

---

# Filosofia

SprintPHP foi criado para:

```text
simplicidade + poder + arquitetura limpa
```

Inspirado em:

* Spring Boot
* Laravel
* NestJS

---

# Autor

Ramon
Desenvolvedor de Sistemas

---

# 📄 Licença

MIT
