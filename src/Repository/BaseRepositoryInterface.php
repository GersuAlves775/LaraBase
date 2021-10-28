<?php

namespace gersonalves\laravelBase\Repository;

interface BaseRepositoryInterface
{
    /**
     * @param int|null $id
     */
    public function get(int $id = null);
}
