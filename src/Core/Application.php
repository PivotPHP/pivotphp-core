<?php

namespace PivotPHP\Core\Core;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Routing\Router;
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
use PivotPHP\Core\Events\RequestReceived;
use PivotPHP\Core\Events\ResponseSent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
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
class Application
{
    /**
     * Versão do framework.
     */
    public const VERSION = '2.0.0';

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
    protected array $middlewareAliases = [
        'load-shedder' => \PivotPHP\Core\Middleware\LoadShedder::class,
        'rate-limiter' => \PivotPHP\Core\Middleware\RateLimiter::class,
    ];

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
        $basePath = $this->container->has('path.base') ? $this->container->get('path.base') : getcwd();
        return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
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
        $configPath = $this->container->has('path.config') ? $this->container->get('path.config') : null;

        if (is_string($configPath) && is_dir($configPath)) {
            $this->config->setConfigPath($configPath)->loadAll();
        }

        // Carregar .env se existir
        $envFile = $this->basePath('.env');
        if (file_exists($envFile)) {
            $this->config->loadEnvironment($envFile);
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
     * Configura tratamento básico de erros no construtor.
     *
     * @return void
     */
    protected function configureBasicErrorHandling(): void
    {
        // Configuração básica de erro que funciona mesmo sem config carregado
        error_reporting(E_ALL);
        ini_set('log_errors', '1');

        // Por enquanto, mostrar erros até config ser carregado
        ini_set('display_errors', '1');

        set_error_handler([$this, 'handleError']);
        set_exception_handler(
            function (Throwable $e): void {
                $this->handleException($e);
            }
        );
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

        set_error_handler([$this, 'handleError']);
        set_exception_handler(
            function (Throwable $e): void {
                $this->handleException($e);
            }
        );
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
                if (is_string($middleware) || is_callable($middleware)) {
                    $this->middlewares->add($middleware);
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
    public function use($middleware): self
    {
        // Check if it's a middleware alias
        if (is_string($middleware) && isset($this->middlewareAliases[$middleware])) {
            $middleware = $this->middlewareAliases[$middleware];
        }

        // If middleware is a string class name, resolve it
        if (is_string($middleware) && class_exists($middleware)) {
            $middlewareInstance = $this->container->has($middleware)
                ? $this->container->get($middleware)
                : new $middleware();

            // Convert to callable format expected by MiddlewareStack
            if (is_object($middlewareInstance) && method_exists($middlewareInstance, 'handle')) {
                $callable = function ($request, $response, $next) use ($middlewareInstance) {
                    return $middlewareInstance->handle($request, $response, $next);
                };
            } else {
                throw new \InvalidArgumentException('Middleware must have a handle method');
            }

            $this->middlewares->add($callable);
        } elseif (is_callable($middleware)) {
            $this->middlewares->add($middleware);
        } else {
            // Try to make it callable
            if (is_object($middleware) && method_exists($middleware, 'handle')) {
                $callable = function ($request, $response, $next) use ($middleware) {
                    return $middleware->handle($request, $response, $next);
                };
                $this->middlewares->add($callable);
            } else {
                throw new \InvalidArgumentException('Middleware must be callable or have a handle method');
            }
        }

        return $this;
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
     * Get middleware by name
     *
     * @param string $name
     * @return mixed
     */
    public function getMiddleware(string $name)
    {
        // This would need to be implemented based on how middlewares are stored
        // For now, return null
        return null;
    }

    /**
     * Registra uma rota GET.
     *
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
     * @return $this
     */
    public function get(string $path, $handler): self
    {
        $this->router->get($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota POST.
     *
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
     * @return $this
     */
    public function post(string $path, $handler): self
    {
        $this->router->post($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota PUT.
     *
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
     * @return $this
     */
    public function put(string $path, $handler): self
    {
        $this->router->put($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota DELETE.
     *
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
     * @return $this
     */
    public function delete(string $path, $handler): self
    {
        $this->router->delete($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota PATCH.
     *
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
     * @return $this
     */
    public function patch(string $path, $handler): self
    {
        $this->router->patch($path, $handler);
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
        \PivotPHP\Core\Routing\StaticFileManager::registerDirectory($routePrefix, $physicalPath, $this, $options);

        return $this;
    }

    /**
     * Processa uma requisição HTTP.
     *
     * @param  Request|null $request Requisição (se null, cria automaticamente)
     * @return Response
     */
    public function handle(?Request $request = null): Response
    {
        if (!$this->booted) {
            $this->boot();
        }

        $request = $request ?: Request::createFromGlobals();
        $response = new Response();
        $startTime = microtime(true);

        // Disparar evento de requisição recebida
        $this->dispatchEvent(new RequestReceived($request, new \DateTime()));

        try {
            // Encontrar rota
            $route = $this->router::identify($request->getMethod(), $request->getPathCallable());

            if (!$route) {
                // Buscar rotas disponíveis para suggestions
                $availableRoutes = array_map(
                    fn($r) => "{$r['method']} {$r['path']}",
                    array_slice($this->router::getRoutes(), 0, 10)
                );

                throw ContextualException::routeNotFound(
                    $request->getMethod(),
                    $request->getPathCallable(),
                    $availableRoutes
                );
            }
            // Definindo o path configurado na requisição
            // Isso é necessário para middlewares que dependem do path para definir os parâmetros
            $request->setPath($route['path']);

            // Executar middlewares e handler
            $result = $this->middlewares->execute(
                $request,
                $response,
                function ($req, $res) use ($route) {
                    return $this->callRouteHandler($route, $req, $res);
                }
            );

            $finalResponse = $result instanceof Response ? $result : $response;

            // Disparar evento de resposta enviada
            $processingTime = microtime(true) - $startTime;
            $this->dispatchEvent(new ResponseSent($request, $finalResponse, new \DateTime(), $processingTime));

            return $finalResponse;
        } catch (Throwable $e) {
            $errorResponse = $this->handleException($e, $request, $response);

            // Disparar evento de resposta com erro
            $processingTime = microtime(true) - $startTime;
            $this->dispatchEvent(new ResponseSent($request, $errorResponse, new \DateTime(), $processingTime));

            return $errorResponse;
        }
    }

    /**
     * Executa o handler de uma rota.
     *
     * @param  array<string, mixed> $route    Dados da rota
     * @param  Request              $request  Requisição
     * @param  Response             $response Resposta
     * @return Response
     */
    protected function callRouteHandler(
        array $route,
        Request $request,
        Response $response
    ): Response {
        $handler = $route['handler'];

        // Usar CallableResolver para garantir compatibilidade com array callables
        try {
            $result = CallableResolver::call($handler, $request, $response);
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

        return $result instanceof Response ? $result : $response;
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
     * @param  Throwable     $e        Exceção
     * @param  Request|null  $request  Requisição
     *                                 (opcional)
     * @param  Response|null $response Resposta (opcional)
     * @return Response
     */
    public function handleException(
        Throwable $e,
        ?Request $request = null,
        ?Response $response = null
    ): Response {
        $response = $response ?: new Response();
        $debug = $this->config->get('app.debug', false);

        // Log do erro usando PSR-3 logger
        $this->logException($e);

        // Determinar status code
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        if ($debug) {
            return $response
                ->status($statusCode)
                ->json(
                [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        } else {
            // Em produção, gerar ID único para o erro e logar detalhes
            $errorId = uniqid('err_', true);

            // Log detalhado para análise posterior
            $this->logException($e, $errorId);

            return $response
                ->status($statusCode)
                ->json(
                [
                    'error' => true,
                    'message' => $statusCode === 404 ? 'Not Found' : 'Internal Server Error',
                    'error_id' => $errorId
                ]
            );
        }
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
            if ($listenerProvider instanceof \PivotPHP\Core\Providers\ListenerProvider) {
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
            if ($listenerProvider instanceof \PivotPHP\Core\Providers\ListenerProvider) {
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
        $response = $this->handle();

        // Delegar toda a lógica de emissão para o Response
        $response->emit();
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
     * Register an extension manually
     */
    public function registerExtension(
        string $name,
        string $provider,
        array $config = []
    ): self {
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
