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
    const RULE_NULLABLE = 'nullable';
    const RULE_FILE = 'file';
    const RULE_IMAGE = 'image';
    const RULE_NUMBER = 'number';
    const RULE_STRING = 'string';
    const RULE_ARRAY = 'array';
    const RULE_OBJECT = 'object';
    const RULE_IN = 'in';
    const RULE_TIME = 'time';
    const RULE_TIMEZONE = 'timezone';
    const RULE_DATETIME = 'datetime';
    const RULE_SAFE = 'safe'; // For attributes that are not validated but should be saved
    const RULE_NOT = 'not'; // For attributes that should not be validated
    const RULE_RANGE = 'range'; // For numeric attributes that should be within a certain range
    const RULE_HAS_ONE = 'has_one'; // For relationships with one-to-one mapping
    const RULE_HAS_MANY = 'has_many'; // For relationships with one-to-many mapping
    const RULE_BELONGS_TO = 'belongs_to';
    const RULE_BELONGS_TO_MANY = 'belongs_to_many';
    const RULE_PASSWORD = 'Passw0rd#';
    public function beforeValidate(): void {}
    /**
     * Fetch related model(s) for HAS_ONE, HAS_MANY, BELONGS_TO, BELONGS_TO_MANY.
     *
     * @param string $relationName
     * @return mixed
     */
    public function getRelation(string $relationName)
    {
        $relations = $this->relations();
        if (!isset($relations[$relationName])) {
            throw new \Exception("Relation '$relationName' not defined.");
        }

        [$rule, $relatedModelClass, $foreignKeyOrPivot, $relatedKey] = $relations[$relationName] + [null, null, null, null];

        switch ($rule) {
            case self::RULE_HAS_ONE:
                // $foreignKeyOrPivot: foreign key in related model
                return $relatedModelClass::findOneByQuery(
                    "SELECT * FROM {$relatedModelClass::tableName()} WHERE $foreignKeyOrPivot = :val LIMIT 1",
                    ['val' => $this->id]
                );
            case self::RULE_HAS_MANY:
                // $foreignKeyOrPivot: foreign key in related model
                return $relatedModelClass::findByQuery(
                    "SELECT * FROM {$relatedModelClass::tableName()} WHERE $foreignKeyOrPivot = :val",
                    ['val' => $this->id]
                );
            case self::RULE_BELONGS_TO:
                // $foreignKeyOrPivot: local foreign key, $relatedKey: related model PK
                $foreignKeyValue = $this->{$foreignKeyOrPivot} ?? null;
                if ($foreignKeyValue === null) return null;
                return $relatedModelClass::findOneByQuery(
                    "SELECT * FROM {$relatedModelClass::tableName()} WHERE $relatedKey = :val LIMIT 1",
                    ['val' => $foreignKeyValue]
                );
            case self::RULE_BELONGS_TO_MANY:
                // $foreignKeyOrPivot: pivot table, $relatedKey: [localKey, relatedKey]
                [$localKey, $relatedKey] = $relatedKey;
                $pivotTable = $foreignKeyOrPivot;
                $sql = "SELECT r.* FROM {$relatedModelClass::tableName()} r
                        JOIN $pivotTable p ON r.id = p.$relatedKey
                        WHERE p.$localKey = :val";
                return $relatedModelClass::findByQuery($sql, ['val' => $this->id]);
            default:
                throw new \Exception("Unknown relation rule '$rule' for '$relationName'.");
        }
    }
    public array $errors = [];

    public function defaultValues(): array
    {

        return [];
    }
    public int $id = 0;
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

        $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

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
    // relations function
    public function relations()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function validate($exceptId = null)
    {
        $this->beforeValidate();
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
                // rule string
                if ($ruleName === self::RULE_STRING && !is_string($value)) {
                    $this->addErrorByRule($attribute, self::RULE_STRING);
                }
                // RULE_PASSWORD
                if ($ruleName === self::RULE_PASSWORD) {
                    $hasUppercase = preg_match('/[A-Z]/', $value);
                    $hasLowercase = preg_match('/[a-z]/', $value);
                    $hasDigit = preg_match('/\d/', $value);
                    $hasSpecialChar = preg_match('/[\W_]/', $value); // non-word characters, includes !@# etc.
                    $hasMinLength = strlen($value) >= 8;

                    if (!($hasUppercase && $hasLowercase && $hasDigit && $hasSpecialChar && $hasMinLength)) {
                        $this->addErrorByRule($attribute, self::RULE_PASSWORD);
                    }
                }
                // RULE_RANGE
                if ($ruleName === self::RULE_RANGE && (!is_numeric($value) || $value < $rule['min'] || $value > $rule['max'])) {
                    $this->addErrorByRule($attribute, self::RULE_RANGE, ['min' => $rule['min'], 'max' => $rule['max']]);
                }
                //rule safe
                if ($ruleName === self::RULE_SAFE) {
                    // No error, as safe attributes are not validated
                }
                // rule not
                if ($ruleName === self::RULE_NOT) {
                    // No error, as not attributes are not validated
                }
                // rule array
                if ($ruleName === self::RULE_ARRAY && !is_array($value)) {
                    $this->addErrorByRule($attribute, self::RULE_ARRAY);
                }
                // rule object
                if ($ruleName === self::RULE_OBJECT && !is_object($value)) {
                    $this->addErrorByRule($attribute, self::RULE_OBJECT);
                }
                // rRULE_DATETIME
                if ($ruleName === self::RULE_DATETIME && !strtotime($value)) {
                    $this->addErrorByRule($attribute, self::RULE_DATETIME);
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
                if (
                    $ruleName === self::RULE_IN &&
                    is_array($rule) &&
                    isset($rule['range']) &&
                    !in_array($value, $rule['range'], true)
                ) {
                    $this->addErrorByRule($attribute, self::RULE_IN, ['range' => implode(', ', $rule['range'])]);
                }

                // Boolean validation
                if ($ruleName === self::RULE_BOOLEAN && !is_bool(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
                    $this->addErrorByRule($attribute, self::RULE_BOOLEAN);
                }

                // Date validation
                if ($ruleName === self::RULE_DATE && !strtotime($value)) {
                    $this->addErrorByRule($attribute, self::RULE_DATE);
                }
                // Phone number validation
                if ($ruleName === self::RULE_PHONE && !preg_match('/^\+?[0-9]{7,15}$/', $value)) {
                    $this->addErrorByRule($attribute, self::RULE_PHONE);
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
                // RULE_NULLABLE
                if ($ruleName === self::RULE_NULLABLE && $value === null) {
                    // No error, as null is allowed
                }
                // RULE_UPPERCASE
                if ($ruleName === self::RULE_UPPERCASE && $value !== strtoupper($value)) {
                    $this->addErrorByRule($attribute, self::RULE_UPPERCASE);
                }
                // RULE_TIME
                if ($ruleName === self::RULE_TIME && !preg_match('/^(0[0-9]|1[0-2]):[0-5][0-9] (AM|PM)$/', $value)) {
                    $this->addErrorByRule($attribute, self::RULE_TIME);
                }
                // RULE_TIMEZONE
                if ($ruleName === self::RULE_TIMEZONE && !in_array($value, timezone_identifiers_list())) {
                    $this->addErrorByRule($attribute, self::RULE_TIMEZONE);
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
                // RULE_HAS_ONE and RULE_HAS_MANY
            }
        }
        return empty($this->errors);
    }
    // validate relations function
    public function validateRelations(): void
    {
        foreach ($this->relations() as $attribute => $rule) {
            [$ruleName, $relatedModelClass, $relatedAttribute] = $rule + [null, null, null];

            if (!in_array($ruleName, [self::RULE_HAS_ONE, self::RULE_HAS_MANY], true)) {
                continue; // Skip if not a recognized relationship rule
            }

            if (!$relatedModelClass || !class_exists($relatedModelClass)) {
                throw new \Exception("Invalid related model class '$relatedModelClass' in relation '$attribute'.");
            }

            $foreignKeyValue = $this->$attribute ?? null;

            // Optional: enforce required for HAS_ONE
            if ($ruleName === self::RULE_HAS_ONE && $foreignKeyValue === null) {
                $this->addError($attribute, "This relationship is required.");
                continue;
            }

            // Skip nullable relationships
            if ($foreignKeyValue === null) {
                continue;
            }

            // DB existence check
            $tableName = $relatedModelClass::tableName();
            $db = \sigawa\mvccore\Application::$app->db;
            $query = "SELECT COUNT(*) FROM $tableName WHERE $relatedAttribute = :val";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':val', $foreignKeyValue);
            $stmt->execute();

            if ((int)$stmt->fetchColumn() === 0) {
                $this->addError($attribute, "Related record not found in '$relatedModelClass'.");
            }
            // Optional: self-reference check (for self-relations like parent_account_id)
            if (
                $relatedModelClass === static::class &&
                $foreignKeyValue == $this->id &&
                $relatedAttribute === 'id'
            ) {
                $this->addError($attribute, "Cannot reference itself in relationship.");
            }
        }
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
            self::RULE_PHONE => '{field} must be a valid Phone Number.',
            self::RULE_IP => '{field} must be a valid IP address.',
            self::RULE_ALPHA => '{field} must contain only letters.',
            self::RULE_ALPHANUMERIC => '{field} must contain only letters and numbers.',
            self::RULE_JSON => '{field} must be a valid JSON string.',
            self::RULE_REGEX => '{field} is not in the correct format.',
            self::RULE_FILE => 'A file must be uploaded for {field}.',
            self::RULE_IMAGE => '{field} must be a valid image (JPEG, PNG, GIF, WebP).',
            self::RULE_NUMBER => '{field} must be a whole number (digits only).',
            self::RULE_STRING => '{field} must be a string.',
            self::RULE_ARRAY => '{field} must be an array.',
            self::RULE_OBJECT => '{field} must be an object.',
            self::RULE_IN => '{field} must be one of the following: {range}.',
            self::RULE_UPPERCASE => '{field} must be in uppercase.',
            self::RULE_LOWERCASE => '{field} must be in lowercase.',
            self::RULE_TIME => '{field} must be in the format HH:MM AM/PM.',
            self::RULE_TIMEZONE => '{field} must be a valid timezone.',
            self::RULE_DATETIME => '{field} must be a valid date and time.',
            self::RULE_RANGE => '{field} must be between {min} and {max}.',
            self::RULE_PASSWORD => '{field} must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.',

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
