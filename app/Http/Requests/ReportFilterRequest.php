<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date', 'before_or_equal:to'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'before_or_equal:today'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('from') && ! $validator->errors()->has('to')
                    && $this->filled('from') && $this->filled('to')
                    && $this->date('from')->diffInDays($this->date('to')) > 365) {
                    $validator->errors()->add('to', 'El período máximo de consulta es de 366 días.');
                }
            },
        ];
    }

    public function fromDate(): string
    {
        return $this->validated('from') ?? now()->startOfMonth()->toDateString();
    }

    public function toDate(): string
    {
        return $this->validated('to') ?? today()->toDateString();
    }
}
