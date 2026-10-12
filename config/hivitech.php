<?php

return [
    'quote_notification_email' => env('QUOTE_NOTIFICATION_EMAIL', 'contact@hivitech.local'),
    'contact_notification_email' => env('CONTACT_NOTIFICATION_EMAIL', 'contact@hivitech.local'),
    'filament_admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FILAMENT_ADMIN_EMAILS', 'christian.ngotchie@hivi-tech.com')),
    ))),
];
