<?php

namespace sigawa\mvc-core\core\form;

use sigawa\mvc-core\core\Model;
;

class Select extends BaseSelect
{
    public const CLASS_FORM_CONTROL = 'form-select';
    public string $class;
    public array $options;
    public string $valueKey;
    public string $textKey;

    public function __construct(Model $model, string $attribute, array $options = [], string $valueKey = 'id', string $textKey = 'name')
    {
        parent::__construct($model, $attribute);
        $this->options = $options;
        $this->class = self::CLASS_FORM_CONTROL;
        $this->valueKey = $valueKey;
        $this->textKey = $textKey;
    }

    public function renderSelect()
    {
        $selectHtml = sprintf('<select class="%s%s" name="%s">',
            $this->class,
            $this->model->hasError($this->attribute) ? ' is-invalid' : '',
            $this->attribute
        );

        foreach ($this->options as $option) {
            $value = $option[$this->valueKey];
            $text = $option[$this->textKey];
            $selected = $value == $this->model->{$this->attribute} ? 'selected' : '';
            $selectHtml .= sprintf('<option value="%s" %s>%s</option>', $value, $selected, $text);
        }

        $selectHtml .= '</select>';
        return $selectHtml;
    }
}


