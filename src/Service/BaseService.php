<?php

namespace gersonalves\laravelBase\Service;

use Exception;
use gersonalves\laravelBase\Helpers\PersistEnum;
use gersonalves\laravelBase\Repository\BaseRepository;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Illuminate\Support\Facades\Hash;

abstract class BaseService implements BaseServiceInterface
{

    public $repository;

    public ?array $excepts = [];
    public ?array $casts = [];
    public ?array $recursiveStore = [];
    public string|null $recursiveCallBack = null;

    public function __construct($repository = null)
    {
        $this->repository = $repository;
        if (is_array($this->excepts) && count($this->excepts))
            $this->applyExcept();
        if (is_array($this->casts) && count($this->casts))
            $this->applyCasts();
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

    public function __get(string $name)
    {
        return $this->repository->getModel()->$name;
    }

    public function getModel()
    {
        return $this->repository->getModel();
    }

    protected function get(int $id = null)
    {
        if ($id)
            return $this->repository->get($id);

        return $this->repository->get();
    }

    /**
     * @throws Exception
     */
    protected function store(Request $data)
    {
        if (count($this->recursiveStore)) {
            return $this->customStore($data);
        } else {
            return $this->repository->store($data);
        }
    }

    /**
     * @throws Exception
     */
    protected function update(Request $data)
    {
        if (count($this->recursiveStore)) {
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
    protected function customStore(Request $data)
    {
        DB::beginTransaction();
        try {
            $relationBag = [];
            foreach ($this->recursiveStore as $repository => $settings) {
                $this->customValidations($settings, $repository);
                $persist = $settings['persist'];
                if ($persist === PersistEnum::BEFORE_PERSIST) {
                    $childrenModelName = lcfirst($this->persistBefores($repository, $settings, $data));
                    $relationName = $settings['customRelationName'] ?? $childrenModelName;
                    $relationBag[] = $relationName;
                }
            }

            $modelKeyName = $this->repository->getModel()->getKeyName();
            $model = $this->repository->store($data);

            if (empty($model->$modelKeyName)) {
                throw new Exception("Voce precis adicionar {$modelKeyName} ao 'fillable' de sua model.");
            }


            foreach ($this->recursiveStore as $repository => $settings) {
                $this->customValidations($settings, $repository);
                $persist = $settings['persist'];
                if ($persist === PersistEnum::AFTER_PERSIST) {
                    $childrenModelName = lcfirst($this->persistAfters($repository, $settings, $data, ...['key' => $modelKeyName, 'value' => $model->$modelKeyName]));
                    $relationName = $settings['customRelationName'] ?? $childrenModelName;
                    if (!method_exists($model, $relationName)) {
                        $modelClass = $this->repository->getModel()::class;
                        throw new Exception("A relacao {$relationName} precisa existir em {$modelClass}");
                    }
                    $relationBag[] = $relationName;
                }
            }

            DB::commit();


            $relationBag = array_filter($relationBag, fn($value) => !is_null($value) && $value !== '');

            $latest = $model->
            with($relationBag)
                ->where($modelKeyName, $model->$modelKeyName)
                ->latest()->firstOrFail();

            if (!is_null($this->recursiveCallBack) && method_exists($this, $this->recursiveCallBack)) {
                $callbackResponse = call_user_func_array([$this, $this->recursiveCallBack], [$latest]);
                if ($callbackResponse)
                    return $callbackResponse;
            }

            return $latest;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }


    /**
     * @throws ReflectionException
     */
    private function persistBefores($repository, $settings, $data): string
    {
        $childrenRepository = new $repository();
        $childrenModel = (new ReflectionClass($childrenRepository->getModel()::class))->getShortName();
        $childrenData = $data->get(Str::snake($childrenModel));

        if (empty($childrenData))
            return '';

        $childrenKeyName = $childrenRepository->getModel()->getKeyName();
        $children = $childrenRepository->store(new Request($childrenData));
        $this->mergeRequest($data, [$childrenKeyName => $children[$childrenKeyName]]);

        return $childrenModel;
    }

    /**
     * @throws ReflectionException
     */
    private function persistAfters($repository, $settings, $data, ...$options): string
    {
        $childrenRepository = new $repository();
        $childrenModel = (new ReflectionClass($childrenRepository->getModel()::class))->getShortName();
        $childrenData = $data->get(Str::snake($childrenModel));

        if (empty($childrenData))
            return '';

        $childrenData = array_merge($childrenData, [$options['key'] => $options['value']]);
        $childrenRepository->store(new Request($childrenData));

        return $childrenModel;
    }

    public function customValidations($settings, $repository)
    {
        if (!isset($settings['persist'])) {
            throw new \Exception("O persist precisa ser definido");
        }

        if (!is_subclass_of($repository, BaseRepository::class, true)) {
            $childrenRepository = new $repository();
            $childrenModel = (new ReflectionClass($childrenRepository->getModel()::class))->getShortName();
            throw new Exception($childrenModel . ", deve extender de de gerson/laravel-base BaseRepository");
        }

        if (!is_null($this->recursiveCallBack) && !method_exists($this, $this->recursiveCallBack))
            throw new \Exception("O callback {$this->recursiveCallBack} foi informado mas o metodo nao existe.");
    }

}
