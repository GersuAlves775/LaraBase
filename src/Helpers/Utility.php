<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

if (!Collection::hasMacro('paginate')) {
    Collection::macro('paginate',
        function ($perPage = 15, $page = null, $options = []) {
            $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
            return (new LengthAwarePaginator(
                $this->forPage($page, $perPage), $this->count(), $perPage, $page, $options))
                ->withPath('');
        });
}
