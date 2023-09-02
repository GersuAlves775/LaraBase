<?php

namespace gersonalves\laravelBase\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait RequestTrait
{
    public array $replaceOnUpdate = [];

    public array $excludeOnUpdate = [];

    public function create($request): array
    {
        $this->validate($request);

        return Arr::only($request, array_keys($this->validators));
    }

    public function update($request): array
    {
        $replaces = collect($this->replaceOnUpdate);
        $validators = collect($this->validators);

        if (count($this->excludeOnUpdate)) {
            $validators = $validators->except($this->excludeOnUpdate);
        }

        if ($replaces->isNotEmpty()) {
            $validators = $validators->map(
                fn ($rule, $key) => $replaces->get($key) ?? $rule
            );
        }

        $this->validate($request, $validators->all());

        return Arr::only($request, array_keys($validators->all()));
    }

    private function validate(array $data, $validators = null)
    {
        $validation = Validator::make($data, $validators ?? $this->validators);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        return $validation;
    }
}
