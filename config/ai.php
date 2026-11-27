<?php

$model = env('AI_MODEL') ?: env('GEMINI_MODEL', 'gemini-3.5-flash-lite');

return [
    'api_key'  => env('AI_API_KEY') ?: env('GEMINI_API_KEY'),
    // ponytail: upgrade model non-aktif jika container masih pegang env lama sebelum --force-recreate
    'model'    => in_array($model, ['gemini-2.5-flash', 'gemini-3.8-flash'], true) ? 'gemini-3.5-flash-lite' : $model,
    'timeout'  => (int) env('AI_TIMEOUT', 30),
    'max_rows' => (int) env('AI_MAX_ROWS', 100),
];
