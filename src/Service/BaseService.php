<?php

namespace gersonalves\laravelBase\Service;

use Carbon\Carbon;
use Exception;
use gersonalves\laravelBase\Helpers\LarabaseOptions;
use gersonalves\laravelBase\Helpers\PersistEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionException;

abstract class BaseService implements BaseServiceInterface
{
    public $repository;

    public ?array $excepts = [];

    public ?array $casts = [];

    public ?array $parentStore = [];

    public ?string $parentCallback = null;

    /**
     * @throws Exception
     */
    public function __construct($repository = null)
    {
        $this->repository = $repository;
        if (is_array($this->excepts) && count($this->excepts)) {
            $this->applyExcept();
        }
        if (is_array($this->casts) && count($this->casts)) {
            $this->applyCasts();
        }

        $this->validate();
    }

    public function __call($name, $arguments)
    {
        if ($name === 'get') {
            $this->repository->withRelations();
        }

        return call_user_func_array([$this, $name], $arguments);
    }

    public function query()
    {
        return $this->repository->withRelations();
    }

    public static function make(): static
    {
        return new static();
    }

    public function __get(string $name)
    {
        return $this->repository->getModel()->$name;
    }

    public function getModel()
    {
        return $this->repository->getModel();
    }

    protected function get(?int $id = null)
    {
        if ($id) {
            return $this->repository->get($id);
        }

        return $this->repository->get();
    }

    /**
     * @throws Exception
     */
    protected function store(Request|array $data)
    {
        if (is_array($data)) {
            $data = new Request($data);
        }

        $this->repositoryRequest->create($data->all());

        if (count($this->parentStore)) {
            return $this->customStore($data);
        } else {
            return $this->repository->store($data);
        }
    }

    /**
     * @throws Exception
     */
    protected function update(int $id, Request|array $data)
    {
        if (is_array($data)) {
            $data = new Request($data);
        }

        $this->repositoryRequest->update($data->all());
        $this->mergeRequest($data, [$this->getModel()->getKeyName() => $id]);

        if (count($this->parentStore)) {
            return $this->customStore($data);
        } else {
            return $this->repository->update($data);
        }

    }

    protected function destroy(int $id)
    {
        return $this->repository->destroy($id);
    }

    public function mergeRequest(Request $request, array $array): Request
    {
        $request->request->add($array);
        $request->merge($array);

        return $request;
    }

    public function paginate()
    {
        return $this->repository
            ->withRelations()
            ->get()
            ->paginate(request()->request->get('limit') ?? 10, request()->request->get('page') ?? 1);
    }

    public function applyExcept()
    {
        foreach ($this->excepts as $index => $except) {
            request()->request->remove($except);
        }
    }

    public function applyCasts()
    {
        foreach ($this->casts as $index => $cast) {
            $data = request()->get($index);
            $newValue = null;
            switch ($cast) {
                case 'password':
                    $newValue = Hash::make($data);
                    break;
                case 'date':
                case 'datetime':
                    $newValue = Carbon::parse($data);
                    break;
                case 'money':
                    $newValue = $data * 100;
                    break;
            }
            $this->mergeRequest(request(), [$index => $newValue]);
        }
    }

    /**
     * @throws Exception
     */
    protected function customStore(Request|array $data)
    {
        if (is_array($data)) {
            $data = new Request($data);
        }

        DB::beginTransaction();
        try {
            $relationBag = [];
            foreach ($this->parentStore as $service => $settings) {
                $this->customValidations($settings, $service);
                $persist = $settings['persist'];
                if ($persist === PersistEnum::BEFORE_PERSIST) {
                    $childrenModelName = lcfirst($this->persistBefores($service, $settings, $data));
                    $relationName = $settings['customRelationName'] ?? $childrenModelName;
                    $relationBag[] = $relationName;
                }
            }

            $modelKeyName = $this->repository->getModel()->getKeyName();
            $model = $this->repository->store($data);

            if (empty($model->$modelKeyName)) {
                throw new Exception("Voce precis adicionar {$modelKeyName} ao 'fillable' de sua model.");
            }

            foreach ($this->parentStore as $service => $settings) {
                $this->customValidations($settings, $service);
                $persist = $settings['persist'];
                if ($persist === PersistEnum::AFTER_PERSIST) {
                    if (array_key_exists('options', $settings) && in_array(LarabaseOptions::SYNC, $settings['options'])) {
                        $relationName = $this->persistSync($model, $service, $settings, $data, ...['key' => $modelKeyName, 'value' => $model->$modelKeyName]);
                    }
                    $childrenModelName = lcfirst($this->persistAfters($service, $settings, $data, ...['key' => $modelKeyName, 'value' => $model->$modelKeyName]));
                    $relationName = Str::snake($settings['customRelationName'] ?? $childrenModelName);
                    $relationBag[] = $relationName;
                }
            }

            DB::commit();

            $relationBag = array_filter($relationBag, fn ($value) => ! is_null($value) && $value !== '');

            $latest = $model->
            with($relationBag)
                ->where($modelKeyName, $model->$modelKeyName)
                ->latest()->firstOrFail();

            if (! is_null($this->parentCallback) && method_exists($this, $this->parentCallback)) {
                $callbackResponse = call_user_func_array([$this, $this->parentCallback], [$latest]);
                if ($callbackResponse) {
                    return $callbackResponse;
                }
            }

            return $latest;
        } catch (ValidationException $e) {
            throw new ValidationException($e->validator, $e->response, $e->errorBag);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws ReflectionException
     */
    private function persistBefores($service, $settings, $data): string
    {
        $childrenService = new $service();
        $childrenModel = (new ReflectionClass($childrenService->getModel()::class))->getShortName();
        $childrenData = $data->get(Str::snake($childrenModel));

        if (empty($childrenData)) {
            return '';
        }

        $childrenKeyName = $childrenService->getModel()->getKeyName();
        $children = $childrenService->store(new Request($childrenData));
        $this->mergeRequest($data, [$childrenKeyName => $children[$childrenKeyName]]);

        return $childrenModel;
    }

    /**
     * @throws ReflectionException
     */
    private function persistSync($model, $service, $settings, $data, ...$options): string
    {
        $primaryKey = $this->getModel()->getKeyName();
        $childrenService = new $service();
        $childrenModel = Str::snake((new ReflectionClass($childrenService->getModel()::class))->getShortName());
        $childrenData = $data->get(Str::snake($childrenModel));

        $keeps = collect($childrenData)->map(function ($d) use ($childrenService) {
            if (! array_key_exists($childrenService->getModel()?->getKeyName(), $d)) {
                return 0;
            }

            return $d[$childrenService->getModel()?->getKeyName()];
        });

        $model->$childrenModel()
            ->whereNotIn($childrenService->getModel()->getKeyName(), $keeps->toArray())
            ->get()
            ->map(function ($map) {
                if (property_exists($map, 'pivot') || $map->pivot) {
                    if ($map->pivot->map) {
                        $map->pivot->map->delete();
                    } else {
                        $map->pivot->delete();
                    }
                } else {
                    $map->delete();
                }
            });

        return $childrenModel;
    }

    /**
     * @throws ReflectionException
     */
    private function persistAfters($service, $settings, $data, ...$options): string
    {
        $childrenService = new $service();
        $childrenModel = (new ReflectionClass($childrenService->getModel()::class))->getShortName();
        $childrenData = $data->get(Str::snake($childrenModel));

        if (empty($childrenData)) {
            return '';
        }

        if (array_filter($childrenData, fn ($content) => is_array($content))) {
            foreach ($childrenData as $index => $childrenDatum) {
                $childrenDatum = array_merge($childrenDatum, [$options['key'] => $options['value']]);
                $childrenService->store(new Request($childrenDatum));
            }
        } else {
            $childrenData = array_merge($childrenData, [$options['key'] => $options['value']]);
            $childrenService->store(new Request($childrenData));
        }

        return $childrenModel;
    }

    public function customValidations($settings, $service)
    {
        if (! isset($settings['persist'])) {
            throw new \Exception('O persist precisa ser definido');
        }

        if (! is_subclass_of($service, BaseService::class, true)) {
            $childrenService = new $service();
            $childrenModel = (new ReflectionClass($childrenService->getModel()::class))->getShortName();
            throw new Exception($childrenModel.', deve extender de de gerson/laravel-base BaseService');
        }

        if (! is_null($this->parentCallback) && ! method_exists($this, $this->parentCallback)) {
            throw new \Exception("O callback {$this->parentCallback} foi informado mas o metodo nao existe.");
        }
    }

    private function validate()
    {
        if (! $this->repositoryRequest || ! class_exists($this->repositoryRequest::class)) {
            $className = (new ReflectionClass($this->repository))->getShortName();
            throw new Exception("O atributo repositoryRequest não existe ou não é uma classe em {$className}");
        }
    }
}
