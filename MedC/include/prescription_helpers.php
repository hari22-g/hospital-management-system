<?php
function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function status_badge($status)
{
    $map = [
        'Pending' => 'badge bg-warning text-dark',
        'Waiting' => 'badge bg-warning text-dark',
        'Processing' => 'badge bg-info text-dark',
        'Ready' => 'badge bg-primary',
        'Dispensed' => 'badge bg-success',
        'Completed' => 'badge bg-success',
        'Confirmed' => 'badge bg-primary',
        'In Consultation' => 'badge bg-info text-dark',
        'Cancelled' => 'badge bg-secondary',
        'Paid' => 'badge bg-success',
        'Unpaid' => 'badge bg-danger',
    ];

    $class = $map[$status] ?? 'badge bg-secondary';
    return '<span class="' . $class . '">' . escape($status ?: 'Unknown') . '</span>';
}

function payment_status_label($invoice)
{
    if (!$invoice) {
        return 'Unpaid';
    }

    $paid = (float) ($invoice['paid_amount'] ?? 0);
    $total = (float) ($invoice['total_amount'] ?? 0);

    if ($total > 0 && $paid >= $total) {
        return 'Paid';
    }

    return 'Unpaid';
}

function format_date($value)
{
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime((string) $value);
    if (!$timestamp) {
        return escape($value);
    }

    return date('d M Y', $timestamp);
}
?>