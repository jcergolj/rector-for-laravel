<?php

namespace App\Requests {
    abstract class AppFormRequest extends \Illuminate\Foundation\Http\FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    }
}

namespace App\Accounts {
    use Other\Requests\{BaseRequest as AppFormRequest};

    class StoreRequest extends \Illuminate\Foundation\Http\FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    }
}
