<?php 


namespace sigawa\mvccore\services;

use sigawa\mvccore\Application;
use sigawa\mvccore\db\DbModel;
use sigawa\mvccore\exception\ValidationException;

abstract class BaseCrudService
{
    /** @var DbModel */
    protected DbModel $model;

    /**
     * Constructor requires a DbModel instance
     */
    public function __construct(DbModel $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new record
     * @throws ValidationException
     */
    public function create(array $data): DbModel
    {
        if (!$this->model->loadData($data)) {
            throw new ValidationException(['Invalid data provided for creation.']);
        }

        if (!$this->model->validate()) {
            throw new ValidationException($this->model->getErrorMessages());
        }

        Application::$app->db->beginTransaction();
        try {
            if (!$this->model->save()) {
                throw new ValidationException(['Failed to save record.']);
            }
            Application::$app->db->commit();
        } catch (\Throwable $e) {
            Application::$app->db->rollback();
            throw $e;
        }

        return $this->model;
    }

    /**
     * Update an existing record
     * @throws ValidationException
     */
    public function update(int $id, array $data): DbModel
    {
        $existing = $this->model->findOne(['id' => $id]);
        if (!$existing) {
            throw new ValidationException(['Record not found.']);
        }

        if (!$existing->loadData($data)) {
            throw new ValidationException(['Invalid data provided for update.']);
        }

        if (!$existing->validate()) {
            throw new ValidationException($existing->getErrorMessages());
        }

        if (!$existing->save()) {
            throw new ValidationException(['Failed to update record.']);
        }

        return $existing;
    }

    /**
     * Delete a record
     * @throws ValidationException
     */
    public function delete(int $id): bool
    {
        $existing = $this->model->findOne(['id' => $id]);
        if (!$existing) {
            throw new ValidationException(['Record not found.']);
        }

        if (!$existing->delete()) {
            throw new ValidationException(['Failed to delete record.']);
        }

        return true;
    }

    /**
     * Get record by ID
     */
    public function getById(int $id): ?array
    {
        $record = $this->model->findOne(['id' => $id]);
        return $record ? $record->toArray() : null;
    }

    /**
     * Get all records optionally filtered by criteria
     */
    public function getAll(array $criteria = []): array
    {
        $results = empty($criteria)
            ? $this->model->findAll()
            : $this->model->findAll($criteria);

        return $results ? array_map(fn($item) => $item->toArray(), $results) : [];
    }
}
