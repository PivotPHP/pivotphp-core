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
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     */
    public function __construct(array $rules = [], array $messages = [])
    {
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Define regras de validação
     * @param array<string, mixed> $rules
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
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

        if (!is_array($this->rules)) {
            return true;
        }

        foreach ($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;

            if (is_array($fieldRules)) {
                foreach ($fieldRules as $rule) {
                    if (!$this->validateRule($field, $value, $rule)) {
                        break; // Para no primeiro erro
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Retorna os erros de validação
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
     */
    private function validateRule(string $field, $value, string $rule): bool
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleValue = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0 && $value !== 0.0 && $value !== false) {
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
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                    return false;
                }
                break;

            case 'min':
                if (is_string($value) && strlen($value) < (int)$ruleValue) {
                    $this->addError($field, 'min', ['min' => $ruleValue]);
                    return false;
                } elseif (is_numeric($value) && $value < (int)$ruleValue) {
                    $this->addError($field, 'min', ['min' => $ruleValue]);
                    return false;
                }
                break;

            case 'max':
                if (is_string($value) && strlen($value) > (int)$ruleValue) {
                    $this->addError($field, 'max', ['max' => $ruleValue]);
                    return false;
                } elseif (is_numeric($value) && $value > (int)$ruleValue) {
                    $this->addError($field, 'max', ['max' => $ruleValue]);
                    return false;
                }
                break;

            case 'in':
                $allowed = explode(',', $ruleValue ?? '');
                if (!in_array($value, $allowed)) {
                    $this->addError($field, 'in', ['values' => implode(', ', $allowed)]);
                    return false;
                }
                break;

            case 'regex':
                if ($ruleValue && is_string($value) && !preg_match($ruleValue, $value)) {
                    $this->addError($field, 'regex');
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Adiciona um erro
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

        // Mensagens padrão
        $defaultMessages = [
            'required' => "O campo {$field} é obrigatório.",
            'string' => "O campo {$field} deve ser uma string.",
            'numeric' => "O campo {$field} deve ser numérico.",
            'integer' => "O campo {$field} deve ser um número inteiro.",
            'email' => "O campo {$field} deve ser um email válido.",
            'min' => "O campo {$field} deve ter pelo menos {min} caracteres.",
            'max' => "O campo {$field} deve ter no máximo {max} caracteres.",
            'in' => "O campo {$field} deve ser um dos valores: {values}.",
            'regex' => "O campo {$field} tem formato inválido."
        ];

        $message = $defaultMessages[$rule] ?? "O campo {$field} é inválido.";
        return $this->replaceParams($message, $params);
    }

    /**
     * Substitui parâmetros na mensagem
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
