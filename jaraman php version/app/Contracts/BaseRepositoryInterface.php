<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Get all models.
     *
     * @return Collection|Model
     */
    public function all();

    /**
     * Find model.
     */
    public function find(int $id): Model;

    /**
     * Create a new model.
     */
    public function create(array $data): Model;

    /**
     * Update model.
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete model.
     */
    public function delete(int $id): bool;

    /**
     * Get instance of model query builder.
     */
    public function queryBuilder(): Builder;
}//end interface
