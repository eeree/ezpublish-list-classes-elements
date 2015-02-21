<?php

/**
 * Description of kslistclassesTableBuilder
 *
 * @copyright Copyright (C) 2015 - Kamil SzymaĹ„ski. All rights reserved.
 * @licence http://www.gnu.org/licenses/gpl-2.0.txt GNU GPLv2
 * @author Kamil SzymaĹ„ski <kamilszymanski@gmail.com>
 * @version 1.0
 * @package www
 *
 */
class kslistclassesTableBuilder
{
    const DEFAULT_NO_OF_PAGINATION_PAGES = 5;
    const DEFAULT_NO_OF_ENTRIES_PER_PAGE = 25;

    /**
     * Current page number to show.
     * 
     * @var int
     */
    private $currentPage;

    /**
     * Number of pagination elements to show
     * in pagination array.
     * 
     * @var int
     */
    private $paginationPages;

    /** 
     * Max number of pages allowed.
     * 
     * @var int
     */
    private $totalPages;

    /**
     * Number of entries per pare. Equals to "limit"
     * value in eZContentObject fetch.
     *
     * @var int
     */
    private $entriesPerPage;

    /**
     * Class ID for which we're showing entries for.
     *
     * @var int $classId
     */
    private $classId;

    /**
     * Sorting method.
     *
     * @var string
     */
    private $sortBy;

    /**
     * String that represents a sorting direction.
     * Allowed values are "asc" and "desc".
     *
     * @var string
     */
    private $orderBy;

    /**
     * Array that stores pagination elements to show on front.
     *
     * @var array
     */
    private $pagination;

    /**
     * Difference between first/last and a current page.
     *
     * @var int
     */
    private $paginationDiff;

    /**
     * First page in pagination array.
     *
     * @var int
     */
    private $paginationStart;

    /**
     * Last page in pagination array.
     *
     * @var int
     */
    private $paginationStop;

    /**
     * kslistclassestablebuilder constructor.
     *
     * @param int $classId
     * @param int $currentPage
     * @param string $sortBy
     * @param string $orderBy
     * @param int $totalCount
     * @param int $entriesPerPage
     */
    public function __construct($classId, $currentPage, $sortBy, $orderBy, $totalCount, $entriesPerPage)
    {
        $this->classId        = $classId;
        $this->currentPage    = $currentPage;
        $this->sortBy         = $sortBy;
        $this->orderBy        = $orderBy;
        $this->totalCount     = $totalCount;
        $this->entriesPerPage = $entriesPerPage;
        $this->totalPages     = ceil($this->totalCount / $this->entriesPerPage);
    }

    /**
     * Return an array with headers, classes and links.
     *
     * @param string $sortBy
     * @param string $order
     * @return array
     */
    public function getTableHeaders()
    {
        $tableHeaders = $this->getTableHeadersArray();
        $names        = array_keys($tableHeaders);

        foreach ($names as $name) {
            $tableHeaders[$name]["class"] = $this->generateCssClassForHeader($name);
            $tableHeaders[$name]["link"]  = $this->generateLinkForHeader($name);
        }

        return $tableHeaders;
    }

    /**
     * Returns a base array with header names only.
     *
     * Used only to make other methods slimmer and
     * much more readable. No logic here.
     * 
     * @return array
     */
    protected function getTableHeadersArray()
    {
        $tableHeaders = array(
            "name"     => array(
                "text" => "Name",
            ),
            "id"       => array(
                "text" => "ID",
            ),
            "path"     => array(
                "text" => "Parent Node",
            ),
            "author"   => array(
                "text" => "Created By",
            ),
            "modified" => array(
                "text" => "Modified By",
            ),
        );
        
        return $tableHeaders;
    }

    /**
     * Generates CSS class for current header.
     *
     * If result are being sorted by the current
     * column, we set the CSS class for its header
     * to 'asc' or 'desc' depending on current
     * order by method.
     * 
     * @param string $name
     * @return boolean|string
     */
    protected function generateCssClassForHeader($name)
    {
        $class = false;

        if ($this->sortBy == $name) {
            $class = $this->orderBy;
        }

        return $class;
    }

    /**
     * Generates anchor used in view.
     *
     * Generates link used in ezurl() method in template.
     * If column we're currently processing is equal to
     * the sort method it means, that we're sorting by
     * this column. We have to change the links sorting
     * direction to opposite, so if user clicks it he'll
     * sort by the same column but in different direction.
     *
     * @param string $name
     * @return string
     */
    protected function generateLinkForHeader($name)
    {
        $link                = "/classes/list/id/" . $this->classId . "/page/" . $this->currentPage . "/sort/" . $name . "/order/";
        $changeSortDirection = $this->sortBy == $name && $this->orderBy == "asc";

        if ($changeSortDirection) {
            $link .= "desc";
        } else {
            $link .= "asc";
        }

        return $link;
    }

    /**
     * Generates a pagination array from input.
     * 
     * By using this method we can get a full pagination
     * to use in view with all the elements like "First",
     * "Last", "Next", "Previous" included.
     * When there are more than 10, 50, 100, 1.000, 10.000
     * elements to paginate, we will get a different results
     * for each condition, ie. for 20 elements we will achieve
     * a pagination in form (assume we're on page 17th:
     * 1, 10, 15, 16, [17], 18, 19, 20
     * For element 1.555 for 5.000 input elements the result is:
     * 10, 50, 100, 1.000, 1.553, 1.554, [1.555], 1.556, 1.557, 4.000, 4.900, 4.950, 4.990
     * 
     * @param int $count
     * @param int $offset
     * @param int $limit
     */
    public function getPaginationArray()
    {
        $this->pagination = array();
        $this->calculateNumberOfPaginationPages();
        $this->calculatePaginationDifference();
        $this->calculateFirstPaginationElement();
        $this->calculateLastPaginationElement();
        $this->createPreviousPaginationElement();
        $this->populatePaginationArray();
        $this->createNextPaginationElement();
        return $this->pagination;
    }

    /**
     * Inserts a pagination pages into a result array.
     *
     * Pagination array consists of a "Previous" and
     * "Next" entries and a proper array. It needs to
     * be calculated every time for different number
     * of pages, that comes from SQL queries.
     */
    protected function populatePaginationArray()
    {
        $this->injectFirstElementAndEllipsis();

        for ($i = $this->paginationStart; $i <= $this->paginationStop; $i++) {
            if ($i == $this->currentPage) {
                $this->pagination[] = array(
                    "disabled" => true,
                    "text"     => $i,
                );
            } else {
                $this->pagination[] = array(
                    "disabled" => false,
                    "text"     => $i,
                    "link"     => $i,
                );
            }
        }

        $this->injectLastElementAndEllipsis();
    }

    /**
     * If user is on different than first page,
     * there's a need to show him a link to this
     * page, so he don't need to navigate trought
     * all the pages to go back.
     */
    protected function injectFirstElementAndEllipsis()
    {
        if ($this->paginationStart > (1)) {
            $this->pagination[] = array(
                "disabled" => false,
                "text"     => 1,
                "link"     => 1,
            );

            if ($this->paginationStart > (2)) {
                $this->pagination[] = array(
                    "disabled" => true,
                    "text"     => "...",
                );
            }
        }
    }

    /**
     * If user is on different than last page,
     * there's a need to show him a link to this
     * page, so he don't need to navigate trought
     * all the pages to go to the last one.
     */
    protected function injectLastElementAndEllipsis()
    {
        if ($this->paginationStop < ($this->totalPages - 1)) {
            $this->pagination[] = array(
                "disabled" => true,
                "text"     => "...",
            );
        }

        if ($this->paginationStop < ($this->totalPages)) {
            $this->pagination[] = array(
                "disabled" => false,
                "text"     => $this->totalPages,
                "link"     => $this->totalPages,
            );
        }
    }

    /**
     * Returns a difference between current and
     * first/last page. If it's even we decrease
     * it to have an equal amount of entries on
     * the beginning and the end.
     * 
     * @return int
     */
    protected function calculatePaginationDifference()
    {
        $pages = $this->paginationPages;

        if ($pages % 2) {
            $pages--;
        }

        $pageDiff             = floor($pages / 2);

        $this->paginationDiff = $pageDiff;
    }

    /**
     * Calculates a position of a first pagination page.
     *
     * First page is a little tricky. In most cases it's
     * substract of a current element and half of a
     * pagination size. But if we're on first few pages
     * we need to calculate it again, always to show
     * proper number of pages to user.
     */
    protected function calculateFirstPaginationElement()
    {
        $startPage = $this->currentPage - $this->paginationDiff;

        if ($startPage > $this->totalPages - $this->paginationPages) {
            $startPage = $this->totalPages - $this->paginationPages + 1;
        }

        if ($startPage < 1) {
            $startPage = 1;
        }

        $this->paginationStart = $startPage;
    }

    /**
     * Calculates a position of a last pagination page.
     *
     * Last page is a little tricky. In most cases it's
     * a sum of a current element and half of a pagination
     * size. But if we're on first few pages we need to
     * calculate it again, always to show proper number
     * of pages to user.
     */
    protected function calculateLastPaginationElement()
    {
        $endPage = $this->currentPage + $this->paginationDiff;

        if ($endPage < $this->paginationPages) {
            $endPage = $this->paginationPages;
        }

        if ($endPage > $this->totalPages) {
            $endPage = $this->totalPages;
        }

        $this->paginationStop = $endPage;
    }

    /**
     * Sets the "Previous" entry in pagination array.
     *
     * Checks if there is a need to show "Previous"
     * pagination element. If not, then sets the
     * element to disabled state, to style it on
     * front or insert in different tags, ie. span.
     */
    protected function createPreviousPaginationElement()
    {
        $previous = array(
            "text" => ezpI18n::tr("design/admin/navigator", "Previous"),
        );

        if ($this->currentPage == 1) {
            $previous["disabled"] = true;
            return $previous;
        }

        $previous["disabled"] = false;
        $previous["link"]     = $this->currentPage - 1;

        $this->pagination[] = $previous;
    }

    /**
     * Sets the "Next" entry in pagination array.
     *
     * Checks if there is a need to show "Next"
     * pagination element. If not, then sets the
     * element to disabled state, to style it on
     * front or insert in different tags, ie. span.
     */
    protected function createNextPaginationElement()
    {
        $next = array(
            "text" => ezpI18n::tr("design/admin/navigator", "Next"),
        );

        if ($this->currentPage >= $this->totalPages) {
            $next["disabled"] = true;
            return $next;
        }

        $next["disabled"] = false;
        $next["link"]     = $this->currentPage + 1;

        $this->pagination[] = $next;
    }

    /**
     * Returns a number of pages visible in pagination area
     * under the table.
     *
     * @return int
     */
    protected function calculateNumberOfPaginationPages()
    {
        $ezIni = eZINI::instance("kslistclasses.ini");

        if ($ezIni->hasVariable("Defaults", "PaginationCount")) {
            $numberOfPages = $ezIni->variable("Defaults", "PaginationCount");
        }

        if (!$numberOfPages || $numberOfPages < self::DEFAULT_NO_OF_PAGINATION_PAGES) {
            $numberOfPages = self::DEFAULT_NO_OF_PAGINATION_PAGES;
        }

        $this->paginationPages = $numberOfPages;
    }
}
