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

abstract class BaseService implements BaseServiceInterface
{

    public $repository;

    public ?array $excepts = [];
    public ?array $casts = [];
    public ?array $recursiveStore = [];

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

    public function __get(string $name)
    {
        return $this->repository->getModel()->$name;
    }

    private function get(int $id = null)
    {
        if ($id)
            return $this->repository->get($id);

        return $this->repository->get();
    }

    /**
     * @throws Exception
     */
    private function store(Request $data)
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
    private function update(Request $data)
    {
        if (count($this->recursiveStore)) {
            return $this->customStore($data);
        } else {
            return $this->repository->update($data);
        }

    }

    private function destroy(int $id)
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
    private function customStore(Request $data)
    {
        DB::beginTransaction();
        try {
            foreach ($this->recursiveStore as $repository => $settings) {
                if (!is_subclass_of($repository, BaseRepository::class, true)) {
                    throw new Exception("recurviseStore, o repositorio deve ser uma extensao de gerson/laravel-base");
                }
                $childrenRepository = new $repository();
                $childrenModel = (new ReflectionClass($childrenRepository->getModel()::class))->getShortName();
                $childrenData = $data->get(Str::snake($childrenModel));

                if ($settings === PersistEnum::BEFORE_PERSIST) {
                    $children = $childrenRepository->store(new Request($childrenData));
                    $this->mergeRequest($data, [$childrenRepository->getModel()->getKeyName() => $children[$childrenRepository->getModel()->getKeyName()]]);
                    $model = $this->repository->store($data);
                    DB::commit();
                    return array_merge($model, [Str::snake($childrenModel) => $children]);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
}
