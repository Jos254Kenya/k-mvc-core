<?php


namespace sigawa\mvccore;


class Model
{

    const RULE_REQUIRED = 'required';
    const RULE_EMAIL = 'email';
    const RULE_MIN = 'min';
    const RULE_MAX = 'max';
    const RULE_MATCH = 'match';
    const RULE_UNIQUE = 'unique';
    const RULE_UPPERCASE = 'uppercase';
    const RULE_LOWERCASE = 'lowercase';
    const RULE_DIGIT = 'digit';
    const RULE_SPECIAL_CHAR = 'special_char';
    const RULE_PHONE = 'phone';
    const RULE_CHECKBOX = 'checkbox';
    const RULE_NUMERIC = 'numeric';
    const RULE_INTEGER = 'integer';
    const RULE_FLOAT = 'float';
    const RULE_BOOLEAN = 'boolean';
    const RULE_DATE = 'date';
    const RULE_URL = 'url';
    const RULE_REGEX = 'regex';
    const RULE_ALPHA = 'alpha';
    const RULE_ALPHANUMERIC = 'alphanumeric';
    const RULE_JSON = 'json';
    const RULE_IP = 'ip';
    const RULE_FILE = 'file';
    const RULE_IMAGE = 'image';
    const RULE_NUMBER = 'number';


    public array $errors = [];

    public function defaultValues():array
    {
        return [];
    }
    public function loadData($data)
    {
        // Merge default values with incoming data (incoming data takes priority)
        $data = array_merge($this->defaultValues(), $data);
    
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
    public static function findByQuery(string $query, array $params = []): array
    {
        $db = Application::$app->db->pdo;
        $stmt = $db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $results; // Returns an empty array if no records are found
    }
    public static function findOneByQuery(string $query, array $params = []): ?array
    {
        $db = Application::$app->db->pdo;
        $stmt = $db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null; // Return null if no record is found
    }
    public static function executeQuery(string $query, array $params = []): bool|int
    {
        $db = Application::$app->db->pdo;
        $stmt = $db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if ($stmt->execute()) {
            // If the query is an INSERT, return the last inserted ID.
            if (str_starts_with(strtoupper(trim($query)), 'INSERT')) {
                return (int) $db->lastInsertId();
            }
            return true; // Query executed successfully
        }

        return false; // Query failed
    }

    public function attributes(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function labels()
    {
        return [];
    }

    public function getLabel($attribute)
    {
        return $this->labels()[$attribute] ?? $attribute;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function validate($exceptId = null)
    {
        foreach ($this->rules() as $attribute => $rules) {
            $value = $this->{$attribute};
            foreach ($rules as $rule) {
                $ruleName = $rule;
                if (!is_string($rule)) {
                    $ruleName = $rule[0];
                }

                // Required field
                if ($ruleName === self::RULE_REQUIRED && empty($value)) {
                    $this->addErrorByRule($attribute, self::RULE_REQUIRED);
                }

                // Email validation
                if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addErrorByRule($attribute, self::RULE_EMAIL);
                }

                // Min & Max length validation
                if ($ruleName === self::RULE_MIN && strlen($value) < $rule['min']) {
                    $this->addErrorByRule($attribute, self::RULE_MIN, ['min' => $rule['min']]);
                }
                if ($ruleName === self::RULE_MAX && strlen($value) > $rule['max']) {
                    $this->addErrorByRule($attribute, self::RULE_MAX, ['max' => $rule['max']]);
                }

                // Numeric validation
                if ($ruleName === self::RULE_NUMERIC && !is_numeric($value)) {
                    $this->addErrorByRule($attribute, self::RULE_NUMERIC);
                }
                if ($ruleName === self::RULE_INTEGER && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addErrorByRule($attribute, self::RULE_INTEGER);
                }
                if ($ruleName === self::RULE_FLOAT && !filter_var($value, FILTER_VALIDATE_FLOAT)) {
                    $this->addErrorByRule($attribute, self::RULE_FLOAT);
                }

                // Boolean validation
                if ($ruleName === self::RULE_BOOLEAN && !is_bool(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
                    $this->addErrorByRule($attribute, self::RULE_BOOLEAN);
                }

                // Date validation
                if ($ruleName === self::RULE_DATE && !strtotime($value)) {
                    $this->addErrorByRule($attribute, self::RULE_DATE);
                }

                // URL validation
                if ($ruleName === self::RULE_URL && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addErrorByRule($attribute, self::RULE_URL);
                }

                // IP validation
                if ($ruleName === self::RULE_IP && !filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->addErrorByRule($attribute, self::RULE_IP);
                }

                // Alpha (only letters)
                if ($ruleName === self::RULE_ALPHA && !ctype_alpha(str_replace(' ', '', $value))) {
                    $this->addErrorByRule($attribute, self::RULE_ALPHA);
                }

                // Alphanumeric
                if ($ruleName === self::RULE_ALPHANUMERIC && !ctype_alnum(str_replace(' ', '', $value))) {
                    $this->addErrorByRule($attribute, self::RULE_ALPHANUMERIC);
                }

                // JSON validation
                if ($ruleName === self::RULE_JSON && json_decode($value) === null && json_last_error() !== JSON_ERROR_NONE) {
                    $this->addErrorByRule($attribute, self::RULE_JSON);
                }

                // Regex pattern validation
                if ($ruleName === self::RULE_REGEX && !preg_match($rule['pattern'], $value)) {
                    $this->addErrorByRule($attribute, self::RULE_REGEX);
                }

                // File validation (ensure uploaded)
                if ($ruleName === self::RULE_FILE && !isset($_FILES[$attribute])) {
                    $this->addErrorByRule($attribute, self::RULE_FILE);
                }

                // Image validation (ensure file is an image)
                if ($ruleName === self::RULE_IMAGE) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array(mime_content_type($_FILES[$attribute]['tmp_name']), $allowedTypes)) {
                        $this->addErrorByRule($attribute, self::RULE_IMAGE);
                    }
                }
                // ✅ Unique Validation with Optional Except ID
                if ($ruleName === self::RULE_UNIQUE) {
                    $className = $rule['class'];
                    $uniqueAttr = $rule['attribute'] ?? $attribute;
                    $tableName = $className::tableName();
                    $db = Application::$app->db;

                    $query = "SELECT * FROM $tableName WHERE $uniqueAttr = :$uniqueAttr";

                    if ($exceptId) { // Only exclude ID if provided
                        $query .= " AND id != :exceptId";
                    }

                    $statement = $db->prepare($query);
                    $statement->bindValue(":$uniqueAttr", $value);

                    if ($exceptId) {
                        $statement->bindValue(":exceptId", $exceptId);
                    }

                    $statement->execute();
                    $record = $statement->fetchObject();

                    if ($record) {
                        $this->addErrorByRule($attribute, self::RULE_UNIQUE);
                    }
                }
            }
        }
        return empty($this->errors);
    }
    /**
     * @return string[]
     */
    public function errorMessages(): array
    {
        return [

            self::RULE_UNIQUE => 'Record with this {field} or username already exists',
            self::RULE_REQUIRED => '{field} is required.',
        self::RULE_EMAIL => '{field} must be a valid email address.',
        self::RULE_MIN => '{field} must be at least {min} characters.',
        self::RULE_MAX => '{field} cannot exceed {max} characters.',
        self::RULE_NUMERIC => '{field} must be a numeric value.',
        self::RULE_INTEGER => '{field} must be an integer.',
        self::RULE_FLOAT => '{field} must be a decimal number.',
        self::RULE_BOOLEAN => '{field} must be true or false.',
        self::RULE_DATE => '{field} must be a valid date.',
        self::RULE_URL => '{field} must be a valid URL.',
        self::RULE_IP => '{field} must be a valid IP address.',
        self::RULE_ALPHA => '{field} must contain only letters.',
        self::RULE_ALPHANUMERIC => '{field} must contain only letters and numbers.',
        self::RULE_JSON => '{field} must be a valid JSON string.',
        self::RULE_REGEX => '{field} is not in the correct format.',
        self::RULE_FILE => 'A file must be uploaded for {field}.',
        self::RULE_IMAGE => '{field} must be a valid image (JPEG, PNG, GIF, WebP).',
        self::RULE_NUMBER => '{field} must be a whole number (digits only).',

        ];
    }

    public function errorMessage($rule)
    {
        return $this->errorMessages()[$rule];
    }

    protected function addErrorByRule(string $attribute, string $rule, $params = [])
    {
        $params['field'] ??= $attribute;
        $errorMessage = $this->errorMessage($rule);
        foreach ($params as $key => $value) {
            $errorMessage = str_replace("{{$key}}", $value, $errorMessage);
        }
        $this->errors[$attribute][] = $errorMessage;
    }

    public function addError(string $attribute, string $message)
    {
        $this->errors[$attribute][] = $message;
    }

    public function hasError($attribute)
    {
        return $this->errors[$attribute] ?? false;
    }

    public function getFirstError($attribute)
    {
        $errors = $this->errors[$attribute] ?? [];
        return $errors[0] ?? '';
    }
    // Existing properties and methods...

    /**
     * Get all errors as a flat array of messages.
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        $flatErrors = [];
        foreach ($this->errors as $fieldErrors) {
            $flatErrors = array_merge($flatErrors, $fieldErrors);
        }
        return $flatErrors;
    }

    /**
     * Get all errors as a single concatenated string.
     *
     * @param string $separator
     * @return string
     */
    public function getErrorString(string $separator = ', '): string
    {
        return implode($separator, $this->getErrorMessages());
    }

    /**
     * Get errors as an associative array (default behavior).
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
