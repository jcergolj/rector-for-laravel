<?php

declare(strict_types=1);

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AfterFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function after(): array
    {
        return [fn ($validator) => $validator->errors()->add('name', 'Invalid name.')];
    }
}
