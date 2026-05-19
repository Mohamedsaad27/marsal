<?php

namespace App\Modules\Core\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\RequiredUnless;
use Illuminate\Validation\Rules\Unique;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Module translation namespace (e.g. "users" → users::validation.*).
     */
    abstract protected function translationNamespace(): string;

    /**
     * Optional per-request messages (merged on top of module + fallback rules).
     *
     * @return array<string, string>
     */
    protected function customMessages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = trans($this->translationNamespace() . '::validation.attributes');

        return is_array($attributes) ? $attributes : [];
    }

    /**
     * Human-readable validation messages (module-specific overrides core fallbacks).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            $this->resolveFallbackMessages(),
            $this->getModuleMessages(),
            $this->customMessages(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getModuleMessages(): array
    {
        $messages = trans($this->translationNamespace() . '::validation.messages');

        return is_array($messages) ? $messages : [];
    }

    /**
     * Build messages from core rule templates + field attributes for rules without explicit copy.
     *
     * @return array<string, string>
     */
    protected function resolveFallbackMessages(): array
    {
        $templates = trans('core::validation.rules');

        if (! is_array($templates) || $templates === []) {
            return [];
        }

        $attributes = $this->attributes();
        $messages = [];

        foreach ($this->rules() as $field => $rules) {
            foreach ($this->normalizeRules($rules) as $rule) {
                $ruleName = $this->parseRuleName($rule);

                if ($ruleName === null || ! isset($templates[$ruleName])) {
                    continue;
                }

                $key = "{$field}.{$ruleName}";

                if (isset($messages[$key])) {
                    continue;
                }

                $attribute = $attributes[$field] ?? $this->humanizeFieldName((string) $field);
                $messages[$key] = str_replace(':attribute', $attribute, $templates[$ruleName]);
            }
        }

        return $messages;
    }

    /**
     * @return list<mixed>
     */
    protected function normalizeRules(mixed $rules): array
    {
        if (is_string($rules)) {
            return explode('|', $rules);
        }

        if (is_array($rules)) {
            return $rules;
        }

        return [$rules];
    }

    protected function parseRuleName(mixed $rule): ?string
    {
        if (is_string($rule)) {
            return Str::before($rule, ':');
        }

        if (! is_object($rule)) {
            return null;
        }

        return match (true) {
            $rule instanceof Unique => 'unique',
            $rule instanceof Exists => 'exists',
            $rule instanceof In, $rule instanceof Enum => 'in',
            $rule instanceof RequiredIf => 'required_if',
            $rule instanceof RequiredUnless => 'required_if',
            default => Str::snake(class_basename($rule)),
        };
    }

    protected function humanizeFieldName(string $field): string
    {
        return str_replace(['.', '_'], ' ', $field);
    }
}
