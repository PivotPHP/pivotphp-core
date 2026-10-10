<?php

declare(strict_types=1);

namespace PivotPHP\Core\Validation;

/**
 * Sistema de validação para PivotPHP
 */
class Validator
{
    /** @var array<string, mixed> */
    private array $rules = [];
    /** @var array<string, mixed> */
    private array $messages = [];
    /** @var array<string, string[]> */
    private array $errors = [];

    /**
     * Lista de regras suportadas
     * @var string[]
     */
    private const SUPPORTED_RULES = [
        'required',
        'nullable',
        'sometimes',
        'string',
        'numeric',
        'integer',
        'email',
        'min',
        'max',
        'in',
        'regex',
    ];

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     */
    public function __construct(array $rules = [], array $messages = [])
    {
        $this->setRules($rules);
        $this->messages = $messages;
    }

    /**
     * Define regras de validação
     * @param array<string, mixed> $rules
     */
    public function setRules(array $rules): self
    {
        $this->validateRulesDefinitions($rules);
        $this->rules = $rules;
        return $this;
    }

    /**
     * Valida que todas as regras declaradas são conhecidas e válidas
     *
     * @param array<string, mixed> $rules
     * @throws \InvalidArgumentException
     */
    private function validateRulesDefinitions(array $rules): void
    {
        foreach ($rules as $field => $fieldRules) {
            $parsedRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            if (!is_array($parsedRules)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Validation rule for "%s" must be a string or array, %s given.',
                        $field,
                        get_debug_type($fieldRules)
                    )
                );
            }

            foreach ($parsedRules as $rule) {
                if (!is_string($rule)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Validation rule for "%s" must be a string, %s given.',
                            $field,
                            get_debug_type($rule)
                        )
                    );
                }

                $parts = explode(':', $rule, 2);
                $ruleName = $parts[0];

                if (!in_array($ruleName, self::SUPPORTED_RULES, true)) {
                    throw new \InvalidArgumentException(
                        sprintf('Unknown validation rule "%s" for field "%s".', $ruleName, $field)
                    );
                }
            }
        }
    }

    /**
     * Define mensagens customizadas
     * @param array<string, mixed> $messages
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Executa a validação
     * @param array<string, mixed> $data
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $hasField = array_key_exists($field, $data);
            $value = $hasField ? $data[$field] : null;

            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;

            if (!is_array($fieldRules)) {
                continue;
            }

            $ruleNames = [];
            foreach ($fieldRules as $r) {
                if (is_string($r)) {
                    $parts = explode(':', $r, 2);
                    $ruleNames[] = $parts[0];
                }
            }

            $isRequired = in_array('required', $ruleNames, true);
            $isNullable = in_array('nullable', $ruleNames, true);

            // Se o campo não existe no payload:
            if (!$hasField) {
                if ($isRequired) {
                    $this->addError($field, 'required');
                }
                // Se não é required (campo opcional ausente), não valida as demais regras
                continue;
            }

            // Se o campo existe e é null:
            if ($value === null) {
                if ($isNullable) {
                    // Campo nullable com valor null é válido
                    continue;
                }
                if ($isRequired) {
                    $this->addError($field, 'required');
                    continue;
                }
            }

            // Validar as regras do campo
            foreach ($fieldRules as $rule) {
                if (!is_string($rule)) {
                    continue;
                }

                $parts = explode(':', $rule, 2);
                $ruleName = $parts[0];

                // Regras modificadoras não precisam ser validadas individualmente aqui
                if ($ruleName === 'nullable' || $ruleName === 'sometimes') {
                    continue;
                }

                if (!$this->validateRule($field, $value, $rule, $ruleNames)) {
                    break; // Para no primeiro erro do campo
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Retorna os erros de validação
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna o primeiro erro
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors)[0] : null;
    }

    /**
     * Valida uma regra específica
     *
     * @param mixed $value
     * @param string[] $allFieldRules
     */
    private function validateRule(string $field, mixed $value, string $rule, array $allFieldRules = []): bool
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleValue = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (
                    $value === null ||
                    $value === '' ||
                    (is_array($value) && empty($value))
                ) {
                    $this->addError($field, 'required');
                    return false;
                }
                break;

            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, 'string');
                    return false;
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, 'numeric');
                    return false;
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, 'integer');
                    return false;
                }
                break;

            case 'email':
                if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                    return false;
                }
                break;

            case 'min':
                if ($ruleValue === null) {
                    return true;
                }
                return $this->validateMin($field, $value, $ruleValue, $allFieldRules);

            case 'max':
                if ($ruleValue === null) {
                    return true;
                }
                return $this->validateMax($field, $value, $ruleValue, $allFieldRules);

            case 'in':
                $allowed = explode(',', $ruleValue ?? '');
                // Comparação estrita com string normalizada para escalares
                if (!is_scalar($value) || !in_array((string)$value, $allowed, true)) {
                    $this->addError($field, 'in', ['values' => implode(', ', $allowed)]);
                    return false;
                }
                break;

            case 'regex':
                if (!is_string($value)) {
                    $this->addError($field, 'regex');
                    return false;
                }
                if ($ruleValue && !preg_match($ruleValue, $value)) {
                    $this->addError($field, 'regex');
                    return false;
                }
                break;

            default:
                throw new \InvalidArgumentException(
                    sprintf('Unknown validation rule "%s" for field "%s".', $ruleName, $field)
                );
        }

        return true;
    }

    /**
     * @param string[] $allFieldRules
     */
    private function isNumericField(mixed $value, array $allFieldRules): bool
    {
        if (in_array('numeric', $allFieldRules, true) || in_array('integer', $allFieldRules, true)) {
            return true;
        }

        return is_numeric($value) && !in_array('string', $allFieldRules, true);
    }

    /**
     * @param string[] $allFieldRules
     */
    private function validateMin(string $field, mixed $value, string $ruleValue, array $allFieldRules): bool
    {
        $limit = (float)$ruleValue;

        if ($this->isNumericField($value, $allFieldRules) && is_numeric($value)) {
            $numVal = (float)$value;
            if ($numVal < $limit) {
                $this->addError($field, 'min_numeric', ['min' => $ruleValue]);
                return false;
            }
            return true;
        }

        if (is_array($value)) {
            if (count($value) < (int)$ruleValue) {
                $this->addError($field, 'min_array', ['min' => $ruleValue]);
                return false;
            }
            return true;
        }

        if (is_string($value)) {
            if (mb_strlen($value, 'UTF-8') < (int)$ruleValue) {
                $this->addError($field, 'min', ['min' => $ruleValue]);
                return false;
            }
            return true;
        }

        $this->addError($field, 'min', ['min' => $ruleValue]);
        return false;
    }

    /**
     * @param string[] $allFieldRules
     */
    private function validateMax(string $field, mixed $value, string $ruleValue, array $allFieldRules): bool
    {
        $limit = (float)$ruleValue;

        if ($this->isNumericField($value, $allFieldRules) && is_numeric($value)) {
            $numVal = (float)$value;
            if ($numVal > $limit) {
                $this->addError($field, 'max_numeric', ['max' => $ruleValue]);
                return false;
            }
            return true;
        }

        if (is_array($value)) {
            if (count($value) > (int)$ruleValue) {
                $this->addError($field, 'max_array', ['max' => $ruleValue]);
                return false;
            }
            return true;
        }

        if (is_string($value)) {
            if (mb_strlen($value, 'UTF-8') > (int)$ruleValue) {
                $this->addError($field, 'max', ['max' => $ruleValue]);
                return false;
            }
            return true;
        }

        $this->addError($field, 'max', ['max' => $ruleValue]);
        return false;
    }

    /**
     * Adiciona um erro
     * @param array<string, string> $params
     */
    private function addError(
        string $field,
        string $rule,
        array $params = []
    ): void {
        $message = $this->getMessage($field, $rule, $params);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Obtém a mensagem de erro
     * @param array<string, string> $params
     */
    private function getMessage(
        string $field,
        string $rule,
        array $params = []
    ): string {
        $messageKey = "{$field}.{$rule}";

        if (isset($this->messages[$messageKey])) {
            return $this->replaceParams((string)$this->messages[$messageKey], $params);
        }

        // Fallback para min/max genéricos se não houver mensagem customizada específica de min_numeric/min_array
        if (($rule === 'min_numeric' || $rule === 'min_array') && isset($this->messages["{$field}.min"])) {
            return $this->replaceParams((string)$this->messages["{$field}.min"], $params);
        }
        if (($rule === 'max_numeric' || $rule === 'max_array') && isset($this->messages["{$field}.max"])) {
            return $this->replaceParams((string)$this->messages["{$field}.max"], $params);
        }

        // Mensagens padrão
        $defaultMessages = [
            'required' => "O campo {$field} é obrigatório.",
            'string' => "O campo {$field} deve ser uma string.",
            'numeric' => "O campo {$field} deve ser numérico.",
            'integer' => "O campo {$field} deve ser um número inteiro.",
            'email' => "O campo {$field} deve ser um email válido.",
            'min' => "O campo {$field} deve ter pelo menos {min} caracteres.",
            'min_numeric' => "O campo {$field} deve ser no mínimo {min}.",
            'min_array' => "O campo {$field} deve conter pelo menos {min} itens.",
            'max' => "O campo {$field} deve ter no máximo {max} caracteres.",
            'max_numeric' => "O campo {$field} deve ser no máximo {max}.",
            'max_array' => "O campo {$field} deve conter no máximo {max} itens.",
            'in' => "O campo {$field} deve ser um dos valores: {values}.",
            'regex' => "O campo {$field} tem formato inválido."
        ];

        $message = $defaultMessages[$rule] ?? "O campo {$field} é inválido.";
        return $this->replaceParams($message, $params);
    }

    /**
     * Substitui parâmetros na mensagem
     * @param array<string, string> $params
     */
    private function replaceParams(string $message, array $params): string
    {
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        return $message;
    }

    /**
     * Factory method para validação rápida
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     */
    public static function make(
        array $data,
        array $rules,
        array $messages = []
    ): self {
        $validator = new self($rules, $messages);
        $validator->validate($data);
        return $validator;
    }
}
