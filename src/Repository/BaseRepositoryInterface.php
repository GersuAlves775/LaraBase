<?php

namespace gersonalves\laravelBase\Repository;

interface BaseRepositoryInterface
{
    public function get(int|string|null $id = null);
}
