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

function is_valid_date_range(?string $dateFrom, ?string $dateTo): bool
{
    if ($dateFrom !== null && $dateFrom !== '' && !is_valid_date($dateFrom)) {
        return false;
    }
    if ($dateTo !== null && $dateTo !== '' && !is_valid_date($dateTo)) {
        return false;
    }

    return $dateFrom === null || $dateFrom === ''
        || $dateTo === null || $dateTo === ''
        || $dateFrom <= $dateTo;
}

function spreadsheet_safe_text(string $value): string
{
    return preg_match('/^[\p{Z}\p{Cc}\x{FEFF}]*[=+\-@]/u', $value) === 1
        ? "'" . $value
        : $value;
}

function paginate_offset(int $page, int $perPage): int
{
    return max(0, ($page - 1) * $perPage);
}

function total_pages(int $totalRows, int $perPage): int
{
    return max(1, (int)ceil($totalRows / $perPage));
}
