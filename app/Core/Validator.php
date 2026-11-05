<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            if (!empty($messages)) {
                return $messages[0];
            }
        }

        return null;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $fieldRules) {
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $this->data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $value, $ruleName, $params);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, array $params): void
    {
        $isEmpty = $value === null || $value === '' || $value === [];

        if ($rule === 'required') {
            if ($isEmpty) {
                $this->addError($field, "The {$field} field is required.");
            }
            return;
        }

        // If field is empty and not required, skip further validations
        if ($isEmpty) {
            return;
        }

        switch ($rule) {
            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, "The {$field} must be a string.");
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "The {$field} must be an integer.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                }
                break;

            case 'min':
                $min = (float) ($params[0] ?? 0);
                if (is_numeric($value)) {
                    if ((float) $value < $min) {
                        $this->addError($field, "The {$field} must be at least {$min}.");
                    }
                } elseif (is_string($value)) {
                    if (mb_strlen($value) < $min) {
                        $this->addError($field, "The {$field} must be at least {$min} characters.");
                    }
                }
                break;

            case 'max':
                $max = (float) ($params[0] ?? 0);
                if (is_numeric($value)) {
                    if ((float) $value > $max) {
                        $this->addError($field, "The {$field} must not exceed {$max}.");
                    }
                } elseif (is_string($value)) {
                    if (mb_strlen($value) > $max) {
                        $this->addError($field, "The {$field} must not exceed {$max} characters.");
                    }
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;

            case 'in':
                if (!in_array((string) $value, $params, true)) {
                    $allowed = implode(', ', $params);
                    $this->addError($field, "The {$field} must be one of: {$allowed}.");
                }
                break;

            case 'date':
                if (strtotime((string) $value) === false) {
                    $this->addError($field, "The {$field} must be a valid date.");
                }
                break;

            case 'phone_mw':
                $normalized = Phone::normalize((string) $value);
                if ($normalized === null) {
                    $this->addError($field, "The {$field} must be a valid Malawi phone number.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}

