<?php


namespace Search\Request\Helper;


interface FilterFieldInterface
{
    public function getName(): string;

    public function getOperator(): string;

    public function getValue();
}