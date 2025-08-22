<?php

namespace sigawa\mvccore\services;

use sigawa\mvccore\Application;
use sigawa\mvccore\db\AuditLog;
use sigawa\mvccore\repository\BaseRepository;

/**
 * Class BaseService
 *
 * A generic service layer class that wraps a repository and provides
 * higher-level business logic for use in controllers or jobs.
 */
abstract class BaseService
{
    /**
     * The repository instance.
     *
     * @var BaseRepository
     */
    protected BaseRepository $repository;

    /**
     * BaseService constructor.
     *
     * @param BaseRepository $repository
     */
    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    /**
     * Find a record by ID.
     *
     * @param mixed $id
     * @return mixed
     */
    public function find($id)
    {
        $record = $this->repository->find($id);
        return $this->transformData($record);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->withTransaction(function () use ($data) {
            $model = $this->validateAndSave($data);
            if ($model) {
                $this->logActivity('created', $model->getPrimaryKeyValue());
                return $this->transformData($model);
            }
            return null;
        });
    }

    /**
     * Update a record by ID.
     *
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        return $this->withTransaction(function () use ($id, $data) {
            $oldModel = $this->repository->find($id);
            $oldData = $oldModel ? $oldModel->toArray() : [];
            $updated = $this->repository->update($id, $data);
            if ($updated) {
                $newModel = $this->repository->find($id);
                $newData = $newModel ? $newModel->toArray() : [];
                $changes = [
                    'old' => array_diff_assoc($oldData, $newData),
                    'new' => array_diff_assoc($newData, $oldData),
                ];

                $this->logActivity('update', $id, $changes);
            }

            return $updated;
        });
    }

    /**
     * Delete a record by ID.
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool
    {
        return $this->withTransaction(function () use ($id) {
            $deleted = $this->repository->delete($id);
            if ($deleted) {
                $this->logActivity('deleted', $id);
            }
            return $deleted;
        });
    }

    /**
     * Find by custom conditions.
     *
     * @param array $conditions
     * @return mixed
     */
    public function findBy(array $conditions)
    {
        return $this->transformData($this->repository->findBy($conditions));
    }

    /**
     * Find all matching records.
     *
     * @param array $conditions
     * @return array
     */
    public function findAllBy(array $conditions): array
    {
        return array_map([$this, 'transformData'], $this->repository->findAllBy($conditions));
    }

    /**
     * Paginate results.
     *
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return array
     */
    public function paginate(int $limit = 20, int $offset = 0, array $filters = []): array
    {
        return array_map([$this, 'transformData'], $this->repository->paginate($limit, $offset, $filters));
    }
    // ================================================
    // Optional Advanced for withTransaction, validateAndSave, logActivity, transformData
    // 
    // ================================================

    /**
     * Wraps an operation inside a DB transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function withTransaction(callable $callback)
    {
        $db = Application::$app->db;
        try {
            $db->beginTransaction();
            $result = $callback();
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e; // Rethrow to controller to handle
        }
    }

    /**
     * Validate and save using model rules.
     *
     * @param array $data
     * @return mixed|null
     */
    public function validateAndSave(array $data)
    {
        $model = $this->repository->getModel();
        $model->loadData($data);

        if (!$model->validate()) {
            throw new \Exception(json_encode($model->getErrors()), 422);
        }

        if ($model->save()) {
            return $model;
        }

        return null;
    }

    /**
     * Log activity to audit_logs or any logging system.
     *
     * @param string $action
     * @param mixed $modelId
     * @param array|null $changes (optional: old/new data for diff logging)
     */
    public function logActivity(string $action, $modelId, ?array $changes = null): void
    {
        if (!class_exists(AuditLog::class)) {
            return;
        }

        $log = new AuditLog();
        $log->loadData([
            'action'       => $action,
            'entity_type'  => static::class, // e.g. "Hot360\v1\Models\Basket"
            'entity_id'    => $modelId,
            'user_id'      => Application::$app->user->id ?? null,
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'changes'      => $changes ? json_encode($changes) : null,
        ]);
        $log->save();
    }

    /**
     * Clean or reshape the model for output (API-friendly formatting).
     *
     * @param mixed $model
     * @return mixed
     */
    public function transformData($model)
    {
        return $model;
    }
}
