<?php


namespace Merudairy\Fmmerudairy\core\form;



use Merudairy\Fmmerudairy\core\Model;

class Field extends BaseField
{
    const TYPE_TEXT = 'text';
    const TYPE_PASSWORD = 'password';
    const TYPE_NUMBER ='number';
    const TYPE_TIME ='time';
    const TYPE_DATE = 'date';
    const TYPE_FILE = 'file';
    const TYPE_CHECKBOX = 'checkbox';
    public const CLASS_FORM_CONTROL='form-control';
    public const CLASS_FORM='';
    public string $label;
    public string $class;
    public function __construct(Model $model, string $attribute)
    {
        $this->type = self::TYPE_TEXT;
        $this->class =self::CLASS_FORM_CONTROL;
        parent::__construct($model, $attribute);
    }
    public function renderInput()
    {
        return sprintf('<input type="%s"  class="%s%s" name="%s" value="%s">',
            $this->type,
            $this->class,
            $this->model->hasError($this->attribute) ? ' is-invalid' : '',
            $this->attribute,
            $this->model->{$this->attribute},
        );
    }

    public function passwordField()
    {
        $this->type = self::TYPE_PASSWORD;
        return $this;
    }   public function dateField()
    {
        $this->type = self::TYPE_DATE;
        return $this;
    }

    public function timeField()
    {
        $this->type = self::TYPE_TIME;
        return $this;
    }
    public function noClass()
    {
        $this->class = self::CLASS_FORM;
        return $this;
    }
    public function fileField()
    {
        $this->type = self::TYPE_FILE;
        return $this;
    }
    public function numberField()
    {
        $this->type = self::TYPE_NUMBER;
        return $this;
    }
}