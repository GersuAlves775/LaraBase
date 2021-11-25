<?php

namespace gersonalves\laravelBase\Service;

use Illuminate\Http\Request;
use Carbon\Carbon;

abstract class BaseService implements BaseServiceInterface
{

    public $repository;

    public ?array $excepts;
    public ?array $casts;

    public function __construct($repository)
    {
        $this->repository = $repository;
        if(is_array($this->excepts) && count($this->excepts))
            $this->applyExcept();
        if(is_array($this->casts) && count($this->casts))
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
        return $this->repository->$name;
    }

    private function get(int $id = null)
    {
        if ($id)
            return $this->repository->get($id);

        return $this->repository->get();
    }

    private function store(Request $data)
    {
        return $this->repository->store($data);
    }

    private function update(Request $data)
    {
        return $this->repository->update($data);
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
            switch ($cast) {
                case 'date':
                case 'datetime':
                    $this->mergeRequest(request(), [$index => Carbon::parse($data)]);
                    break;

            }
        }
    }
}
