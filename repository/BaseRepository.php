<?php 

namespace sigawa\mvccore\repository;
/**
 * Class BaseRepository
 *
 * A generic repository providing basic CRUD operations.
 */
abstract class BaseRepository
{
    /**
     * The model instance used by the repository.
     *
     * @var DbModel
     */
    protected $model;

    /**
     * Inject the model instance.
     *
     * @param \Sigawa\Core\DbModel $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve all records.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->model->all();
    }

    /**
     * Find a single record by its primary key.
     *
     * @param int|string $id
     * @return mixed|null
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        $instance = clone $this->model;
        $instance->loadData($data);
        return $instance->save() ? $instance : null;
    }

    /**
     * Update a record by its ID.
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        $record = $this->find($id);
        if (!$record) return false;
        $record->loadData($data);
        return $record->save();
    }
    /**
     * Delete a record by ID.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool
    {
        $record = $this->find($id);
        return $record ? $record->delete() : false;
    }

    /**
     * Soft delete a record by ID (if supported).
     *
     * @param int|string $id
     * @return bool
     */
    public function softDelete($id): bool
    {
        $record = $this->find($id);
        return $record && method_exists($record, 'softDelete')
            ? $record->softDelete($id)
            : false;
    }

    /**
     * Find one record by conditions.
     *
     * @param array $conditions
     * @return mixed|null
     */
    public function findBy(array $conditions)
    {
        return $this->model->findOne($conditions);
    }

    /**
     * Find all records matching conditions.
     *
     * @param array $conditions
     * @return array
     */
    public function findAllBy(array $conditions): array
    {
        return $this->model->findAll($conditions);
    }

    /**
     * Find a single record by conditions.
     *
     * @param array $conditions
     * @return mixed|null
     */
    public function findOne(array $conditions, ?string $orderBy=null)
    {
        return $this->model->findOne($conditions,$orderBy);
    }
    /**
     * Paginate results using offset and limit.
     *
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return array
     */
    public function paginate(int $limit = 20, int $offset = 0, array $filters = []): array
    {
        return $this->model->findWithLimitOffset($limit, $offset, $filters);
    }

    /**
     * Bulk insert multiple records.
     *
     * @param array $records
     * @return bool
     */
    public function createMany(array $records): bool
    {
        return $this->model->insertMany($records);
    }

    /**
     * Allow access to the underlying model class.
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }
}
