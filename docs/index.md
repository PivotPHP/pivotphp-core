# 📚 Índice da Documentação PivotPHP

Bem-vindo ao guia completo do PivotPHP! Esta documentação foi criada para ser um guia prático para quem quer usar o framework na prática.

## 🚀 Para Começar

### 📖 Implementação Rápida
- [**API Básica**](implementations/usage_basic.md) - Sua primeira API em 5 minutos
- [**API com Middlewares**](implementations/usage_with_middleware.md) - Usando segurança, CORS e autenticação
- [**Middleware Customizado**](implementations/usage_with_custom_middleware.md) - Criando suas próprias extensões
- [**Rotas com Regex**](implementations/usage_regex_routes.md) - Validação avançada com regex e constraints

## 🔧 Referência Técnica

### 📡 Core da Aplicação
- [**Application**](technical/application.md) - O coração do framework
- [**Request**](technical/http/request.md) - Manipulando requisições HTTP
- [**Response**](technical/http/response.md) - Criando respostas poderosas
- [**Router**](technical/routing/router.md) - Sistema de roteamento avançado
- [**OpenAPI/Swagger**](technical/http/openapi_documentation.md) - Documentação automática da API

### 🛡️ Segurança e Middlewares
- [**Visão Geral**](technical/middleware/README.md) - Todos os middlewares disponíveis
- [**SecurityHeadersMiddleware**](technical/middleware/SecurityMiddleware.md) - Headers de segurança (XSS/CSRF são middlewares separados: `CsrfMiddleware`, `XssMiddleware`)
- [**CorsMiddleware**](technical/middleware/CorsMiddleware.md) - Cross-Origin Resource Sharing
- [**AuthMiddleware**](technical/middleware/AuthMiddleware.md) - JWT, Basic, Bearer, API Key
- [**RateLimitMiddleware**](technical/middleware/RateLimitMiddleware.md) - Controle de taxa (depreciado desde v2.1.0 — veja `RateLimiter`)
- [**ValidationMiddleware**](technical/middleware/ValidationMiddleware.md) - Validação de dados
- [**Middleware Customizado**](technical/middleware/CustomMiddleware.md) - Crie o seu próprio

### 🔐 Autenticação
- [**Uso Nativo**](technical/authentication/usage_native.md) - JWT, Basic, Bearer prontos para usar
- [**Autenticação Customizada**](technical/authentication/usage_custom.md) - Implemente seu próprio sistema

### ⚠️ Tratamento de Erros e Debug
- [**Sistema de Erros**](technical/exceptions/ErrorHandling.md) - Como o framework trata erros
- [**Exceptions Personalizadas**](technical/exceptions/CustomExceptions.md) - Crie suas próprias exceções
- [**Modo Debug**](technical/debugging/debug-mode.md) - Configuração e uso do modo debug

### 🧩 Extensibilidade
- [**Providers**](technical/providers/usage.md) - Injeção de dependências
- [**Criando Extensões**](technical/providers/extension.md) - Desenvolva plugins
- [**Sistema de Extensões**](technical/extensions/README.md) - Arquitetura de plugins

## ⚡ Performance

### 📊 Monitoramento
- [**PerformanceMonitor**](performance/PerformanceMonitor.md) - Monitore sua aplicação
- [**Benchmarks**](performance/benchmarks/README.md) - Resultados e otimizações

## 📋 Releases e Versões

### 🚀 Histórico de Versões
- [**Documentação de Releases**](releases/README.md) - Índice completo de versões

Todas as entradas abaixo linkam para o registro oficial da versão — um documento
`FRAMEWORK_OVERVIEW` dedicado quando existe um, ou a entrada correspondente no
[CHANGELOG](../CHANGELOG.md) quando não existe (ainda não foi escrito um overview
técnico para v2.1.1/v2.1.0, releases pequenas/patch):

- [**v2.1.1 (Atual)**](../CHANGELOG.md) - PSR-7 2.0 Compatibility Fix
- [**v2.1.0**](../CHANGELOG.md) - Response Emission, Pool Safety & Deprecation Cycle
- [**v2.0.0**](releases/FRAMEWORK_OVERVIEW_v2.0.0.md) - Modular Routing & Legacy Cleanup Edition
- [**v1.2.0**](releases/FRAMEWORK_OVERVIEW_v1.2.0.md) - Simplicity Edition: Arquitetura simplificada
- [**v1.1.4**](releases/FRAMEWORK_OVERVIEW_v1.1.4.md) - Developer Experience & Examples Modernization
- [**v1.1.3**](releases/FRAMEWORK_OVERVIEW_v1.1.3.md) - Architectural Excellence Edition
- [**v1.0.1**](releases/FRAMEWORK_OVERVIEW_v1.0.1.md) - Regex route validation support
- [**v1.0.0**](releases/FRAMEWORK_OVERVIEW_v1.0.0.md) - Core rewrite and PSR compliance

## 🧪 Testes

### 📝 Guias de Teste
- [**Testando sua API**](testing/api_testing.md) - Como testar endpoints
- [**Testando Middlewares**](testing/middleware_testing.md) - Testes de middleware
- [**Mocks e Stubs**](testing/mocks_and_stubs.md) - Simulando dependências
- [**Testes de Integração**](testing/integration_testing.md) - Testando o fluxo completo

## 🤝 Contribuindo

### 💡 Como Ajudar
- [**Guia de Contribuição**](contributing/README.md) - Como contribuir com o projeto

---

## 🎯 Fluxo de Aprendizado Recomendado

### 👶 Iniciante
1. [API Básica](implementations/usage_basic.md)
2. [Application](technical/application.md)
3. [Request](technical/http/request.md) [Response](technical/http/response.md)

### 🚀 Intermediário
1. [API com Middlewares](implementations/usage_with_middleware.md)
2. [Autenticação](technical/authentication/usage_native.md)
3. [Modo Debug](technical/debugging/debug-mode.md)
4. [Testando sua API](testing/api_testing.md)

### 🔥 Avançado
1. [Middleware Customizado](implementations/usage_with_custom_middleware.md)
2. [Criando Extensões](technical/providers/extension.md)
3. [Performance](performance/PerformanceMonitor.md)
4. [Releases e Versões](releases/README.md)

---

## ⚠️ Importante: Manutenção do Projeto

**PivotPHP Core é mantido por apenas uma pessoa** e pode não receber atualizações constantemente. Esta documentação cobre um framework estável e funcional, ideal para provas de conceito, protótipos e estudos, mas não recomendado para sistemas de produção críticos.

---

*📖 Documentação atualizada em: 15 de julho de 2026 - v2.1.1*
