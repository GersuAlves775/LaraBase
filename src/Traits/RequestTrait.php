<?php

namespace gersonalves\laravelBase\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;

trait RequestTrait
{
    public function create($request): array
    {
        $this->validate($request);
        return Arr::only($request, array_keys($this->validators));
    }

    public function update($request): array
    {
        if (property_exists($this, 'replaceOnUpdate')) {
            $replaces = $this->replaceOnUpdate;
        }
        $validators = collect($this->validators);

        if (property_exists($this, 'excludeOnUpdate') && count($this->excludeOnUpdate)) {
            $validators = $validators->except($this->excludeOnUpdate);
        }

        if (property_exists($this, 'replaceOnUpdate') && count($this->replaceOnUpdate)) {
            $this->validators = $validators->map(
                fn($rule, $key) => $replaces[$key] ?? $rule
            )->toArray();
        }

        $this->validate($request);
        return Arr::only($request, array_keys($this->validators));
    }

    private function validate(array $data)
    {
        $validation = Validator::make($data, $this->validators);

        if ($validation->fails())
            throw new ValidationException($validation);

        return $validation;
    }
}
