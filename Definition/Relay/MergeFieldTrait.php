<?php

namespace Overblog\GraphBundle\Definition\Relay;

trait MergeFieldTrait
{
    protected function getFieldsWithDefaults($fields, array $defaultFields)
    {
        return function() use ($fields, $defaultFields) {
            if (empty($fields)) {
                return $defaultFields;
            }

            if ($fields instanceof \Closure) {
                $fields = $fields();
            }

            if (!is_array($fields)) {
                $fields = [$fields];
            }

            return array_merge($fields, $defaultFields);
        };
    }
}
