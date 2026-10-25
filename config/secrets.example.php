<?php
// Phola secrets — NEVER commit this file (it is in .gitignore).
// Upload it once to public_html/config/ with cPanel File Manager; deploys leave it alone.
return [
    // Live database (cPanel → MySQL Databases)
    'db_host' => 'localhost',
    'db_name' => 'pholahea_database',
    'db_user' => 'database_nexus',
    'db_pass' => 'CHANGE_ME',

    // Local XAMPP database
    'local_db_name' => 'pholahea_database',
    'local_db_user' => 'root',
    'local_db_pass' => '',

    // Mailbox that sends reminders, enquiries and newsletters
    'smtp_host'   => 'mail.pholahealthsolutions.co.za',
    'smtp_port'   => '465',
    'smtp_user'   => 'info@pholahealthsolutions.co.za',
    'smtp_pass'   => 'CHANGE_ME',
    'admin_email' => 'info@pholahealthsolutions.co.za',

    // Random values: keep them, or every patient is signed out
    'jwt_secret'      => 'make-a-long-random-string',
    'reminder_secret' => 'make-a-long-random-string',
    'cron_key'        => 'make-a-random-string',

    // Groq API key (console.groq.com → API Keys). Can also be saved in Admin → AI & social.
    'groq_api_key' => '',
];
