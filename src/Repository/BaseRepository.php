<?php

namespace gersonalves\laravelBase\Repository;

use gersonalves\laravelBase\Helpers\fileEnum;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

abstract class BaseRepository implements BaseRepositoryInterface
{
    private $model;

    protected ?array $storeFile = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function get(?int $id = null)
    {
        if ($id) {
            return $this->model = $this->model->find($id);
        }

        return $this->model = $this->model->get();
    }

    public function findBy(string $column, mixed $value)
    {
        return $this->model = $this->model->where($column, '=', $value)->firstOrFail();
    }

    public function withRelations(): baseRepository
    {
        if (method_exists(get_class($this->model), 'scopeWithRelations')) {
            $this->model = $this->model->withRelations();
        }

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function update(array|Request $data)
    {
        if (is_array($data)) {
            $data = new Request($data);
        }

        return $this->model = $this->store($data);
    }

    public function store(array|Request $data)
    {
        if (is_array($data)) {
            $data = new Request($data);
        }
        $data = $this->storeAndCastFiles($data);
        if ($data->get($this->getModel()->getKeyName())) {
            $this->model->updateOrCreate(
                $this->getPrimaryKeyWithValue($data),
                $this->getStoreContent($data)
            );
        } else {
            return $this->setModel($this->model->create(
                $this->getStoreContent($data)
            )->toArray());
        }

        return $this->setModel($data);

    }

    private function setModel(array|Request $data)
    {
        if (is_array($data)) {
            $data = new Request($data);
        }

        if (! $data->exists($this->getModel()->getKeyName())) {
            $myModel = new $this->model();
            $myModel->fill($data->all());
        } else {
            $myModel = new $this->model();
            $myModel = $myModel->find($data->get($this->getModel()->getKeyName()));
        }

        return $this->model = $myModel;
    }

    private function getStoreContent(array|Request $data)
    {
        $myFillable = array_merge($this->getModel()->getFillable(), [$this->getModel()->getKeyName()]);
        $itensToUpdate = array_intersect(
            array_keys($data->all()), $myFillable
        );

        return Collection::make(
            array_flip(
                Collection::make(array_keys($itensToUpdate))
                    ->map(function ($item) use ($itensToUpdate) {
                        return $itensToUpdate[$item];
                    })->toArray()))
            ->map(function ($item, $key) use ($data) {
                return $data->get($key);
            })->toArray();
    }

    private function getPrimaryKeyWithValue(array|Request $data): array
    {
        return [$this->getModel()->getKeyName() => $data->get($this->getModel()->getKeyName())];
    }

    public function destroy(int $id)
    {
        $this->getModel()->destroy($id);
    }

    private function storeAndCastFiles(array|Request $data): Request
    {
        if (is_array($data)) {
            $data = new Request($data);
        }

        if (count($this->storeFile)) {
            foreach ($this->storeFile as $column => $settings) {
                if ($data[$column] && is_base64($data[$column]) && $settings['type'] === fileEnum::BASE64_IMAGE) {
                    $data[$column] = $this->saveFile($data[$column], $settings['path'] ?? 'public/');
                }
            }
        }

        return $data;
    }

    public function saveFile($imagem, $path, $filename = null): string|UrlGenerator|Application
    {
        $pos = strpos($imagem, ';');
        $type = explode(':', substr($imagem, 0, $pos))[1];
        $type = str_replace(['image/', 'application/'], '', $type);
        $filename = ($filename ?? Uuid::uuid4()).'.'.$type;

        $imagem = trim($imagem);
        $imagem = str_replace('data:image/png;base64,', '', $imagem);
        $imagem = str_replace('data:image/jpg;base64,', '', $imagem);
        $imagem = str_replace('data:image/jpeg;base64,', '', $imagem);
        $imagem = str_replace('data:image/gif;base64,', '', $imagem);
        $imagem = str_replace('data:application/pdf;base64,', '', $imagem);
        $imagem = str_replace(' ', '+', $imagem);

        Storage::disk('local')->put($path.$filename, base64_decode($imagem));

        return url('storage/'.str_replace('public/', '', ltrim($path, '/')).$filename);
    }
}
