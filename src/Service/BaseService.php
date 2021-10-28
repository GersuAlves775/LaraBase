<?php

namespace gersonalves\laravelBase\Service;
use Illuminate\Http\Request;

abstract class BaseService implements baseServiceInterface
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

     public function destroy(int $id)
    {
        return $this->repository->destroy($id);
    }
}
