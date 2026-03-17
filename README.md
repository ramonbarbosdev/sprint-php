# SprintPHP

**SprintPHP** é um micro-framework PHP, inspirado em **Spring Boot, Laravel e NestJS**, focado em:

* Alta produtividade
* Arquitetura modular
* Uso de Attributes (anotações)
* Middleware por rota
* Auto-discovery de controllers
* Geração automática de documentação (OpenAPI / Swagger)

---

# Começando rápido (Quick Start)

Este guia cria uma API funcional em menos de 5 minutos.

## 1. Instalar o framework

```bash
composer require ramoncode/sprint-php
```

---

## 2. Criar estrutura básica

```bash
project/
│
├── app/
│   └── Api/
│       ├── Controller/
│       │   └── TestController.php
│       └── Kernel/
│           └── ApiKernel.php
│
├── config/
│   └── database.php
│
├── api.php
│
├── vendor/
└── composer.json
```

---

## 3. Criar o entrypoint da aplicação

### `api.php`

```php
use SprintPHP\Core\Application;
use App\Api\Kernel\ApiKernel;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->useKernel(new ApiKernel());

$app->run();
```

---

## 4. Criar o Kernel

O Kernel é o **coração da aplicação**. Ele define:

* bootstrap (configurações)
* controllers
* middlewares

### `app/Api/Kernel/ApiKernel.php`

```php
namespace App\Api\Kernel;

use SprintPHP\Core\BaseKernel;

class ApiKernel extends BaseKernel
{
    protected function bootstrap(): void
    {
        // Configurações iniciais (DB, env, etc)
        require_once __DIR__ . '/../../../config/database.php';
    }

    protected function registerControllers(): void
    {
        // Auto-descobre controllers
        $this->scanControllers(__DIR__ . '/../Controller');
    }

    protected function registerMiddlewares(): void
    {
        // Exemplo:
        // $this->app->use(AuthMiddleware::class);
    }
}
```

---

## 5. Criar seu primeiro Controller

### `app/Api/Controller/TestController.php`

```php
namespace App\Api\Controller;

use SprintPHP\Attributes\Controller;
use SprintPHP\Attributes\Get;

#[Controller('/test')]
class TestController
{
    #[Get('')]
    public function index()
    {
        return ["message" => "SprintPHP funcionando"];
    }
}
```

---

## 6. Rodar o servidor

```bash
php -S localhost:8000 -t public
```

Acesse:

```
http://localhost:8000/test
```

Resposta esperada:

```json
{
  "success": true,
  "data": {
    "message": "SprintPHP funcionando"
  }
}
```

---

# Como o framework funciona

Fluxo interno:

```text
Request
 → Application
 → Kernel
 → Router
 → Middleware
 → Controller
 → Response
```

---

# Controllers

Controllers usam **Attributes** para definir rotas.

```php
#[Controller('/users')]
class UserController
{
    #[Get('')]
    public function list()
    {
        return [];
    }

    #[Get('/{id}')]
    public function show(int $id)
    {
        return ["id" => $id];
    }
}
```

---

# Middleware

## Criando um middleware

```php
use SprintPHP\Contracts\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        // validar token
    }
}
```

---

## Usando em uma rota

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

## Middleware global

```php
protected function registerMiddlewares(): void
{
    $this->app->use(AuthMiddleware::class);
}
```

---

# Rotas públicas

Ignora middleware global:

```php
use SprintPHP\Attributes\PublicRoute;

#[Get('/docs')]
#[PublicRoute]
public function docs()
{
    return [];
}
```

---

# Binding automático (Request → parâmetros)

SprintPHP injeta automaticamente dados da request:

| Tipo  | Origem              |
| ----- | ------------------- |
| Param | URL (`/users/{id}`) |
| Query | `?page=1`           |
| Body  | JSON                |

---

## Exemplo

```php
use SprintPHP\Attributes\Param;

#[Get('/user/{id}')]
public function show(#[Param] int $id)
{
    return ["id" => $id];
}
```

---

# Validação automática

```php
use SprintPHP\Attributes\Min;
use SprintPHP\Attributes\Max;

public function list(
    #[Min(1)] int $page,
    #[Max(100)] int $limit
) {}
```

Se inválido → erro automático na resposta.

---

# Responses

## Padrão (JSON estruturado)

```php
return ["data" => "ok"];
```

Saída:

```json
{
  "success": true,
  "data": {
    "data": "ok"
  }
}
```

---

## RAW (sem wrapper)

Usado para:

* Swagger
* arquivos
* responses customizadas

```php
use SprintPHP\Http\Response;

Response::raw($data);
```

---

# Swagger / OpenAPI

## Controller de documentação

```php
#[Controller('/docs')]
class SwaggerController
{
    #[Get('')]
    #[PublicRoute]
    public function docs()
    {
        return Response::raw(
            OpenApiGenerator::generate()
        );
    }
}
```

---

# Estrutura recomendada (produção)

```bash
app/
 ├── Api/
 │   ├── Controller/
 │   ├── Middleware/
 │   ├── DTO/
 │   ├── Service/
 │   └── Kernel/
```

---

# Integração com banco

Exemplo:

```php
// config/database.php
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([...]);
$capsule->bootEloquent();
```

---

# Boas práticas

* Use DTOs para entrada de dados
* Separe lógica em Services
* Use middleware para autenticação
* Evite lógica pesada em controllers

---

# Filosofia

```text
simplicidade + produtividade + arquitetura limpa
```

---

# Autor

Ramon
Desenvolvedor de Sistemas

---

# Licença

MIT
