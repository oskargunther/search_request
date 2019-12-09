<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 13.12.2017
 * Time: 15:14
 */

namespace Search\Request\Helper;

class FilterField implements HelperInterface, FilterFieldInterface
{
    /** @var  string */
    private $name;
    /** @var  mixed */
    private $value;
    /** @var  string */
    private $operator;

    public function __construct(array $data = null)
    {
        if(!empty($data)) {
            $this->name = (string) $data['name'];
            if(isset($data['value'])) {
                $this->value = $data['value'];
            }
            $this->operator = (string) $data['operator'];
        }
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'operator' => $this->operator,
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }


}