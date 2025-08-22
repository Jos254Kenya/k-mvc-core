<?php  
namespace sigawa\mvccore\constants;

class FileUploader
{
    private string $uploadDir;
    private ?array $rules;
    private int $maxSize;

    public function __construct(string $uploadDir, array $rules = [], int $maxSize = 10)
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/'; // Ensure trailing slash
        $this->rules = $rules;
        $this->maxSize = ($rules['maxSize'] ?? $maxSize) * 1024 * 1024; // Convert MB to Bytes
    }

    private function validateFile(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("File upload error: " . $file['error']);
        }

        // Sanitize the filename
        $secureFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $secureFileName)) {
            throw new \InvalidArgumentException("Invalid filename.");
        }
        // Get file extension
        $fileExt = strtolower(pathinfo($secureFileName, PATHINFO_EXTENSION));

        // Check allowed file types
        if (!empty($this->rules['allowedTypes']) && !in_array($fileExt, $this->rules['allowedTypes'], true)) {
            throw new \InvalidArgumentException("Invalid file type. Allowed: " . implode(', ', $this->rules['allowedTypes']));
        }

        // Check file size limit
        if ($file['size'] > $this->maxSize) {
            throw new \InvalidArgumentException("File exceeds maximum size of " . ($this->maxSize / 1024 / 1024) . "MB.");
        }
    }

    public function uploadFiles(array $fields): array
    {
        $uploadedFiles = [];
        
            
        // Ensure directory exists
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0777, true) && !is_dir($this->uploadDir)) {
                error_log("❌ Failed to create upload directory: " . $this->uploadDir);
                throw new \RuntimeException("Failed to create upload directory.");
            }
        }
        
        // Remove duplicate field names
        $fields = array_unique($fields);
    
        foreach ($fields as $fieldName) {
            error_log("📌 Processing field: " . $fieldName);
    
            if (!isset($_FILES[$fieldName])) {
                error_log("⚠️ No file uploaded for field: " . $fieldName);
                continue; 
            }
    
            $file = $_FILES[$fieldName];
    
            if ($file['error'] !== UPLOAD_ERR_OK) {
                error_log("❌ Upload error for field '{$fieldName}': " . $file['error']);
                continue;
            }
    
    
            try {
                // Validate file
                $this->validateFile($file);
    
                // Generate a unique file name
                $secureFileName = uniqid() . '_' . basename($file['name']);
                $destination = $this->uploadDir . $secureFileName;
    
    
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    error_log("❌ File move failed for: " . $file['tmp_name']);
                    error_log("❌ Move error details: " . print_r(error_get_last(), true));
                    throw new \RuntimeException("Failed to upload file: " . $file['name']);
                }
                   $uploadedFiles[$fieldName] = $secureFileName;
    
            } catch (\Exception $e) {
                error_log("❌ Exception while processing '{$fieldName}': " . $e->getMessage());
                return []; // Prevent partial uploads
            }
        }
        return $uploadedFiles;
    }
    
}
