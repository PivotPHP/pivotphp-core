# Guia do Objeto Request

O objeto `Request` representa a requisição HTTP recebida com **suporte híbrido PSR-7**. Ele fornece acesso a parâmetros de rota, query string, corpo da requisição, cabeçalhos e arquivos, mantendo compatibilidade total com Express.js e implementando completamente a interface PSR-7 `ServerRequestInterface`.

## 🔄 Compatibilidade Híbrida

O Request PivotPHP oferece:
- ✅ **API Express.js** completa para facilidade de uso
- ✅ **Interface PSR-7** completa para compatibilidade com middleware PSR-15
- ✅ **Lazy Loading** para performance otimizada
- ✅ **Object Pooling** para melhor utilização de memória
- ✅ **Imutabilidade** respeitando padrões PSR-7

## Estrutura do Request

### Propriedades Principais

- **method**: Método HTTP (GET, POST, PUT, DELETE, etc.)
- **path**: Padrão da rota definida (ex: `/users/:id`)
- **pathCallable**: Caminho real da requisição (ex: `/users/123`)
- **params**: Parâmetros extraídos da URL
- **query**: Parâmetros da query string
- **body**: Corpo da requisição (JSON ou form data)
- **headers**: Cabeçalhos HTTP
- **files**: Arquivos enviados via upload

### Propriedades Dinâmicas

O Request suporta propriedades dinâmicas, úteis para middleware:

```php
// Middleware pode adicionar propriedades
$app->use(function($req, $res, $next) {
    $req->user = authenticate($req);
    $req->timestamp = time();
    return $next();
});

// Usar na rota
$app->get('/profile', function($req, $res) {
    return $res->json([
        'user' => $req->user,
        'timestamp' => $req->timestamp
    ]);
});
```

## 🔄 Usando PSR-7 (ServerRequestInterface)

O Request implementa completamente a interface PSR-7, permitindo uso com middleware PSR-15:

```php
use Psr\Http\Message\ServerRequestInterface;

function myMiddleware(ServerRequestInterface $request, $response, $next) {
    // Métodos PSR-7 padrão
    $method = $request->getMethod();
    $uri = $request->getUri();
    $headers = $request->getHeaders();
    $body = $request->getBody();
    
    // Atributos PSR-7 (imutável)
    $newRequest = $request->withAttribute('middleware_processed', true);
    $user = $newRequest->getAttribute('user');
    
    return $next($newRequest, $response);
}

// Usar com middleware PSR-15
$app->use(myMiddleware);
```

### Imutabilidade PSR-7

Métodos `with*()` retornam **nova instância** respeitando imutabilidade:

```php
$request1 = new Request('GET', '/api/users', '/api/users');
$request2 = $request1->withAttribute('user_id', 123);
$request3 = $request2->withHeader('X-Custom', 'value');

// $request1, $request2, $request3 são objetos DIFERENTES
// Imutabilidade garantida - nenhum objeto original é modificado
```

### Lazy Loading PSR-7

O objeto PSR-7 interno é criado apenas quando necessário:

```php
$request = new Request('GET', '/api/users', '/api/users');
// ✅ Rápido - sem PSR-7 criado ainda

$request->param('id');     // ✅ Express.js - sem PSR-7
$request->ip();           // ✅ Express.js - sem PSR-7

$request->getMethod();    // ✅ PSR-7 criado agora (lazy loading)
$request->getHeaders();   // ✅ Reutiliza PSR-7 já criado
```

### Object Pooling

Use a factory otimizada para melhor performance:

```php
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

// Configurar pooling
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
]);

// Criar requests com pooling
$request = OptimizedHttpFactory::createRequest('GET', '/api/users', '/api/users');
// Objetos PSR-7 internos são reutilizados automaticamente
```

## Acessando Parâmetros de Rota

### Método `param(string $key, $default = null)`

```php
// Rota: /users/:id/posts/:postId
$app->get('/users/:id/posts/:postId', function($req, $res) {
    $userId = $req->param('id'); // string ou int
    $postId = $req->param('postId');
    $version = $req->param('version', 'v1'); // com valor padrão

    return $res->json([
        'userId' => $userId,
        'postId' => $postId,
        'version' => $version
    ]);
});
```

### Conversão Automática de Tipos

```php
// URL: /users/123/posts/456
$app->get('/users/:id/posts/:postId', function($req, $res) {
    $userId = $req->param('id');    // 123 (int)
    $postId = $req->param('postId'); // 456 (int)

    // Valores numéricos são automaticamente convertidos para int
    var_dump(is_int($userId)); // true
});
```

## Acessando Query String

### Método `get(string $key, $default = null)`

```php
// URL: /search?q=express&page=2&limit=10&sort=name
$app->get('/search', function($req, $res) {
    $query = $req->get('q');          // 'express'
    $page = $req->get('page', 1);     // 2 (ou 1 se não fornecido)
    $limit = $req->get('limit', 20);  // 10 (ou 20 se não fornecido)
    $sort = $req->get('sort');        // 'name'

    return $res->json([
        'query' => $query,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit
        ],
        'sort' => $sort
    ]);
});
```

### Acessando Todos os Query Parameters

```php
$app->get('/api/users', function($req, $res) {
    // Obter objeto completo dos query params
    $allParams = $req->query;

    // Verificar se um parâmetro existe
    if (property_exists($req->query, 'filter')) {
        $filter = $req->query->filter;
    }

    return $res->json($allParams);
});
```

## Acessando Corpo da Requisição

### Método `input(string $key, $default = null)`

```php
$app->post('/users', function($req, $res) {
    $name = $req->input('name');
    $email = $req->input('email');
    $age = $req->input('age', 18); // com valor padrão

    // Validação básica
    if (!$name || !$email) {
        return $res->status(400)->json([
            'error' => 'Name and email are required'
        ]);
    }

    return $res->json([
        'name' => $name,
        'email' => $email,
        'age' => (int)$age
    ]);
});
```

### Acessando Todo o Corpo

```php
$app->post('/api/data', function($req, $res) {
    // Objeto completo do corpo
    $data = $req->body;

    // JSON enviado
    // {"user": {"name": "João", "email": "joao@test.com"}, "action": "create"}

    $user = $data->user ?? null;
    $action = $data->action ?? 'unknown';

    return $res->json([
        'received' => $data,
        'user' => $user,
        'action' => $action
    ]);
});
```

> **Método `body()`**: além do acesso por propriedade (`$req->body`), também existe o
> método Express.js `$req->body()`, que retorna o corpo como `stdClass` — um alias de
> `getBodyAsStdClass()`.

### Trabalhando com JSON Complexo

```php
$app->post('/orders', function($req, $res) {
    // JSON: {"customer": {"id": 1}, "items": [{"id": 1, "qty": 2}]}

    $customer = $req->input('customer');
    $items = $req->input('items', []);

    // Validação de estrutura
    if (!is_object($customer) || !isset($customer->id)) {
        return $res->status(400)->json(['error' => 'Invalid customer data']);
    }

    if (!is_array($items) || empty($items)) {
        return $res->status(400)->json(['error' => 'Items are required']);
    }

    return $res->json([
        'customer_id' => $customer->id,
        'item_count' => count($items),
        'total_qty' => array_sum(array_column($items, 'qty'))
    ]);
});
```

## Trabalhando com Arquivos

### Método `file(string $key)`

```php
$app->post('/upload', function($req, $res) {
    $uploadedFile = $req->file('document');

    if (!$uploadedFile) {
        return $res->status(400)->json(['error' => 'No file uploaded']);
    }

    return $res->json([
        'filename' => $uploadedFile['name'],
        'type' => $uploadedFile['type'],
        'size' => $uploadedFile['size'],
        'tmp_name' => $uploadedFile['tmp_name']
    ]);
});
```

### Método `hasFile(string $key)`

```php
$app->post('/profile/avatar', function($req, $res) {
    if (!$req->hasFile('avatar')) {
        return $res->status(400)->json(['error' => 'Avatar file is required']);
    }

    $avatar = $req->file('avatar');

    // Validações
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($avatar['type'], $allowedTypes)) {
        return $res->status(400)->json(['error' => 'Invalid file type']);
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($avatar['size'] > $maxSize) {
        return $res->status(400)->json(['error' => 'File too large']);
    }

    // Processar upload
    $uploadPath = '/uploads/' . uniqid() . '_' . $avatar['name'];

    if (move_uploaded_file($avatar['tmp_name'], $uploadPath)) {
        return $res->json(['message' => 'File uploaded', 'path' => $uploadPath]);
    }

    return $res->status(500)->json(['error' => 'Upload failed']);
});
```

### Upload Múltiplo

```php
$app->post('/gallery', function($req, $res) {
    $uploaded = [];

    // HTML: <input type="file" name="images[]" multiple>
    $images = $_FILES['images'] ?? [];

    if (empty($images['name'])) {
        return $res->status(400)->json(['error' => 'No images uploaded']);
    }

    for ($i = 0; $i < count($images['name']); $i++) {
        if ($images['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $images['name'][$i],
                'type' => $images['type'][$i],
                'size' => $images['size'][$i],
                'tmp_name' => $images['tmp_name'][$i]
            ];

            // Processar cada arquivo
            $uploaded[] = $file['name'];
        }
    }

    return $res->json(['uploaded' => $uploaded]);
});
```

## Acessando Cabeçalhos

### Objeto `headers`

```php
$app->get('/api/info', function($req, $res) {
    // Acessar cabeçalhos específicos
    $contentType = $req->headers->get('Content-Type');
    $userAgent = $req->headers->get('User-Agent');
    $authorization = $req->headers->get('Authorization');

    // Com valor padrão
    $apiVersion = $req->headers->get('X-API-Version', 'v1');

    return $res->json([
        'content_type' => $contentType,
        'user_agent' => $userAgent,
        'has_auth' => !empty($authorization),
        'api_version' => $apiVersion
    ]);
});
```

### Cabeçalhos de Autenticação

```php
$app->middleware('auth', function($req, $res, $next) {
    $auth = $req->headers->get('Authorization');

    if (!$auth || !str_starts_with($auth, 'Bearer ')) {
        return $res->status(401)->json(['error' => 'Token required']);
    }

    $token = substr($auth, 7); // Remove "Bearer "
    $user = validateToken($token);

    if (!$user) {
        return $res->status(401)->json(['error' => 'Invalid token']);
    }

    $req->user = $user;
    return $next();
});
```

## Métodos Utilitários

### `ip()` - Obter IP do Cliente

```php
$app->get('/api/location', function($req, $res) {
    $clientIp = $req->ip();

    // Considera proxies e load balancers
    // Verifica headers como X-Forwarded-For, X-Real-IP, etc.

    return $res->json([
        'ip' => $clientIp,
        'message' => "Request from {$clientIp}"
    ]);
});
```

### `userAgent()` - Obter User-Agent

```php
$app->get('/api/device', function($req, $res) {
    $userAgent = $req->userAgent();

    // Detecção básica de dispositivo
    $isMobile = str_contains(strtolower($userAgent), 'mobile');
    $isBot = str_contains(strtolower($userAgent), 'bot');

    return $res->json([
        'user_agent' => $userAgent,
        'is_mobile' => $isMobile,
        'is_bot' => $isBot
    ]);
});
```

## Padrões de Uso Avançados

### Validação de Entrada

```php
function validateRequest($req, $rules) {
    $errors = [];

    foreach ($rules as $field => $rule) {
        $value = $req->input($field);

        if ($rule['required'] && empty($value)) {
            $errors[$field] = "{$field} is required";
        }

        if (!empty($value) && isset($rule['type'])) {
            switch ($rule['type']) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "{$field} must be a valid email";
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        $errors[$field] = "{$field} must be numeric";
                    }
                    break;
            }
        }
    }

    return $errors;
}

$app->post('/users', function($req, $res) {
    $errors = validateRequest($req, [
        'name' => ['required' => true],
        'email' => ['required' => true, 'type' => 'email'],
        'age' => ['type' => 'numeric']
    ]);

    if (!empty($errors)) {
        return $res->status(422)->json(['errors' => $errors]);
    }

    // Processar dados válidos
});
```

### Paginação e Filtros

```php
$app->get('/api/users', function($req, $res) {
    // Parâmetros de paginação
    $page = max(1, (int)$req->get('page', 1));
    $limit = min(100, max(1, (int)$req->get('limit', 20)));
    $offset = ($page - 1) * $limit;

    // Filtros
    $search = $req->get('search');
    $status = $req->get('status');
    $sortBy = $req->get('sort', 'id');
    $sortOrder = strtolower($req->get('order', 'asc')) === 'desc' ? 'DESC' : 'ASC';

    // Construir query
    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(name LIKE ? OR email LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    return $res->json([
        'data' => getUsersWithPagination($whereClause, $params, $sortBy, $sortOrder, $limit, $offset),
        'meta' => [
            'page' => $page,
            'limit' => $limit,
            'total' => getTotalUsers($whereClause, $params)
        ]
    ]);
});
```

### Transformação de Dados

```php
$app->post('/api/orders', function($req, $res) {
    // Transformar estrutura de entrada
    $data = $req->body;

    $order = [
        'customer_id' => $data->customer->id ?? null,
        'total' => 0,
        'items' => []
    ];

    if (isset($data->items) && is_array($data->items)) {
        foreach ($data->items as $item) {
            $orderItem = [
                'product_id' => $item->product_id ?? null,
                'quantity' => max(1, (int)($item->quantity ?? 1)),
                'price' => (float)($item->price ?? 0)
            ];

            $orderItem['subtotal'] = $orderItem['quantity'] * $orderItem['price'];
            $order['total'] += $orderItem['subtotal'];
            $order['items'][] = $orderItem;
        }
    }

    return $res->json(['transformed_order' => $order]);
});
```

## Debugging e Logging

### Inspecionar Request

```php
$app->use(function($req, $res, $next) {
    // Log detalhado da requisição
    error_log("Request Debug: " . json_encode([
        'method' => $req->method,
        'path' => $req->pathCallable,
        'params' => $req->params,
        'query' => $req->query,
        'body' => $req->body,
        'ip' => $req->ip(),
        'user_agent' => $req->userAgent()
    ]));

    return $next();
});
```

### Sanitização de Logs

```php
function sanitizeForLog($data) {
    $sensitive = ['password', 'token', 'secret', 'key'];

    foreach ($sensitive as $field) {
        if (property_exists($data, $field)) {
            $data->$field = '[REDACTED]';
        }
    }

    return $data;
}

$app->use(function($req, $res, $next) {
    $logData = sanitizeForLog(clone $req->body);
    error_log("Request body: " . json_encode($logData));
    return $next();
});
```

O objeto Request é a interface principal para acessar dados da requisição HTTP. Use os métodos apropriados para cada tipo de dado e sempre valide e sanitize as entradas dos usuários.
