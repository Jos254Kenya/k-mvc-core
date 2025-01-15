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


    public array $errors = [];

    public function loadData($data)
    {
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
    public function validate()
    {
        foreach ($this->rules() as $attribute => $rules) {
            $value = $this->{$attribute};
            foreach ($rules as $rule) {
                $ruleName = $rule;
                if (!is_string($rule)) {
                    $ruleName = $rule[0];
                }
                if($ruleName=== self::RULE_CHECKBOX && $value <1){
                    $this->addError($attribute, self::RULE_CHECKBOX);
                }
                if($ruleName===self::RULE_UPPERCASE && !preg_match('/[A-Z]/', $value)){
                    $this->addErrorByRule($attribute,self::RULE_UPPERCASE);
                }
                if($ruleName===self::RULE_LOWERCASE && !preg_match('/[a-z]/', $value)){
                    $this->addErrorByRule($attribute,self::RULE_LOWERCASE);
                }
                if($ruleName===self::RULE_SPECIAL_CHAR && !preg_match('/[^a-zA-Z\d]/', $value)){
                    $this->addErrorByRule($attribute,self::RULE_SPECIAL_CHAR);
                }
                if($ruleName===self::RULE_DIGIT && !preg_match('/\d/', $value)){
                    $this->addErrorByRule($attribute,self::RULE_DIGIT);
                }
                if ($ruleName === self::RULE_REQUIRED && !$value) {
                    $this->addErrorByRule($attribute, self::RULE_REQUIRED);
                }
                if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addErrorByRule($attribute, self::RULE_EMAIL);
                }
                if ($ruleName === self::RULE_MIN && strlen($value) < $rule['min']) {
                    $this->addErrorByRule($attribute, self::RULE_MIN, ['min' => $rule['min']]);
                }
                if ($ruleName === self::RULE_MAX && strlen($value) > $rule['max']) {
                    $this->addErrorByRule($attribute, self::RULE_MAX, ['max' => $rule['max']]);
                }
                if ($ruleName === self::RULE_MATCH && $value !== $this->{$rule['match']}) {
                    $this->addErrorByRule($attribute, self::RULE_MATCH, ['match' => $rule['match']]);
                }

                if ($ruleName === self::RULE_UNIQUE) {
                    $className = $rule['class'];
                    $uniqueAttr = $rule['attribute'] ?? $attribute;
                    $tableName = $className::tableName();
                    $db = Application::$app->db;
                    $statement = $db->prepare("SELECT * FROM $tableName WHERE $uniqueAttr = :$uniqueAttr");
                    $statement->bindValue(":$uniqueAttr", $value);
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
            self::RULE_REQUIRED => 'Please enter data in this field. This field is required',
            self::RULE_EMAIL => 'This field must contain a valid email address e.g example@karsch.com',
            self::RULE_MAX => 'The maximum length MUST not exceed {max} characters for this field',
            self::RULE_MIN => 'The minimum length is {min} characters for this field',
            self::RULE_MATCH =>'This field must be the same as {match} field',
            self::RULE_DIGIT => 'This field must contain at least one Number',
            self::RULE_LOWERCASE => 'This field MUST contain at least one Lowercase character',
            self::RULE_SPECIAL_CHAR => 'This field MUST contain at least one special character',
            self::RULE_UPPERCASE => 'This field MUST contain at least one Uppercase character',
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
}
