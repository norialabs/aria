<?php

declare(strict_types=1);

return [
    'provider' => env('ARIA_PROVIDER', 'openai'),
    'model' => env('ARIA_MODEL', ''),

    'connection' => env('ARIA_DB_CONNECTION'),

    'table_prefix' => env('ARIA_TABLE_PREFIX', 'aria_'),

    'tables' => [
        'conversations' => env('ARIA_TABLE_CONVERSATIONS'),
        'messages' => env('ARIA_TABLE_MESSAGES'),
        'documents' => env('ARIA_TABLE_DOCUMENTS'),
        'chunks' => env('ARIA_TABLE_CHUNKS'),
        'runs' => env('ARIA_TABLE_RUNS'),
        'spend_ledger' => env('ARIA_TABLE_SPEND_LEDGER'),
    ],

    'record_runs' => (bool) env('ARIA_RECORD_RUNS', true),

    'load_migrations' => (bool) env('ARIA_LOAD_MIGRATIONS', true),

    'history' => (int) env('ARIA_HISTORY', 20),

    'embeddings' => [
        'provider' => env('ARIA_EMBEDDINGS_PROVIDER', 'openai'),
        'model' => env('ARIA_EMBEDDINGS_MODEL', 'text-embedding-3-small'),
        'dimensions' => (int) env('ARIA_EMBEDDINGS_DIMENSIONS', 1536),
        'cache' => (bool) env('ARIA_EMBEDDINGS_CACHE', true),
    ],

    'retrieval' => [
        'limit' => (int) env('ARIA_RETRIEVAL_LIMIT', 6),
        'min_similarity' => (float) env('ARIA_RETRIEVAL_MIN_SIMILARITY', 0.3),
        'chunk_characters' => (int) env('ARIA_CHUNK_CHARACTERS', 1500),
    ],

    'limits' => [
        'max_steps' => (int) env('ARIA_MAX_STEPS', 6),
        'max_tokens' => (int) env('ARIA_MAX_TOKENS', 1500),
        'timeout' => (int) env('ARIA_TIMEOUT', 60),
    ],

    'tools' => [
        'knowledge_search' => [
            'description' => 'Search the knowledge base for facts before answering anything factual. Returns the most relevant entries with their URLs.',
            'empty' => 'No matching entries were found. Say you are not certain rather than guessing.',
        ],
    ],

    'masking' => [
        'enabled' => (bool) env('ARIA_MASKING', true),
        'patterns' => [
            'email' => '/[\w.+-]+@[\w-]+\.[\w.-]+/',
            'card' => '/\b(?:\d[ -]*?){13,19}\b/',
        ],
    ],

    'pricing' => [
        'gpt-4o-mini' => ['input' => 150_000, 'output' => 600_000],
        'gpt-4o' => ['input' => 2_500_000, 'output' => 10_000_000],
        'text-embedding-3-small' => ['input' => 20_000, 'output' => 0],
    ],

    'normaliser' => null,
];
