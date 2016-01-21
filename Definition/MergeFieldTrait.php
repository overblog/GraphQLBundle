<?php

namespace Overblog\GraphBundle\Definition;

trait MergeFieldTrait
{
    protected function getFieldsWithDefaults($fields, array $defaultFields)
    {
        return function() use ($fields, $defaultFields) {
            if (empty($fields)) {
                return $defaultFields;
            }

            if (is_callable($fields)) {
                $fields = $fields();
            }

            if (!is_array($fields)) {
                $fields = [$fields];
            }

            return array_merge($fields, $defaultFields);
        };
    }
}
