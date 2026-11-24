<?php

declare(strict_types=1);

namespace Illuminate\Foundation\Http;

class FormRequest
{
    protected function prepareForValidation(): void {}

    public function validated($key = null, $default = null) {}
}
