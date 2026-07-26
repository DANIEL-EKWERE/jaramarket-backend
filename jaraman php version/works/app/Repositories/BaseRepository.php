<?php

namespace App\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    public $model;

    /**
     * Get base All query.
     */
    protected function builderAll(): Builder
    {
        return $this->model->query();
    } //end builderAll()

    /**
     * Get a single instance of the Model.
     */
    protected function builderSingle(int $id): Builder
    {
        return $this->model->query()->where('id', $id);
    } //end builderSingle()

    /**
     * Get base all query with eager loading relationships.
     *
     * @param  string|array  $eager
     */
    protected function buildAllWith($eager): Builder
    {
        if (is_array($eager) === false) {
            $eager = [$eager];
        }

        return $this->builderAll()->with($eager);
    } //end buildAllWith()

    /**
     * Get a single instance of the model with eager loading relationships.
     *
     * @param  string|array  $eager
     */
    protected function buildSingleWith(int $id, $eager): Builder
    {
        if (is_array($eager) === false) {
            $eager = [$eager];
        }

        return $this->builderSingle($id)->with($eager);
    } //end buildSingleWith()

    /**
     * Get all entries for the model.
     */
    public function getAll(): Collection
    {
        return $this->builderAll()->get();
    } //end getAll()

    /**
     * Get a single instance of the Model.
     */
    public function getSingle(int $id): ?Model
    {
        return $this->builderSingle($id)->first();
    } //end getSingle()

    /**
     * Get a single instance of the Model.
     */
    public function getSingleWith(int $id, array $eager): ?Model
    {
        return $this->buildSingleWith($id, $eager)->first();
    } //end getSingle()

    /**
     * Get a new version of the Model.
     */
    public function newModel(array $params): Model
    {
        return $this->model->newInstance($params);
    } //end newModel()

    /**
     * Insert Into Model.
     *
     * @return Model
     */
    public function insert(array $params): bool
    {
        return $this->builderAll()->insert($params);
    } //end insert()

    /**
     * Insert Into Model.
     *
     * @return Model
     */
    public function insertOrIgnore(array $params): bool
    {
        return $this->builderAll()->insertOrIgnore($params);
    } //end insert()

    /**
     * Save the model.
     */
    public function saveModel(Model $model): void
    {
        $model->save();
    } //end saveModel()

    /**
     * Delete the Model.
     *
     *
     * @throws Exception
     */
    public function deleteModel(Model $model): void
    {
        $model->delete();
    } //end deleteModel()

    public function find(int $id): Model
    {
        return $this->builderSingle($id)->first();
    } //end find()

    /**
     * Get instance of model query builder.
     */
    public function queryBuilder(): Builder
    {
        return $this->builderAll();
    } //end queryBuilder()
}//end class
