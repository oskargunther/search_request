<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 13.12.2017
 * Time: 14:10
 */

namespace Search\Request\Helper;

class Sort implements HelperInterface, SortInterface
{
    /**
     * @var string
     */
    private $field;
    /**
     * @var string
     */
    private $direction;

    public function __construct(array $data)
    {
        $this->field = (string) $data['field'];
        $this->direction = (string) $data['direction'];
    }

    public function toArray()
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
        ];
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @param string $direction
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }
}