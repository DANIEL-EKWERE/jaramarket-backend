<?php

return [
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),
    'project_id'  => env('FIREBASE_PROJECT_ID'),
];
