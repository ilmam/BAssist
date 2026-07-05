<?php
namespace App\Attributes;
use \Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FormFieldAttribute
{
    public string $fieldType;
    public string $modelName;

    public function __construct($fieldType, $model="")
    {
        $this->fieldType = $fieldType;
        $this->modelName = $model;
    }

    // public function getRelationType()
    // {
    //     return $this->fieldType;
    // }
}