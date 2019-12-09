<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 13.12.2017
 * Time: 15:14
 */

namespace Search\Request\Helper;


class Filter implements HelperInterface, FilterInterface
{
    const LOGIC_AND = 'and';
    const LOGIC_OR = 'or';

    /** @var  string */
    private $logic;
    /** @var  FilterField[] */
    private $fields;

    public function toArray()
    {
        return [
            'logic' => $this->logic,
            'fields' => array_map(function(FilterField $field) {
                return $field->toArray();
            }, $this->fields),
        ];
    }

    public function __construct(array $data = null)
    {
        if(!empty($data)) {
            $this->setLogic((string) $data['logic']);
            $this->fields = array_map(function(array $filterField) {
                return new FilterField($filterField);
            }, $data['fields']);
        }
    }

    /**
     * @return string
     */
    public function getLogic(): string
    {
        return $this->logic;
    }

    /**
     * @param string $logic
     */
    public function setLogic($logic)
    {
        $this->logic = $logic;
    }

    /**
     * @return FilterField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(FilterField $field)
    {
        $this->fields[] = $field;
    }

    public function createField(string $fieldName, string $operator, $value = 0)
    {
        $field = new FilterField();
        $field->setName($fieldName);
        $field->setOperator($operator);
        $field->setValue($value);
        $this->addField($field);
    }

}