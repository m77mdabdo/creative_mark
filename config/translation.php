<?php

/*
 * Machine translation (D-05, LR-06). Every machine result is stored with review_status "machine"
 * and shown with the "under review" notice until a Content Manager approves it.
 * Only CMS content is ever sent to a translator, never lead or personal data (T-17).
 */
return [

    // Tried in order; the next one is used when a driver is down or over its quota.
    'drivers' => array_filter(explode(',', env('TRANSLATION_DRIVERS', 'libretranslate,mymemory'))),

    // Fill empty languages automatically after an editor saves a record in the dashboard.
    'auto_on_save' => (bool) env('TRANSLATION_AUTO_ON_SAVE', true),

    // Preferred source language when several exist (English is the content master).
    'source_priority' => ['en', 'ar'],

    'libretranslate' => [
        // Self-hosted: infra/translation/docker-compose.yml
        'url' => env('LIBRETRANSLATE_URL', 'http://127.0.0.1:5050'),
        'api_key' => env('LIBRETRANSLATE_API_KEY'),
        'timeout' => 120,
        'codes' => ['zh' => 'zh-Hans'],
    ],

    'mymemory' => [
        // Free public API (5,000 characters/day anonymous, 50,000 with a contact email).
        'url' => 'https://api.mymemory.translated.net/get',
        'email' => env('MYMEMORY_EMAIL'),
        'timeout' => 30,
        'codes' => ['zh' => 'zh-CN', 'ur' => 'ur-PK', 'hi' => 'hi-IN', 'ar' => 'ar-SA', 'fr' => 'fr-FR', 'en' => 'en-GB'],
    ],

    /*
     * Terms that must never be translated. The value is how each locale writes it; a missing locale keeps
     * the original. Kept out of the translator's reach and restored afterwards.
     */
    'glossary' => [
        'Creative Mark' => ['ar' => 'كرييتف مارك', 'ur' => 'کریئیٹو مارک'],
        'كرييتف مارك' => ['en' => 'Creative Mark', 'fr' => 'Creative Mark', 'hi' => 'Creative Mark', 'zh' => 'Creative Mark', 'ur' => 'کریئیٹو مارک'],
        'Vision 2030' => ['ar' => 'رؤية 2030', 'ur' => 'وژن 2030'],
        'KAFD' => [],
        'NEOM' => ['ar' => 'نيوم', 'ur' => 'نیوم'],
        'AlUla' => ['ar' => 'العلا', 'ur' => 'العُلا'],
        'B2B' => [],
    ],

    /*
     * Preferred wording for recurring site terms (menu items, buttons). An exact match is used instead of the
     * machine, because engines translate short labels literally ("Our edge" → "Notre bord"). Still marked
     * "machine" until a translator approves the language.
     */
    'phrasebook' => [
        'About' => ['fr' => 'À propos', 'hi' => 'हमारे बारे में', 'ur' => 'ہمارے بارے میں', 'zh' => '关于我们'],
        'Business solutions' => ['fr' => 'Solutions d’affaires', 'hi' => 'व्यावसायिक समाधान', 'ur' => 'کاروباری حل', 'zh' => '商业解决方案'],
        'Our edge' => ['fr' => 'Notre avantage', 'hi' => 'हमारी विशेषता', 'ur' => 'ہماری انفرادیت', 'zh' => '我们的优势'],
        'Regions' => ['fr' => 'Régions', 'hi' => 'क्षेत्र', 'ur' => 'علاقے', 'zh' => '地区'],
        'Investor guide' => ['fr' => 'Guide de l’investisseur', 'hi' => 'निवेशक गाइड', 'ur' => 'سرمایہ کار گائیڈ', 'zh' => '投资者指南'],
        'News' => ['fr' => 'Actualités', 'hi' => 'समाचार', 'ur' => 'خبریں', 'zh' => '新闻'],
        'Case studies' => ['fr' => 'Études de cas', 'hi' => 'केस स्टडी', 'ur' => 'کیس اسٹڈیز', 'zh' => '案例研究'],
        'Get consultation' => ['fr' => 'Demander une consultation', 'hi' => 'परामर्श लें', 'ur' => 'مشاورت حاصل کریں', 'zh' => '预约咨询'],
        'Start your company' => ['fr' => 'Créez votre entreprise', 'hi' => 'अपनी कंपनी शुरू करें', 'ur' => 'اپنی کمپنی شروع کریں', 'zh' => '创办您的公司'],
        'Talk to a consultant' => ['fr' => 'Parler à un consultant', 'hi' => 'सलाहकार से बात करें', 'ur' => 'مشیر سے بات کریں', 'zh' => '咨询顾问'],
        'Makkah Region' => ['fr' => 'Région de La Mecque', 'hi' => 'मक्का क्षेत्र', 'ur' => 'مکہ ریجن', 'zh' => '麦加地区'],
        'Historic Jeddah' => ['fr' => 'Djeddah historique', 'hi' => 'ऐतिहासिक जेद्दा', 'ur' => 'تاریخی جدہ', 'zh' => '吉达历史区'],
        'Investment opportunities' => ['fr' => 'Opportunités d’investissement', 'hi' => 'निवेश के अवसर', 'ur' => 'سرمایہ کاری کے مواقع', 'zh' => '投资机会'],
    ],

    // Folder of interface label files (lang/ui/{locale}.json); tests point this at a temporary copy.
    'ui_strings_path' => env('TRANSLATION_UI_STRINGS_PATH'),

    // Cache identical requests for a year (keyed by driver-independent text hash).
    'cache_ttl' => 60 * 60 * 24 * 365,
];
