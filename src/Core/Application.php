<?php

declare(strict_types=1);

namespace PivotPHP\Core\Core;

use PivotPHP\Http\BodyParser;
use PivotPHP\Http\Emitter\SapiEmitter;
use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;
use PivotPHP\Http\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use PivotPHP\Routing\Router\Router;
use PivotPHP\Routing\Router\StaticFileManager;
use PivotPHP\Core\Utils\CallableResolver;
use PivotPHP\Core\Middleware\MiddlewareStack;
use PivotPHP\Core\Exceptions\HttpException;
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;
use PivotPHP\Core\Providers\Container;
use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Providers\ContainerServiceProvider;
use PivotPHP\Core\Providers\EventServiceProvider;
use PivotPHP\Core\Providers\LoggingServiceProvider;
use PivotPHP\Core\Providers\HookServiceProvider;
use PivotPHP\Core\Providers\ExtensionServiceProvider;
use PivotPHP\Core\Providers\RoutingServiceProvider;
use PivotPHP\Core\Support\HookManager;
use PivotPHP\Core\Events\ApplicationStarted;
use PivotPHP\Core\Events\ListenerProvider as EventsListenerProvider;
use PivotPHP\Core\Events\RequestReceived;
use PivotPHP\Core\Events\ResponseSent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Classe principal da aplicação PivotPHP.
 *
 * Gerencia o ciclo de vida da aplicação, incluindo:
 * - Inicialização e configuração
 * - Roteamento de requisições
 * - Execução de middlewares
 * - Tratamento de erros
 * - Resposta HTTP
 */
class Application implements ApplicationInterface
{
    /**
     * Versão do framework.
     */
    public const VERSION = '4.1.0';

    /**
     * Container de dependências PSR-11.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Configurações da aplicação.
     *
     * @var Config
     */
    protected Config $config;

    /**
     * Router da aplicação.
     *
     * @var Router
     */
    protected Router $router;

    /**
     * Stack de middlewares globais.
     *
     * @var MiddlewareStack
     */
    protected MiddlewareStack $middlewares;

    /**
     * Providers de serviços registrados.
     *
     * @var array<ServiceProvider>
     */
    protected array $serviceProviders = [];

    /**
     * Classes de providers para serem registrados.
     *
     * @var array<string>
     */
    protected array $providers = [
        ContainerServiceProvider::class,
        EventServiceProvider::class,
        LoggingServiceProvider::class,
        HookServiceProvider::class,
        ExtensionServiceProvider::class,
        RoutingServiceProvider::class,
    ];

    /**
     * Middleware aliases mapping
     *
     * @var array<string, string>
     */
    protected array $middlewareAliases = [];

    /**
     * Indica se a aplicação foi inicializada.
     *
     * @var bool
     */
    protected bool $booted = false;

    /**
     * URL base da aplicação.
     */
    protected ?string $baseUrl = null;

    /**
     * Tempo de início da aplicação.
     */
    protected \DateTime $startTime;

    /**
     * Lista de listeners PSR-14 registrados.
     * @var array<string, array<int, callable>>
     */
    protected array $registeredListeners = [];

    /**
     * Construtor da aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     */
    public function __construct(?string $basePath = null)
    {
        $this->startTime = new \DateTime();
        $this->container = new Container();
        $this->registerBaseBindings();

        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerCoreServices();

        // Core providers (container, events, logging, hooks, extensions, routing) are registered
        // here so listeners, hooks and extensions can be added before boot(); boot() only boots
        // them and registers the providers listed in configuration.
        foreach ($this->providers as $provider) {
            $this->register($provider);
        }

        // Configurar error handling o mais cedo possível
        $this->configureBasicErrorHandling();
    }

    /**
     * Registra bindings básicos no container.
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        $this->container->instance(Application::class, $this);
        $this->container->alias('app', Application::class);
    }

    /**
     * Registra serviços core da aplicação.
     *
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // Configuração
        $this->config = new Config();
        $this->container->instance(Config::class, $this->config);
        $this->container->alias('config', Config::class);

        // Router
        // O Router do core-routing usa estado estático; limpa rotas de instâncias
        // anteriores para que cada Application comece isolada (SPEC-076).
        Router::clear();
        $this->router = new Router();
        $this->container->instance(Router::class, $this->router);
        $this->container->alias('router', Router::class);

        // Middleware Stack
        $this->middlewares = new MiddlewareStack();
        $this->container->instance(MiddlewareStack::class, $this->middlewares);
        $this->container->alias('middleware', MiddlewareStack::class);

        // Padronizar alias para hooks
        $this->alias('hooks', HookManager::class);
    }

    /**
     * Define o caminho base da aplicação.
     *
     * @param  string $basePath Caminho base
     * @return $this
     */
    public function setBasePath(string $basePath): self
    {
        $this->container->instance('path.base', rtrim($basePath, '\/'));
        $this->container->instance('path.config', $this->basePath('config'));
        $this->container->instance('path.storage', $this->basePath('storage'));
        $this->container->instance('path.public', $this->basePath('public'));
        $this->container->instance('path.logs', $this->basePath('logs'));

        return $this;
    }

    /**
     * Obtém um caminho relativo ao base path.
     *
     * @param  string $path Caminho relativo
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        $basePath = $this->container->has('path.base') ? $this->container->get('path.base') : null;
        if (!is_string($basePath)) {
            $basePath = getcwd() ?: '';
        }

        return $basePath . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Inicializa a aplicação.
     *
     * @return $this
     */
    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        // Carregar configurações
        $this->loadConfiguration();

        // Registrar providers de serviços
        $this->registerServiceProviders();

        // Fazer boot dos providers
        $this->bootServiceProviders();

        // Configurar error handling
        $this->configureErrorHandling();

        // Carregar middlewares padrão
        $this->loadDefaultMiddlewares();

        $this->booted = true;

        // Disparar evento de boot da aplicação
        $this->dispatchEvent(new ApplicationStarted($this->startTime, $this->config->all()));

        return $this;
    }

    /**
     * Carrega configurações da aplicação.
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        // O .env vem primeiro: os arquivos de config/ leem $_ENV/getenv() ao serem avaliados
        // (SPEC-101). Variáveis já definidas no ambiente real têm precedência sobre o .env.
        $envFile = $this->basePath('.env');
        if (file_exists($envFile)) {
            $this->config->loadEnvironment($envFile);
        }

        $configPath = $this->container->has('path.config') ? $this->container->get('path.config') : null;

        if (is_string($configPath) && is_dir($configPath)) {
            $this->config->setConfigPath($configPath)->loadAll();
        }
    }

    /**
     * Registra service providers.
     *
     * @return void
     */
    protected function registerServiceProviders(): void
    {
        // Registrar providers básicos
        foreach ($this->providers as $provider) {
            $this->register($provider);
        }

        // Registrar providers adicionais do config
        $configProviders = $this->config->get('app.providers', []);
        if (is_array($configProviders)) {
            foreach ($configProviders as $provider) {
                if (is_string($provider)) {
                    $this->register($provider);
                }
            }
        }
    }

    /**
     * Faz boot dos service providers.
     *
     * @return void
     */
    protected function bootServiceProviders(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Configura o reporte básico de erros no construtor (os handlers globais
     * são instalados apenas em run()).
     *
     * @return void
     */
    protected function configureBasicErrorHandling(): void
    {
        // Configuração básica de erro que funciona mesmo sem config carregado
        error_reporting(E_ALL);
        ini_set('log_errors', '1');
        ini_set('display_errors', '0');
    }

    /**
     * Configura tratamento de erros com base na configuração.
     *
     * @return void
     */
    protected function configureErrorHandling(): void
    {
        $debug = $this->config->get('app.debug', false);

        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('log_errors', '1');
        } else {
            error_reporting(E_ALL); // Manter ativo para logs
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }
    }

    /**
     * Handler registrado via set_exception_handler() para exceções que escapam
     * completamente do fluxo handle()/run() (ex.: erros durante o bootstrap).
     * Único ponto responsável por converter e emitir a resposta de erro nesse
     * cenário — handleException() apenas monta a Response, sem emiti-la.
     *
     * @return void
     */
    public function handleUncaughtException(Throwable $e): void
    {
        (new SapiEmitter())->emit($this->handleException($e));
    }

    /**
     * Carrega middlewares padrão.
     *
     * @return void
     */
    protected function loadDefaultMiddlewares(): void
    {
        $middlewares = $this->config->get('app.middleware', []);

        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                if (is_callable($middleware)) {
                    $this->middlewares->add($this->adaptMiddleware($middleware));
                }
            }
        }
    }

    /**
     * Registra um service provider.
     *
     * @param  string|ServiceProvider $provider Classe ou instância do provider
     * @return $this
     */
    public function register(string|ServiceProvider $provider): self
    {
        $providerClass = is_string($provider) ? $provider : get_class($provider);
        foreach ($this->serviceProviders as $registered) {
            if (get_class($registered) === $providerClass) {
                return $this;
            }
        }

        // Criar instância se necessário
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        // Registrar o provider
        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        if ($provider instanceof ServiceProvider) {
            $this->serviceProviders[] = $provider;
        }

        return $this;
    }

    /**
     * Adiciona um middleware global.
     *
     * @param  mixed $middleware Middleware a ser adicionado
     * @return $this
     */
    public function use(mixed $middleware): self
    {
        // Resolve alias string (alias → class name)
        if (is_string($middleware) && isset($this->middlewareAliases[$middleware])) {
            $middleware = $this->middlewareAliases[$middleware];
        }

        // Resolve class name to instance (via container when available)
        if (is_string($middleware) && class_exists($middleware)) {
            $middleware = $this->container->has($middleware)
                ? $this->container->get($middleware)
                : new $middleware();
        }

        $this->middlewares->add($this->adaptMiddleware($middleware));

        return $this;
    }

    /**
     * Adapta um middleware (PSR-15 ou callable) para PSR-15.
     *
     * @param  mixed $middleware Middleware a ser adaptado
     * @return MiddlewareInterface
     * @throws \InvalidArgumentException Quando não é adaptável
     */
    private function adaptMiddleware(mixed $middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_callable($middleware)) {
            return new class ($middleware) implements MiddlewareInterface {
                /** @var callable */
                private $next;

                public function __construct(callable $next)
                {
                    $this->next = $next;
                }

                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    $response = new ExpressResponse();
                    $downstream = null;

                    // $next() accepts an optional (modified) request and runs the rest of the
                    // pipeline once; its response is kept even if the middleware does not return it.
                    $next = static function (?ServerRequestInterface $nextRequest = null) use (
                        $handler,
                        $request,
                        &$downstream
                    ): ResponseInterface {
                        return $downstream = $handler->handle($nextRequest ?? $request);
                    };

                    $result = ($this->next)($request, $response, $next);

                    if ($result instanceof ResponseInterface) {
                        return $result;
                    }

                    if ($result instanceof ExpressResponse) {
                        return $result->psr7();
                    }

                    return $downstream ?? $handler->handle($request);
                }
            };
        }

        throw new \InvalidArgumentException('Middleware must be PSR-15 or callable.');
    }

    /**
     * Alias for the use method for middleware registration
     *
     * @param  string|callable|object $middleware Middleware to add
     * @param  array $options Optional configuration for the middleware
     * @return $this
     */
    public function middleware($middleware, array $options = []): self
    {
        // Handle named middleware with options
        if (is_string($middleware) && !empty($options)) {
            // Store options for named middleware
            $this->container->bind("middleware.{$middleware}.options", $options);
        }

        return $this->use($middleware);
    }

    /**
     * Retorna as opções de um middleware registrado por nome.
     *
     * As opções são armazenadas via middleware() quando chamado com o segundo argumento.
     * Retorna null se o middleware não foi registrado ou não possui opções.
     *
     * @param  string $name Nome do middleware
     * @return mixed Opções do middleware ou null se não encontrado
     */
    public function getMiddleware(string $name): mixed
    {
        if ($this->container->has("middleware.{$name}.options")) {
            return $this->container->get("middleware.{$name}.options");
        }
        return null;
    }

    /**
     * Resolve um handler de rota em formato array para a forma executável.
     *
     * Métodos de instância `[Classe::class, 'método']` são resolvidos de forma
     * lazy: a instância é obtida do contêiner a cada requisição (respeitando
     * bind()/singleton()), em vez de no registro — evita erro dependente de ordem
     * e instância compartilhada entre requisições (SPEC-041).
     *
     * @param  callable|array $handler Handler original
     * @return callable|array
     */
    private function resolveHandler(callable|array $handler): callable|array
    {
        if (!is_array($handler) || count($handler) !== 2) {
            return $handler;
        }

        $class = $handler[0] ?? null;
        $method = $handler[1] ?? null;

        if (!is_string($class) || !is_string($method) || !class_exists($class) || !method_exists($class, $method)) {
            return $handler;
        }

        if ((new \ReflectionMethod($class, $method))->isStatic()) {
            return $handler;
        }

        return function (ExpressRequest $request, ExpressResponse $response) use ($class, $method) {
            $instance = $this->container->has($class)
                ? $this->container->get($class)
                : new $class();

            return $instance->{$method}($request, $response);
        };
    }

    /**
     * Registra uma rota GET.
     *
     * @param  string         $path    Caminho da rota
     * @param  callable|array $handler Handler da rota
     * @return $this
     */
    public function get(string $path, callable|array $handler): self
    {
        $this->router->get($path, $this->resolveHandler($handler));
        return $this;
    }

    /**
     * Registra uma rota POST.
     *
     * @param  string         $path    Caminho da rota
     * @param  callable|array $handler Handler da rota
     * @return $this
     */
    public function post(string $path, callable|array $handler): self
    {
        $this->router->post($path, $this->resolveHandler($handler));
        return $this;
    }

    /**
     * Registra uma rota PUT.
     *
     * @param  string         $path    Caminho da rota
     * @param  callable|array $handler Handler da rota
     * @return $this
     */
    public function put(string $path, callable|array $handler): self
    {
        $this->router->put($path, $this->resolveHandler($handler));
        return $this;
    }

    /**
     * Registra uma rota DELETE.
     *
     * @param  string         $path    Caminho da rota
     * @param  callable|array $handler Handler da rota
     * @return $this
     */
    public function delete(string $path, callable|array $handler): self
    {
        $this->router->delete($path, $this->resolveHandler($handler));
        return $this;
    }

    /**
     * Registra uma rota PATCH.
     *
     * @param  string         $path    Caminho da rota
     * @param  callable|array $handler Handler da rota
     * @return $this
     */
    public function patch(string $path, callable|array $handler): self
    {
        $this->router->patch($path, $this->resolveHandler($handler));
        return $this;
    }

    /**
     * Registra arquivos específicos como rotas estáticas.
     *
     * Abordagem direta: registra cada arquivo encontrado como uma rota individual.
     * Exemplo: $app->staticFiles('/public/js', 'src/bundle/js')
     * Resultado: GET /public/js/app.js, GET /public/js/dist/compiled.min.js
     *
     * @param  string $routePrefix Prefixo da rota (ex: '/public/js')
     * @param  string $physicalPath Pasta física (ex: 'src/bundle/js')
     * @param  array  $options Opções adicionais
     * @return $this
     */
    public function staticFiles(
        string $routePrefix,
        string $physicalPath,
        array $options = []
    ): self {
        // Registra cada arquivo encontrado como uma rota individual
        StaticFileManager::registerDirectory($routePrefix, $physicalPath, $options);

        return $this;
    }

    /**
     * Processa uma requisição HTTP.
     *
     * @param  ServerRequestInterface|null $request Requisição (se null, cria automaticamente)
     * @return ResponseInterface
     */
    public function handle(?ServerRequestInterface $request = null): ResponseInterface
    {
        if (!$this->booted) {
            $this->boot();
        }

        $request ??= ServerRequestFactory::fromGlobals();
        $startTime = microtime(true);

        // Disparar evento de requisição recebida
        $this->dispatchLifecycleEvent(new RequestReceived($request, new \DateTime()));

        try {
            // Parsing do corpo dentro do try: corpo malformado (ex.: JSON inválido) vira
            // resposta 400 via HttpExceptionInterface, em vez de escapar de handle().
            $request = (new BodyParser())->parse($request);

            // Executar middlewares globais ENVOLVENDO a resolução de rota, para
            // que middlewares vejam todas as requisições (incl. 404/OPTIONS) e
            // possam responder antes do roteamento (SPEC-040).
            $response = $this->middlewares->execute(
                $request,
                $this->finalHandler()
            );
        } catch (Throwable $e) {
            $response = $this->handleException($e, $request);
        }

        // Disparado uma única vez, com a resposta final (sucesso ou erro) — SPEC-085.
        $processingTime = microtime(true) - $startTime;
        $this->dispatchLifecycleEvent(new ResponseSent($request, $response, new \DateTime(), $processingTime));

        return $response;
    }

    /**
     * Dispara um evento de ciclo de vida (RequestReceived/ResponseSent).
     *
     * Listeners desses eventos observam a requisição (log, métricas, auditoria); uma falha neles é
     * registrada no log e não altera a resposta (SPEC-085). Eventos disparados pela aplicação via
     * dispatchEvent() continuam propagando exceções.
     */
    private function dispatchLifecycleEvent(object $event): void
    {
        try {
            $this->dispatchEvent($event);
        } catch (Throwable $e) {
            $this->logException($e);
        }
    }

    /**
     * Handler final da pipeline: identifica a rota e executa seu handler.
     */
    private function finalHandler(): RequestHandlerInterface
    {
        $final = fn (ServerRequestInterface $request): ResponseInterface => $this->resolveAndExecuteRoute($request);

        return new class ($final) implements RequestHandlerInterface {
            /** @var callable */
            private $final;

            public function __construct(callable $final)
            {
                $this->final = $final;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->final)($request);
            }
        };
    }

    /**
     * Identifica a rota e executa seu handler.
     *
     * @param  ServerRequestInterface $request Requisição
     * @return ResponseInterface
     */
    private function resolveAndExecuteRoute(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        // Encontrar rota
        $route = $this->router::identify($method, $path);

        if (!$route) {
            // SPEC-072: se o path casa com outros métodos, responde 405 (ou 204 p/ OPTIONS) com Allow.
            $allowed = $this->router::allowedMethods($path);

            if ($allowed !== []) {
                $allow = implode(', ', $allowed);

                if ($method === 'OPTIONS') {
                    return (new ExpressResponse())->noContent(204)->withHeader('Allow', $allow);
                }

                return (new ExpressResponse())
                    ->status(405)
                    ->header('Allow', $allow)
                    ->json(['error' => 'Method Not Allowed']);
            }

            // Buscar rotas disponíveis para suggestions
            $availableRoutes = array_map(
                static fn ($r) => "{$r['method']} {$r['path']}",
                array_slice($this->router::getRoutes(), 0, 10)
            );

            throw ContextualException::routeNotFound($method, $path, $availableRoutes);
        }

        // Expor os parâmetros de rota como atributo; a fachada ExpressRequest os lê.
        $matchedParams = $route['matched_params'] ?? [];
        if (is_array($matchedParams) && $matchedParams !== []) {
            $request = $request->withAttribute(ExpressRequest::ROUTE_PARAMS_ATTRIBUTE, $matchedParams);
        }

        $handler = $this->handlerFrom(
            fn (ServerRequestInterface $req): ResponseInterface => $this->callRouteHandler($route, $req)
        );

        // Executar middlewares da rota (os globais já rodaram), se houver.
        $routeMiddlewares = $route['middlewares'] ?? [];

        if (empty($routeMiddlewares)) {
            return $handler->handle($request);
        }

        $stack = new MiddlewareStack();
        foreach ($routeMiddlewares as $middleware) {
            $stack->add($this->adaptMiddleware($middleware));
        }

        return $stack->execute($request, $handler);
    }

    /**
     * Envolve um callable num RequestHandlerInterface PSR-15.
     */
    private function handlerFrom(callable $handler): RequestHandlerInterface
    {
        return new class ($handler) implements RequestHandlerInterface {
            /** @var callable */
            private $handler;

            public function __construct(callable $handler)
            {
                $this->handler = $handler;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->handler)($request);
            }
        };
    }

    /**
     * Executa o handler de uma rota.
     *
     * @param  array<string, mixed>    $route    Dados da rota
     * @param  ServerRequestInterface  $request  Requisição
     * @return ResponseInterface
     */
    protected function callRouteHandler(
        array $route,
        ServerRequestInterface $request
    ): ResponseInterface {
        $handler = $route['handler'];
        $expressRequest = new ExpressRequest($request);
        $expressResponse = new ExpressResponse();

        // Usar CallableResolver para garantir compatibilidade com array callables
        try {
            $result = CallableResolver::call($handler, $expressRequest, $expressResponse);
        } catch (\InvalidArgumentException $e) {
            $handlerInfo = [
                'type' => gettype($handler),
                'class' => is_array($handler) && isset($handler[0]) ?
                    (is_object($handler[0]) ? get_class($handler[0]) : $handler[0]) : 'N/A',
                'method' => is_array($handler) && isset($handler[1]) ? $handler[1] : 'N/A'
            ];

            throw ContextualException::handlerError(
                $handlerInfo['type'],
                $e->getMessage(),
                $handlerInfo
            );
        }

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result instanceof ExpressResponse) {
            return $result->psr7();
        }

        // Sem `return` explícito: usa o estado acumulado na fachada.
        return $expressResponse->psr7();
    }

    /**
     * Bind a service to the container
     */
    public function bind(
        string $abstract,
        mixed $concrete = null,
        bool $shared = false
    ): self {
        $this->container->bind($abstract, $concrete, $shared);
        return $this;
    }

    /**
     * Bind a singleton to the container
     */
    public function singleton(string $abstract, mixed $concrete = null): self
    {
        $this->container->singleton($abstract, $concrete);
        return $this;
    }

    /**
     * Register an existing instance in the container
     */
    public function instance(string $abstract, mixed $instance): self
    {
        $this->container->instance($abstract, $instance);
        return $this;
    }

    /**
     * Resolve a service from the container
     */
    public function make(string $abstract): mixed
    {
        return $this->container->get($abstract);
    }

    /**
     * Resolve a service from the container (alias for make)
     */
    public function resolve(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Check if a service exists in the container (PSR-11)
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Create an alias for a service
     */
    public function alias(string $alias, string $abstract): self
    {
        $this->container->alias($alias, $abstract);
        return $this;
    }

    /**
     * Trata erros PHP.
     *
     * @param  int    $level   Nível
     *                         do erro
     * @param  string $message Mensagem do erro
     * @param  string $file    Arquivo do erro
     * @param  int    $line    Linha do erro
     * @return bool
     */
    public function handleError(
        int $level,
        string $message,
        string $file,
        int $line
    ): bool {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }

        return false;
    }

    /**
     * Trata exceções não capturadas.
     *
     * @param  Throwable                    $e        Exceção
     * @param  ServerRequestInterface|null  $request  Requisição (opcional)
     * @return ResponseInterface
     */
    public function handleException(
        Throwable $e,
        ?ServerRequestInterface $request = null
    ): ResponseInterface {
        $debug = $this->config->get('app.debug', false);
        $statusCode = $this->statusCodeOf($e);
        $response = (new ExpressResponse())->status($statusCode);

        if ($e instanceof HttpException) {
            foreach ($e->getHeaders() as $name => $value) {
                $response->header((string) $name, (string) $value);
            }
        }

        if ($debug) {
            $this->logException($e);

            return $response->json(
                [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }

        // Em produção, gerar ID único para o erro e logar detalhes
        $errorId = uniqid('err_', true);
        $this->logException($e, $errorId);

        return $response->json(
            [
                'error' => true,
                'message' => $this->defaultErrorMessage($statusCode),
                'error_id' => $errorId,
            ]
        );
    }

    private function statusCodeOf(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof \PivotPHP\Http\Exception\HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return 500;
    }

    private function defaultErrorMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            default => 'Internal Server Error',
        };
    }

    /**
     * Registra uma exceção no log usando PSR-3.
     *
     * @param  Throwable $e Exceção
     * @param  string|null $errorId ID único do erro (opcional)
     * @return void
     */
    protected function logException(Throwable $e, ?string $errorId = null): void
    {
        try {
            if ($this->container->has('logger')) {
                $logger = $this->container->get('logger');
                if ($logger instanceof LoggerInterface) {
                    $context = [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'type' => get_class($e)
                    ];

                    if ($errorId) {
                        $context['error_id'] = $errorId;
                    }

                    $logger->error('Exception: {message}', $context);
                    return;
                }
            }
        } catch (\Throwable $loggerError) {
            // Fallback se logger não disponível - usar error_log
            $errorMessage = sprintf(
                '[%s] CRITICAL: %s in %s:%d',
                date('Y-m-d H:i:s'),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            error_log($errorMessage);
            error_log('Logger Error: ' . $loggerError->getMessage());
        }

        // Fallback para error_log
        $message = sprintf(
            'Exception: %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        error_log($message);
    }

    /**
     * Registra um listener de evento usando PSR-14.
     *
     * @param  string   $eventType Nome da classe do evento
     * @param  callable $listener  Listener
     * @return $this
     */
    public function addEventListener(string $eventType, callable $listener): self
    {
        if ($this->container->has('listeners')) {
            $listenerProvider = $this->container->get('listeners');
            if ($listenerProvider instanceof EventsListenerProvider) {
                $listenerProvider->addListener($eventType, $listener);
                // Rastrear listener
                $this->registeredListeners[$eventType][] = $listener;
            }
        }

        return $this;
    }

    /**
     * Remove todos os listeners PSR-14 previamente registrados.
     * @return void
     */
    public function clearEventListeners(): void
    {
        if ($this->container->has('listeners')) {
            $listenerProvider = $this->container->get('listeners');
            if ($listenerProvider instanceof EventsListenerProvider) {
                foreach ($this->registeredListeners as $eventType => $listeners) {
                    foreach ($listeners as $listener) {
                        $listenerProvider->removeListener($eventType, $listener);
                    }
                }
            }
        }
        $this->registeredListeners = [];
    }

    /**
     * Remove todos os listeners PSR-14 e registra novamente os fornecidos.
     * @param array<string, callable[]> $listeners
     * @return void
     */
    public function reRegisterEventListeners(array $listeners): void
    {
        $this->clearEventListeners();
        foreach ($listeners as $eventType => $callbacks) {
            foreach ($callbacks as $callback) {
                $this->addEventListener($eventType, $callback);
            }
        }
    }

    /**
     * Dispara um evento usando PSR-14.
     *
     * @param  object $event Evento a ser disparado
     * @return object
     */
    public function dispatchEvent(object $event): object
    {
        if ($this->container->has('events')) {
            $dispatcher = $this->container->get('events');
            if ($dispatcher instanceof EventDispatcherInterface) {
                return $dispatcher->dispatch($event);
            }
        }

        return $event;
    }

    /**
     * Alias para addEventListener (compatibilidade)
     *
     * @param  string   $event    Nome do evento
     * @param  callable $listener Listener
     * @return $this
     */
    public function on(string $event, callable $listener): self
    {
        return $this->addEventListener($event, $listener);
    }

    /**
     * Alias para dispatchEvent (compatibilidade)
     *
     * @param  string $event   Nome do evento
     * @param  mixed  ...$args Argumentos do evento
     * @return $this
     */
    public function fireEvent(string $event, ...$args): self
    {
        // Para compatibilidade, criar um evento simples
        $eventObject = new class ($event, $args) {
            /**
             * @param array<mixed> $data
             */
            public function __construct(
                public readonly string $name,
                public readonly array $data
            ) {
            }
        };

        $this->dispatchEvent($eventObject);
        return $this;
    }


    /**
     * Executa a aplicação e envia a resposta.
     *
     * @return void
     */
    public function run(): void
    {
        // Global handlers are installed only at the SAPI entry point, so handle()
        // (tests, workers, embedding) never changes global state.
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleUncaughtException']);

        try {
            (new SapiEmitter())->emit($this->handle());
        } finally {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    /**
     * Obtém o container de dependências.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Obtém as configurações.
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Obtém o router.
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Obtém o logger PSR-3.
     *
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        try {
            if ($this->container->has('logger')) {
                $logger = $this->container->get('logger');
                return $logger instanceof LoggerInterface ? $logger : null;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Obtém o event dispatcher PSR-14.
     *
     * @return EventDispatcherInterface|null
     */
    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        try {
            if ($this->container->has('events')) {
                $dispatcher = $this->container->get('events');
                return $dispatcher instanceof EventDispatcherInterface ? $dispatcher : null;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Verifica se a aplicação foi inicializada.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Obtém a versão do framework.
     *
     * @return string
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Factory method para criar instância da aplicação (compatibilidade com ApiPivot).
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return self
     */
    public static function create(?string $basePath = null): self
    {
        return new self($basePath);
    }

    /**
     * Factory method estilo Express.js para criar aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return self
     */
    public static function express(?string $basePath = null): self
    {
        return new self($basePath);
    }

    /**
     * Configura múltiplas opções da aplicação de uma vez.
     *
     * @param array<string, mixed> $config Configurações
     * @return $this
     */
    public function configure(array $config): self
    {
        foreach ($config as $key => $value) {
            $this->config->set($key, $value);
        }

        return $this;
    }

    /**
     * Define a URL base da aplicação.
     *
     * @param string $baseUrl URL base
     * @return $this
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Obtém a URL base da aplicação.
     *
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    // ==========================================
    // EXTENSION & HOOK MANAGEMENT METHODS
    // ==========================================

    /**
     * Get extension manager instance
     */
    public function extensions(): \PivotPHP\Core\Providers\ExtensionManager
    {
        /** @var \PivotPHP\Core\Providers\ExtensionManager */
        return $this->make(\PivotPHP\Core\Providers\ExtensionManager::class);
    }

    /**
     * Get hook manager instance
     */
    public function hooks(): HookManager
    {
        /** @var HookManager */
        return $this->make(HookManager::class);
    }

    /**
     * Register an extension manually: $provider is a ServiceProvider class name,
     * instantiated with the application and registered immediately.
     */
    public function registerExtension(string $name, string $provider): self
    {
        $this->extensions()->registerExtension($name, $provider);
        return $this;
    }

    /**
     * Add an action hook
     */
    public function addAction(
        string $hook,
        callable $callback,
        int $priority = 10
    ): self {
        $this->hooks()->addAction($hook, $callback, $priority);
        return $this;
    }

    /**
     * Add a filter hook
     */
    public function addFilter(
        string $hook,
        callable $callback,
        int $priority = 10
    ): self {
        $this->hooks()->addFilter($hook, $callback, $priority);
        return $this;
    }

    /**
     * Execute an action hook
     */
    public function doAction(string $hook, array $context = []): self
    {
        $this->hooks()->doAction($hook, $context);
        return $this;
    }

    /**
     * Apply a filter hook
     *
     * @param mixed $data
     * @param array<string, mixed> $context
     * @return mixed
     */
    public function applyFilter(
        string $hook,
        mixed $data,
        array $context = []
    ): mixed {
        return $this->hooks()->applyFilter($hook, $data, $context);
    }

    /**
     * Get extension statistics
     *
     * @return array{extensions: array, hooks: array}
     */
    public function getExtensionStats(): array
    {
        return [
            'extensions' => $this->extensions()->getStats(),
            'hooks' => $this->hooks()->getStats()
        ];
    }
}
