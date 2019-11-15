<?php


namespace Search\Response;


use Search\Request\SearchRequestInterface;

class PaginationResponse
{
    /**
     * @var integer
     */
    private $currentPage;

    /**
     * @var integer
     */
    private $pageSize;

    public function __construct(SearchRequestInterface $searchRequest)
    {
        $this->currentPage = $searchRequest->getPage();
        $this->pageSize = $searchRequest->getPageSize();
    }

    public function toArray(): array
    {
        return [
            'currentPage' => $this->currentPage,
            'pageSize' => $this->pageSize
        ];
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

}