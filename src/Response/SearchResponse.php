<?php


namespace Search\Response;



use Search\Request\SearchRequestInterface;

class SearchResponse
{
    /**
     * @var array
     */
    private $items;

    /**
     * @var integer
     */
    private $total;

    /**
     * @var PaginationResponse
     */
    private $pagination;

    public function __construct(SearchRequestInterface $searchRequest, array $items)
    {
        $this->items = $items;
        $this->pagination = new PaginationResponse($searchRequest);
    }

    public function toArray(): array
    {
        return [
            'items' => $this->getItems(),
            'total' => $this->getTotal(),
            'pagination' => $this->getPagination()->toArray(),
        ];
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return PaginationResponse
     */
    public function getPagination(): PaginationResponse
    {
        return $this->pagination;
    }
}