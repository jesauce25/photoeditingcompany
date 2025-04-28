<?php
// Function to get status display class
function getStatusClass($status)
{
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'primary';
        case 'finish':
            return 'info';
        case 'qa':
        case 'review':
            return 'info';
        case 'approved':
            return 'success';
        case 'completed':
            return 'success';
        case 'delayed':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Function to get priority display class
function getPriorityClass($priority)
{
    switch (strtolower($priority)) {
        case 'high':
            return 'danger';
        case 'medium':
            return 'warning';
        case 'low':
            return 'success';
        case 'urgent':
            return 'dark';
        default:
            return 'secondary';
    }
}
?>