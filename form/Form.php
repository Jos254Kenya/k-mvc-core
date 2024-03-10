<?php


namespace sigawa\mvccore\form;


use sigawa\mvccore\Model;

class Form
{
    public static function begin($action, $method, $options = [])
    {
        $attributes = [];
        foreach ($options as $key => $value) {
            $attributes[] = "$key=\"$value\"";
        }
        echo sprintf('<form id="submitForm" action="%s" method="%s" %s>', $action, $method, implode(" ", $attributes));
        return new Form();
    }

    public static function end()
    {
        echo '</form>';
    }

    public function field(Model $model, $attribute)
    {
        return new Field($model, $attribute);
    }
    public function select(Model $model, $attribute, $options,$valueKey,$textname)
    {
        return new Select($model, $attribute,$options,$valueKey,$textname);
    }

}
