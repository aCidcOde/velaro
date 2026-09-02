<?php

namespace App\Support;

class DocumentInput
{
    public static function normalize(?string $value): ?string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', trim((string) $value));

        return $normalized !== '' ? strtoupper($normalized) : null;
    }

    /**
     * @return array<int, mixed>
     */
    public static function documentRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:30',
            'regex:/^[A-Za-z0-9.\-\/\s]+$/',
        ];
    }
}
