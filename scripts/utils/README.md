# Utility Scripts

Este diretório contém scripts utilitários para manutenção e configuração do projeto.

## Scripts Disponíveis


### version-utils.sh
Utilitários para manipulação de versões e metadados.
```bash
source ./scripts/utils/version-utils.sh
get_current_version
validate_version_format "1.2.3"
```

## Funcionalidades


### version-utils.sh
- Funções para leitura de versão
- Validação de formato semântico
- Utilitários de comparação de versões
- Helpers para scripts de release

## Uso em Desenvolvimento

### Funções de Versão
```bash
# Incluir utilitários em outros scripts
source ./scripts/utils/version-utils.sh

# Usar funções disponíveis
current_version=$(get_current_version)
if validate_version_format "$current_version"; then
    echo "Versão válida: $current_version"
fi
```

## Integração com Outros Scripts

Estes utilitários são usados por:
- Scripts de release para validação de versão
- Scripts de qualidade para verificação PSR-7
- Scripts de validação para leitura de metadados
- CI/CD para configuração automática

## Dependências

- PHP 8.1+ para scripts PHP
- Bash para scripts shell
- Composer para manipulação de dependências
- Git para operações de versionamento