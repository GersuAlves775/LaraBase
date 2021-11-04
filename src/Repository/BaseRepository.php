<?php

namespace gersonalves\laravelBase\Repository;

use gersonalves\laravelBase\Repository\BaseRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class BaseRepository implements BaseRepositoryInterface
{
    private $model;
    private $request;

    public function __construct(Model $model)
    {
        $this->request = request();
        $this->model = $model;
    }

    public function get(int $id = null)
    {
        if ($id)
            return $this->model->find($id);

        return $this->model->get();
    }

    public function withRelations(): baseRepository
    {
        if (method_exists(get_class($this->model), 'scopeWithRelations'))
            $this->model = $this->model->withRelations();
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function store(Request $data)
    {
        if ($data->get($this->getModel()->getKeyName())) {
            $this->model->updateOrCreate(
                $this->getPrimaryKeyWithValue($data),
                $this->getStoreContent($data)
            );
        } else {

            return $this->model->create(
                $this->getStoreContent($data)
            )->toArray();
        }

        return $this->getStoreContent($data);
    }

    private function getStoreContent(Request $data)
    {
        $itensToUpdate = array_intersect(
            array_keys($data->all()), $this->getModel()->getFillable()
        );
        return Collection::make(
            array_flip(
                Collection::make(array_keys($itensToUpdate))
                    ->map(function ($item) use ($itensToUpdate, $data) {
                        return $itensToUpdate[$item];
                    })->toArray()))
            ->map(function ($item, $key) use ($data) {
                return $data->get($key);
            })->toArray();
    }

    private function getPrimaryKeyWithValue(Request $data): array
    {
        return [$this->getModel()->getKeyName() => $data->get($this->getModel()->getKeyName())];
    }

    public function destroy(int $id)
    {
        $this->getModel()->destroy($id);
    }
}
