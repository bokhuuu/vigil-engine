<?php

return [

    'shared_views' => [
        'teams' => [
            'ამზომველები' => 'https://airtable.com/appuF9IUa53xy31el/shrh8vbbOI9U9RDEj',
            'ხარჯთაღმრიცხველი' => 'https://airtable.com/appuF9IUa53xy31el/shrfQYJ3cZAT9OZNr',
            'აგრონომები' => 'https://airtable.com/appuF9IUa53xy31el/shrrFn4ExraA59BkB',
            'ჩადუ & გივიკო' => 'https://airtable.com/appuF9IUa53xy31el/shrKFxy3T0bWRx3IS',
            'გიო & სანდრიკა' => 'https://airtable.com/appuF9IUa53xy31el/shrTQCW2mmzZ4YMIY',
            'მურო & რევაზა' => 'https://airtable.com/appuF9IUa53xy31el/shr9RQqhU0MqS6tTk',
        ],
        'sectors' => 'https://airtable.com/appuF9IUa53xy31el/shrATg4H8DSyzRmCq',

        'inspector_full' => [
            'ჩადუ & გივიკო' => 'https://airtable.com/appuF9IUa53xy31el/shrJifbw7c0fx2Oxi',
            'გიო & სანდრიკა' => 'https://airtable.com/appuF9IUa53xy31el/shrp8pg5J3jiyWzUV',
            'მურო & რევაზა' => 'https://airtable.com/appuF9IUa53xy31el/shrcg6wDQNeTBvF4R',
        ],
        'inspector_sectors' => [
            'ჩადუ & გივიკო' => 'https://airtable.com/appuF9IUa53xy31el/shr2i4AZNvkrUd7ap',
            'გიო & სანდრიკა' => 'https://airtable.com/appuF9IUa53xy31el/shrq2yGsByaQodlpV',
            'მურო & რევაზა' => 'https://airtable.com/appuF9IUa53xy31el/shrQxuDZq6omDh7d2',
        ],
        'updates' => [
            'ჩადუ & გივიკო' => 'https://airtable.com/appuF9IUa53xy31el/shrvDSwwdEJ77Fwgm',
            'გიო & სანდრიკა' => 'https://airtable.com/appuF9IUa53xy31el/shr6g4niD4F6Fz2D1',
            'მურო & რევაზა' => 'https://airtable.com/appuF9IUa53xy31el/shrxNX86BNNCvwz6q',
            'კალმახელიძე' => 'https://airtable.com/appuF9IUa53xy31el/shr1YKe651P4fVf9A',
        ],
        'managers' => [
            'acts_incomplete' => 'https://airtable.com/appuF9IUa53xy31el/shrbvQh8eU5u0yNEp',
            'acts_complete' => 'https://airtable.com/appuF9IUa53xy31el/shrXqsOLH8RhpVm6H',
            'corr_incomplete' => 'https://airtable.com/appuF9IUa53xy31el/shrjg95XhKYnLKswV',
            'corr_overdue' => 'https://airtable.com/appuF9IUa53xy31el/shrQiFtI3fp3trU3z',
            'corr_answered' => 'https://airtable.com/appuF9IUa53xy31el/shry43IQTUyxqqyr7',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Airtable
    |--------------------------------------------------------------------------
    */
    'airtable' => [
        'token' => env('AIRTABLE_TOKEN'),
        'base_id' => env('AIRTABLE_BASE_ID', 'appuF9IUa53xy31el'),
        'base_url' => 'https://api.airtable.com/v0',

        'tables' => [
            'acts' => 'tblqVYQxgMXwgyY6r',
            'deadlines' => 'tbldJL7NHV91IHqQP',
            'sectors' => 'tblFMU2Zj3VCRdL2M',
            'correspondence' => 'tblM1b0aw2lbvTC2A',
            'teams' => 'tbly6CXuhsuITExqg',
            'updates' => 'tbly4VL3pjKn8a0bn',
            'businesses' => 'tbl4Aarov1w9wqgXC',
        ],

        // Field IDs - use these everywhere, never field names
        'fields' => [

            'acts' => [
                'number' => 'fldoiDMLunk2ra9mr',
                'date_received' => 'fldpcU96VHa44b59G',
                'sector' => 'fldJMyWjLfkASbojn',
                'team' => 'fldfP6uhlyAhWV7S3',
                'owner_contact' => 'fldccg89Gw9x9Isut',
                // Completion checkboxes
                'photos' => 'fldu9t0onXDuenc95',
                'signatures' => 'fldjD8AUmU9kYsr78',
                'land' => 'fldNxuCevwk2CWqBY',
                'split_land' => 'fldbvG6dJfRml0Kk0',
                'building' => 'fldVMgLQuTvKqrTaZ',
                'fence' => 'fldu3xZ1U8DZSJz3U',
                'gate' => 'fldVvIWJSABft3rCM',
                'movable' => 'fld0LIPK22B9eYrNf',
                'perennial' => 'fld60NiIhfcxeiKf0',
                'decorative' => 'fld1wbingVBMUaitP',
                'annual' => 'fldmGbmaypy8IpsdU',
                'business' => 'fld6vhkerEPc8qsvY',
                'soceco' => 'fldvsEwhxS1ZQkOA2',
                // Technical docs
                'doc_cost' => 'fldgsS8iWmw4ksaEd',
                'doc_cost_small' => 'fldsidahHfa8qG8QZ',
                'doc_boundary' => 'fldnaKdrr0fr94cL2',
                'doc_drawings' => 'fldxY6hAVxTyCbM2v',
                'doc_ortho' => 'fldZvDdM2CeP0oTWJ',
                'doc_topo' => 'fldr4wXpk8fNTDpcg',
                // Status
                'missing' => 'fldQ6YTrnm4YTeflh',
                'responsible' => 'fldZvfViuKZpUsHAG',
                'status' => 'fldPaJU62uTXnystm',
                'notified_at' => 'fld3DRn8LMpc21S9f',
                'notes' => 'fld7pjHQgRbBXOoEv',
                'folder' => 'fld1jy2Zp22rO8Fde',
            ],

            'deadlines' => [
                'title' => 'fldnjrR7iScGTPi8C',
                'date' => 'flduJXUewzemlB9R4',
                'status' => 'flda2zMml6UU6VvCE',
                'act' => 'fldPdQMFxWf9iJgfM',
                'team' => 'fldbwybxmNBB1ocbS',
                'notes' => 'fldmh21ciQFRNnyh0',
            ],

            'sectors' => [
                'name' => 'fldgFPrINTkJbXjgO',
                'sector' => 'fldpxgwM9XtZxMlXT',
                'team' => 'fldLMrOmu98zOUrqV',
                'total_acts' => 'fldlYFCxy2ltqsM2e',
                'celebrated' => 'fldaJj0vHAWHBcoNH',
                'notes' => 'fldwqOjN1XW1kSkpb',
            ],

            'correspondence' => [
                'subject' => 'fldpLrKzlnfYD9J8j',
                'date_received' => 'fldDuNz2fHZ7shvyV',
                'deadline' => 'fldFSuqBFvSfGb6Ez',
                'promised_date' => 'fldgQFLLup2VOfZz4',
                'status' => 'fld8hXaOCAk0nNv0k',
                'date_replied' => 'fldjYQ15CuOP1xtDs',
                'direction' => 'fldAuftCT6Kqn72Qu',
                'road_contact' => 'fldOIY7e4d3UiyokP',
                'lot' => 'fldSb0NgQj6kHDwMV',
                'notes' => 'fldRRDHtm46cYixto',
                'folder' => 'fldVpKpcYcl1b6qoK',
            ],

            'teams' => [
                'name' => 'fldNRNOmCHFg1xGLc',
                'role' => 'fldjwepc2YmGeDOcN',
                'sectors' => 'fld6XEpAWGVRboYCY',
                'phone' => 'fld99dCBbKDWDhvjt',
                'telegram' => 'fldHoEvrTmGAWkefs',
                'email' => 'fldu0xZjo3kUtb9Kr',
                'notes' => 'fldMNLooMQKu6oVzk',
            ],

            'businesses' => [
                'name' => 'fldyoClonRw5Hnb6C',
                'id_code' => 'fldxJBWeOw67afHrE',
                'type' => 'fld8MZNf4RNiAKA0h',
                'start_year' => 'fldyTrUfmtxoTrhV5',
                'rent' => 'fldsg4LUCqgkx8aLV',
                'staff' => 'fldp2PNUtvJIqK0Si',
                'documents' => 'fldAbgu5PewlINAuk',
                'valuation' => 'fldmThrV0QZLg3Dbd',
                'status' => 'fld7pUHyrpRqArov6',
                'act' => 'fldE1dvs6gvXMXfsK',
                'notes' => 'fldCKpiFYWJUKl0Gl',
                'folder' => 'fldR39QaVJwsLhffc',
            ],

            'updates' => [
                'team' => 'flds3k5Wp3ZWVLUdn',
                'date' => 'fldIwotK3pAtot1aG',
            ],
        ],

        // Exact option strings as they appear in Airtable
        'values' => [
            'status_active' => '⏳ მიმდინარე',
            'status_done' => '✅ დასრულებული',
            'corr_overdue' => '🔴 ვადაგადაცილებული',
            'corr_replied' => '📤 პასუხი გაგზავნილი',
            'soceco_done' => '✅ შესრულებული',
            'soceco_pending' => '⏳ მომლოდინე',
            'deadline_pending' => '⏳ მომლოდინე',
            'deadline_overdue' => '🔴 გადაცილებული',
            'deadline_done' => '✅ შესრულებული',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'my_chat_id' => env('TELEGRAM_MY_CHAT_ID'),
        'group_chat_id' => env('TELEGRAM_GROUP_CHAT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'secret' => env('WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        'morning_hour' => 10,  // Scripts run at 10:00
        'backup_day' => 'sunday',
        'backup_hour' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    */
    'overdue_days' => 3,  // ACT flagged overdue after 3 days incomplete

];
