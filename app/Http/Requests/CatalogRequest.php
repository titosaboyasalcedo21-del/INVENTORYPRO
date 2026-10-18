<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $section = explode('.', (string) $this->route()?->getName())[0] ?? '';
        $record = match ($section) {
            'categories' => $this->route('category'),
            'suppliers' => $this->route('supplier'),
            'customers' => $this->route('customer'),
            default => null,
        };
        $recordId = is_object($record) ? $record->id : $record;

        if ($section === 'categories') {
            return [
                'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($recordId)],
                'description' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => $section === 'suppliers' ? ['nullable', 'string', 'max:255'] : ['prohibited'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
