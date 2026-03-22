<?php
declare(strict_types=1);

function is_valid_date(?string $date): bool
{
    if ($date === null || $date === '') {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

function paginate_offset(int $page, int $perPage): int
{
    return max(0, ($page - 1) * $perPage);
}

function total_pages(int $totalRows, int $perPage): int
{
    return max(1, (int)ceil($totalRows / $perPage));
}
