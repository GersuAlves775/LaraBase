<?php

namespace gersonalves\laravelBase\Service;

use Illuminate\Http\Request;

abstract class BaseService implements BaseServiceInterface
{

    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
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
}
