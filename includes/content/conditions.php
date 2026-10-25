<?php
/**
 * Condition hub pages (condition.php?c=key). Each one pulls together the guide,
 * the diet plan, a calculator, matching products and the latest news on that
 * condition, so the page is worth finding on Google and worth sharing.
 *   article  = blog slug, diet = key in diet-plans.php, category = shop category slug,
 *   tool     = anchor on health-tools.php, news = words to match in news headlines
 */
return [
    'diabetes' => [
        'name' => 'Diabetes', 'title' => 'Living with diabetes in South Africa', 'icon' => 'fa-droplet',
        'intro' => 'Type 2 diabetes affects millions of South Africans and many more people across SADC — and many don\'t know they have it. With the right daily routine, most people with diabetes live long, active lives.',
        'facts' => ['Fasting sugar target: 4–7 mmol/L', 'HbA1c target: usually below 7%', 'Check your feet every evening', 'Walk 30 minutes on most days'],
        'article' => 'living-well-with-type-2-diabetes', 'extra' => ['protecting-your-kidneys'], 'diet' => 'diabetes', 'category' => 'blood-sugar-support',
        'tool' => 'sugar', 'news' => ['diabet', 'insulin', 'blood sugar', 'glucose', 'obesity'],
    ],
    'high-blood-pressure' => [
        'name' => 'High blood pressure', 'title' => 'Managing high blood pressure', 'icon' => 'fa-heart-pulse',
        'intro' => 'Almost half of South African adults have high blood pressure, and most feel completely fine. It is the leading cause of strokes — and one of the easiest conditions to check at home.',
        'facts' => ['Healthy: below 120/80', 'High: 140/90 or more', 'Under one teaspoon of salt a day', 'Take tablets every day, even when well'],
        'article' => 'high-blood-pressure-the-silent-condition', 'extra' => ['protecting-your-kidneys'], 'diet' => 'hypertension', 'category' => 'heart-circulation',
        'tool' => 'bp', 'news' => ['blood pressure', 'hypertension', 'stroke', 'heart', 'cardio'],
    ],
    'hiv' => [
        'name' => 'HIV', 'title' => 'Thriving with HIV', 'icon' => 'fa-shield-heart',
        'intro' => 'With daily ARVs, people living with HIV live full, healthy lives. Undetectable means untransmittable. Good food, sleep and support make every day better.',
        'facts' => ['Take ARVs at the same time daily', 'Go to every viral-load check', 'Tell your clinic about any supplement', 'U = U: undetectable = untransmittable'],
        'article' => 'thriving-with-hiv-daily-wellness', 'extra' => [], 'diet' => 'hiv', 'category' => 'immune-support',
        'tool' => 'pack', 'news' => ['hiv', 'aids', 'antiretroviral', 'arv', 'prep'],
    ],
    'tb' => [
        'name' => 'TB', 'title' => 'TB recovery', 'icon' => 'fa-lungs',
        'intro' => 'TB is curable when every dose of the six-month course is taken. Eating well and avoiding alcohol helps your body recover its strength.',
        'facts' => ['Finish all six months', 'No alcohol during treatment', 'Ventilate rooms in the first weeks', 'Report yellow eyes or a rash'],
        'article' => 'tb-recovery-finish-every-dose', 'extra' => ['asthma-and-copd-breathing-easier'], 'diet' => 'tb', 'category' => 'lungs-breathing',
        'tool' => 'pack', 'news' => ['tuberculosis', ' tb ', 'lung'],
    ],
    'cancer' => [
        'name' => 'Cancer care', 'title' => 'Wellness during cancer treatment', 'icon' => 'fa-hand-holding-medical',
        'intro' => 'Good nutrition helps people cope with cancer treatment and recover. Small meals, extra protein — and every supplement checked with your oncology team first.',
        'facts' => ['Six small meals beat three big ones', 'Protein at every meal', 'Ginger and dry crackers for nausea', 'Check every supplement with your oncologist'],
        'article' => 'eating-well-during-cancer-treatment', 'extra' => [], 'diet' => 'cancer', 'category' => 'supportive-care',
        'tool' => 'bmi', 'news' => ['cancer', 'tumour', 'tumor', 'chemo', 'oncolog'],
    ],
    'arthritis' => [
        'name' => 'Arthritis & joints', 'title' => 'Living with arthritis and joint pain', 'icon' => 'fa-person-walking',
        'intro' => 'Gentle daily movement, a healthy weight and anti-inflammatory food keep stiff joints working.',
        'facts' => ['Move a little every day', 'Every kg lost eases the knees', 'Oily fish twice a week', 'Warmth for morning stiffness'],
        'article' => 'joint-pain-and-arthritis-staying-mobile', 'extra' => [], 'diet' => 'arthritis', 'category' => 'skin-body',
        'tool' => 'bmi', 'news' => ['arthritis', 'joint', 'gout', 'bone'],
    ],
];
