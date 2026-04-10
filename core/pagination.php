<?php
declare(strict_types=1);

/* =====================================================
   DOCVAULT PAGINATION SYSTEM
   zentrale Pagination für alle Module
===================================================== */

/* =====================================================
   STANDARD EINTRÄGE PRO SEITE
===================================================== */

const DV_PER_PAGE = 40;


/* =====================================================
   AKTUELLE SEITE ERMITTELN
===================================================== */

function dvPage(): int
{
    return max(1, (int)($_GET['page'] ?? 1));
}


/* =====================================================
   OFFSET BERECHNEN (für SQL LIMIT)
===================================================== */

function dvOffset(int $perPage = DV_PER_PAGE): int
{
    return (dvPage() - 1) * $perPage;
}


/* =====================================================
   PAGINATION BERECHNEN
===================================================== */

function dvPagination(int $totalRows, int $perPage = DV_PER_PAGE): array
{
    $page = dvPage();

    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;

    return [
        "page"       => $page,
        "perPage"    => $perPage,
        "offset"     => $offset,
        "totalPages" => $totalPages
    ];
}


/* =====================================================
   URL PARAMETER ERHALTEN (FILTER / SORT)
===================================================== */

function dvPaginationQuery(array $extra = []): string
{
    $params = $_GET;

    unset($params["page"]);

    $params = array_merge($params, $extra);

    return http_build_query($params);
}


/* =====================================================
   PAGINATION RENDER
===================================================== */

function dvPaginationRender(int $page, int $totalPages): void
{

    if ($totalPages <= 1) {
        return;
    }

    echo '<nav class="mt-4">';
    echo '<ul class="pagination pagination-sm justify-content-center">';

    $query = dvPaginationQuery();

    $prev = max(1, $page - 1);
    $next = min($totalPages, $page + 1);

    /* vorherige Seite */

    echo '<li class="page-item '.($page==1?'disabled':'').'">';
    echo '<a class="page-link" href="?'.$query.'&page='.$prev.'">';
    echo '&laquo;';
    echo '</a>';
    echo '</li>';

    /* Seitenfenster (max 7 Seiten anzeigen) */

    $start = max(1, $page - 3);
    $end   = min($totalPages, $page + 3);

    if ($start > 1) {

        echo '<li class="page-item">';
        echo '<a class="page-link" href="?'.$query.'&page=1">1</a>';
        echo '</li>';

        if ($start > 2) {
            echo '<li class="page-item disabled">';
            echo '<span class="page-link">...</span>';
            echo '</li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {

        if ($i == $page) {

            echo '<li class="page-item active">';
            echo '<span class="page-link">'.$i.'</span>';
            echo '</li>';

        } else {

            echo '<li class="page-item">';
            echo '<a class="page-link" href="?'.$query.'&page='.$i.'">'.$i.'</a>';
            echo '</li>';

        }

    }

    if ($end < $totalPages) {

        if ($end < $totalPages - 1) {
            echo '<li class="page-item disabled">';
            echo '<span class="page-link">...</span>';
            echo '</li>';
        }

        echo '<li class="page-item">';
        echo '<a class="page-link" href="?'.$query.'&page='.$totalPages.'">'.$totalPages.'</a>';
        echo '</li>';
    }

    /* nächste Seite */

    echo '<li class="page-item '.($page==$totalPages?'disabled':'').'">';
    echo '<a class="page-link" href="?'.$query.'&page='.$next.'">';
    echo '&raquo;';
    echo '</a>';
    echo '</li>';

    echo '</ul>';
    echo '</nav>';
}