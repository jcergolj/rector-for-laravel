<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest as LaravelRequest;

abstract class AppFormRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function logPayload(): void
    {
        logger()->info($this->getContent());
    }
}

class StoreRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required'];
    }

    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}

class CustomAuthorizationRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }
}

class ParentDependentRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => $this->rulesFor(parent::class)];
    }
}

class HelperConflictRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function logPayload(): void {}
}
-----
<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest as LaravelRequest;

abstract class AppFormRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function logPayload(): void
    {
        logger()->info($this->getContent());
    }
}

class StoreRequest extends \App\Requests\AppFormRequest
{
    public function rules(): array
    {
        return ['name' => 'required'];
    }

    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}

class CustomAuthorizationRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }
}

class ParentDependentRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => $this->rulesFor(parent::class)];
    }
}

class HelperConflictRequest extends LaravelRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function logPayload(): void {}
}
