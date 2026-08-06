<?php
namespace App\Helpers;

class FormHelper
{

    public static function getFieldType($field)
    {
        $type = 'text';
        if (!is_array($field)) {
            //$this->info ($field);
            switch ($field)
            {
                case 'text':
                case 'textarea':
                case 'code':
                case 'select':
                case 'kt-select':
                case 'checkbox':
                case 'radio':
                case 'file':
                case 'dropzone':
                    $type = $field;
                    break;
                default:
                    $type = 'text';
            }
        } else if (isset($field["type"])){
            $type = $field["type"];
        }
        return $type;
    }

    public static function getFieldList($type, $fieldName)
    {
        switch($type)
        {
            case 'select':
            case 'kt-select':
            case 'checkbox':
                $list = "$".str_replace("_id", "List", $fieldName);
                break;
            default:
                $list = "null";
                break;
        }
        return $list;
    }

    public static function getFormFields($fields)
    {
        $formFields = [];
        foreach ($fields as $fild=>$args) {

            $fieldOptions = [];
            $fieldOptions["type"] = self::getFieldType($args[0]);
            if (isset($args["list"])) {
                $fieldOptions["list"] = $args["list"];
            }
            if (isset($args['ui_span'])) {
                $fieldOptions['ui_span'] = $args['ui_span'];
            }
            if (! empty($args['readonly'])) {
                $fieldOptions['readonly'] = true;
            }
            if (! empty($args['language'])) {
                $fieldOptions['language'] = (string) $args['language'];
            }
            if (! empty($args['help'])) {
                $fieldOptions['help'] = (string) $args['help'];
            }
            if (array_key_exists('kt_select', $args)) {
                $fieldOptions['kt_select'] = (bool) $args['kt_select'];
            }
            $formFields[$fild] = $fieldOptions;

        }
        return $formFields;
        // // if ($model->getFormFields() != null) {
        // //     return $model->formFields;
        // // } else {
        // //     return [];
        // // }
        // return $model->getFormFields();
    }

}



?>
