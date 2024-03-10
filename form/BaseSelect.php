<?php

namespace sigawa\mvccore\core\form;

use sigawa\mvccore\core\Model;

abstract class BaseSelect
{
    public Model $model;
    public string $attribute;

    public function __construct(Model $model, string $attribute)
    {
        $this->model = $model;
        $this->attribute = $attribute;
    }
    public function __toString()
    {
        return sprintf('                    %s
                                   <label>%s</label>
                                   <div class="invalid-feedback">
                                       %s
                             </div>
            ',
            $this->renderSelect(),
            $this->model->getLabel($this->attribute),
            $this->model->getFirstError($this->attribute)
        );
    }
    abstract public function renderSelect();
}