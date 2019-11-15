<?php


namespace Search\Request\Helper;


interface FilterFieldInterface
{
    const OPERATOR_CONTAINS = 'contains';
    const OPERATOR_CONTAINS_FROM_LEFT = 'containsFromLeft';
    const OPERATOR_EQ = 'eq';
    const OPERATOR_NEQ = 'neq';
    const OPERATOR_IS_EMPTY = 'isEmpty';
    const OPERATOR_IS_NOT_EMPTY = 'isNotEmpty';
    const OPERATOR_IS_NULL = 'isNull';
    const OPERATOR_IS_NOT_NULL = 'isNotNull';
    const OPERATOR_LT = 'lt';
    const OPERATOR_LTE = 'lte';
    const OPERATOR_GT = 'gt';
    const OPERATOR_GTE = 'gte';
    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = 'notIn';
    const OPERATOR_AUTO = 'auto';

    public function getName(): string;

    public function getOperator(): string;

    public function getValue();
}