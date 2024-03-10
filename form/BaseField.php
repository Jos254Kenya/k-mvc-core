<?php


namespace sigawa\mvc-core\core\form;


use sigawa\mvc-core\core\Model;

abstract class BaseField
{
    public Model $model;
    public string $attribute;
    public string $type;

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
            $this->renderInput(),
            $this->model->getLabel($this->attribute),
            $this->model->getFirstError($this->attribute)
        );
    }

    abstract public function renderInput();

}