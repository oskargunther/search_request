<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 19.12.2017
 * Time: 10:29
 */

namespace Search\Repository;

use Search\Request\Helper\FilterFieldInterface;
use Search\Request\Helper\SortInterface;
use Search\Request\SearchRequestInterface;
use Doctrine\ORM\QueryBuilder;
use \Exception;

abstract class SearchDynamicRepository extends SearchRepository
{
    /** @var string[] */
    private $joins;

    abstract public function getRelations(): array;

    /** @return string */
    abstract public function getMainAlias();

    abstract public function getSelects(): array;

    protected function getFieldNamesTranslations()
    {
        return [];
    }

    public function getCountSelect()
    {
        return 'count('.$this->getMainAlias().')';
    }

    public function findByRequest(
        SearchRequestInterface $request,
        $oneOrNullResult = false,
        QueryBuilder $qb = null,
        Callable $addSelects = null,
        $rawReturn = false,
        $count = true,
        $selectOnlyMainClass = false
    )
    {
        $this->joins = [];
        if(!$qb) {
            $qb = $this->createQueryBuilder($this->getMainAlias());
        }

        $this->filter($qb, $request->getFilters());

        if(!$rawReturn and $count) {
            $count = $qb->select($this->getCountSelect())->getQuery()->getSingleScalarResult();
        } else {
            $count = null;
        }

        if($addSelects) {
            $addSelects($qb);
        } else {
            if($selectOnlyMainClass) {
                $qb->select($this->getMainAlias());
            } else {
                $qb->select($this->getSelects());
                $this->joinForSelect($qb);
            }
        }

        $this->paginate($qb, $request->getPagination());
        $this->sort($qb, $request->getSort());

        if($oneOrNullResult) {
            return $qb->getQuery()->getOneOrNullResult();
        }
        $items = $qb->getQuery()->getResult();

        if($rawReturn) {
            return $items;
        }

        return [
            'items' => $items,
            'total' => intval($count),
            'pagination' => [
                'currentPage' => (int) $request->getPage(),
                'pageSize' => (int) $request->getPageSize()
            ]
        ];
    }

    private function joinForSelect(QueryBuilder $qb)
    {
        foreach ($this->getSelects() as $select) {
            if(
                $select !== $this->getMainAlias() and
                !in_array($select, $this->joins)
            ) {
                $qb->leftJoin($this->getRelations()[$select] . '.' . $select, $select);
                $this->joins[] = $select;
            }
        }
    }

    protected function createExpression(QueryBuilder $qb, FilterFieldInterface $field)
    {
        if(!strpos($this->getFieldName($field->getName()), '.') !== false) {
            $fieldName = $this->getMainAlias().'.'.$this->getFieldName($field->getName());
        } else {
            $fieldName = $this->getFieldName($field->getName());
        }

        $paramName = str_replace('.','',$field->getName()).$this->paramCounter;

        if(!in_array($field->getOperator(), ['isEmpty', 'isNotEmpty','isNull','isNotNull'], true)) {
            $this->paramCounter++;
            $qb->setParameter($paramName, $field->getValue());
        }

        if(strpos($fieldName, '.') !== false) {
            $relName = explode('.', $fieldName)[0];
            if($relName !== $this->getMainAlias() and !in_array($relName, $this->joins)) {
                if(key_exists($relName, $this->getRelations())) {
                    $qb->leftJoin($this->getRelations()[$relName] . '.' . $relName, $relName);
                    $this->joins[] = $relName;
                } else {
                    throw new Exception('Cannot join table: ' . $relName);
                }
            }

        }

        return $this->filterField($qb, $field, $paramName, $fieldName);
    }

    protected function sort(QueryBuilder $qb, SortInterface $sort = null)
    {
        if($sort) {

            if(!strpos($this->getFieldName($sort->getField()), '.') !== false) {
                $qb->addOrderBy($this->getMainAlias().'.'.$this->getFieldName($sort->getField()), $sort->getDirection());
            } else {
                $fieldName = $this->getFieldName($sort->getField());

                $qb->addOrderBy($fieldName, $sort->getDirection());
                $this->joinForSelect($qb);
            }
        }
    }
}