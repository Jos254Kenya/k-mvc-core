<?php

namespace sigawa\mvccore\http;

class UploadedFile
{
    protected string $tmpName;
    protected string $originalName;
    protected string $type;
    protected int $size;
    protected int $error;

    public function __construct(array $file)
    {
        if (!isset($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error'])) {
            throw new \InvalidArgumentException("Invalid uploaded file structure.");
        }

        $this->tmpName = $file['tmp_name'];
        $this->originalName = $file['name'];
        $this->type = $file['type'];
        $this->size = $file['size'];
        $this->error = $file['error'];
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpName);
    }

    public function getName(): string
    {
        return $this->originalName;
    }

    public function getExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getMimeType(): string
    {
        return $this->type;
    }

    public function saveAs(string $path): bool
    {
        return move_uploaded_file($this->tmpName, $path);
    }
}
