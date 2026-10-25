<?php
/**
 * Starter "Chronic conditions" articles for the Learn hub. includes/migrate.php
 * inserts each one once (matched by slug); after that they are ordinary
 * articles, edited in Admin → Articles.
 *
 * Written for Phola in plain language. They inform; they never tell anyone to
 * change or stop treatment their clinic prescribed.
 */
$careNote = '<p class="note"><b>Phola supplements support health — they do not treat, cure or prevent disease</b> and never replace the medication your clinic prescribes. Talk to your doctor or clinic before you change anything.</p>';

return [
    [
        'slug'  => 'living-well-with-type-2-diabetes',
        'title' => 'Living well with type 2 diabetes',
        'image' => 'assets/img/products/02.jpg',
        'mins'  => 6,
        'excerpt' => 'Type 2 diabetes is managed day by day. What the numbers mean, which habits make the biggest difference and when to see your clinic.',
        'html' => '<p>About one in nine South African adults lives with diabetes, and many more do not know they have it. Type 2 diabetes happens when the body stops using insulin well, so sugar builds up in the blood. Over years, high sugar damages the eyes, kidneys, nerves, heart and feet. The good news: steady daily habits keep most of that damage away.</p>'
            . '<h3>Know your numbers</h3><ul><li><b>Fasting sugar</b>: most clinics aim for 4–7 mmol/L.</li><li><b>HbA1c</b> (the three-month average): usually below 7%.</li><li><b>Blood pressure</b>: below 140/90, or lower if your clinic says so.</li></ul><p>Write your readings down. Phola customers can log them in the free tracker and show the chart at the next visit.</p>'
            . '<h3>The habits that matter most</h3><ol><li><b>Take your treatment every day</b>, even when you feel well. Diabetes is often silent.</li><li><b>Half your plate vegetables</b>, a quarter protein, a quarter starch. Choose brown bread, oats and samp-and-beans over white bread and large portions of soft pap.</li><li><b>Swap sugary drinks for water.</b> One 500 ml cool drink holds about 13 teaspoons of sugar.</li><li><b>Walk 30 minutes</b> on most days. Muscles use sugar without needing as much insulin.</li><li><b>Check your feet</b> every evening for cuts or blisters you cannot feel.</li></ol>'
            . '<h3>See your clinic straight away if</h3><p>your sugar stays above 15 mmol/L, you feel shaky, sweaty and confused (low sugar), a foot wound is not healing, or your vision suddenly changes.</p>' . $careNote,
    ],
    [
        'slug'  => 'high-blood-pressure-the-silent-condition',
        'title' => 'High blood pressure: the silent condition',
        'image' => 'assets/img/products/26.jpg',
        'mins'  => 5,
        'excerpt' => 'Almost half of South African adults have high blood pressure, and most feel nothing. How to measure it, bring it down and protect your heart.',
        'html' => '<p>High blood pressure (hypertension) rarely causes symptoms, yet it is a leading cause of stroke, heart attack and kidney failure. That is why it is called silent. The only way to know is to measure it.</p>'
            . '<h3>Reading the numbers</h3><p>A reading of <b>120/80</b> is healthy. <b>140/90 or higher</b> on repeated readings is high blood pressure. Measure seated, after five minutes of rest, with your arm supported at heart height, and take two readings a minute apart.</p>'
            . '<h3>What brings it down</h3><ul><li><b>Less salt.</b> Most of it hides in bread, stock cubes, soup powders, polony, viennas and chips. Aim for under one teaspoon a day in total.</li><li><b>More potassium</b> from vegetables, beans, bananas and avocado.</li><li><b>Move daily</b> — brisk walking counts.</li><li><b>Limit alcohol</b> and stop smoking.</li><li><b>Keep a healthy weight</b>: every kilogram lost lowers the reading.</li><li><b>Take your tablets every day.</b> Stopping when the numbers improve is the most common reason they climb again.</li></ul>'
            . '<h3>Emergency signs</h3><p>A sudden severe headache, chest pain, weakness on one side of the body, a drooping face or slurred speech need emergency care — call 10177 or go to the nearest hospital.</p>' . $careNote,
    ],
    [
        'slug'  => 'thriving-with-hiv-daily-wellness',
        'title' => 'Thriving with HIV: daily wellness beyond your ARVs',
        'image' => 'assets/img/products/01.jpg',
        'mins'  => 5,
        'excerpt' => 'With ARVs taken every day, people living with HIV live long, full lives. The everyday habits that support your immune system alongside treatment.',
        'html' => '<p>Antiretroviral treatment (ART) has changed everything. Taken every day, it lowers the virus until it is undetectable — and <b>undetectable means untransmittable</b>. The foundation is simple: never miss your ARVs.</p>'
            . '<h3>Habits that support your body</h3><ul><li><b>Same time, every day.</b> Link your dose to something you always do, and use reminders.</li><li><b>Eat for your immune system</b>: vegetables, fruit, beans, eggs, fish and whole grains. Food safety matters more — wash hands, cook meat through, drink safe water.</li><li><b>Stay active</b> to protect your bones, heart and mood.</li><li><b>Sleep</b> seven to eight hours.</li><li><b>Look after your mind.</b> Depression is common and treatable; speak to your clinic or a support group.</li><li><b>Go to every viral-load check.</b></li></ul>'
            . '<h3>About supplements</h3><p>Some people choose a daily supplement to help fill nutrient gaps. Always tell your clinic — certain herbs (for example St John\'s wort) and high-dose products can interfere with ARVs.</p>' . $careNote,
    ],
    [
        'slug'  => 'tb-recovery-finish-every-dose',
        'title' => 'TB recovery: why every single dose counts',
        'image' => 'assets/img/products/06.jpg',
        'mins'  => 4,
        'excerpt' => 'TB is curable — if the full course is finished. How to get through six months of treatment and rebuild your strength.',
        'html' => '<p>Tuberculosis is curable. Treatment usually lasts six months, and most people feel better within weeks. That is exactly when the danger starts: stopping early lets the strongest bacteria survive and come back as drug-resistant TB, which is much harder to treat.</p>'
            . '<h3>Getting through treatment</h3><ul><li>Take your tablets <b>every day</b>, at the same time, for the full course.</li><li>Keep your clinic appointments and sputum tests.</li><li>Cover your cough and keep rooms well ventilated during the first weeks.</li><li>Report yellow eyes, a rash, or tingling hands and feet to your clinic straight away.</li></ul>'
            . '<h3>Rebuilding your strength</h3><p>TB burns energy and muscle. Eat three meals and two snacks a day with protein at each: eggs, beans, peanut butter, milk, fish or chicken. Avoid alcohol completely — it strains the liver, which is already working hard on the medicine. Gentle walks rebuild lung capacity as you recover.</p>' . $careNote,
    ],
    [
        'slug'  => 'eating-well-during-cancer-treatment',
        'title' => 'Eating well during cancer treatment',
        'image' => 'assets/img/products/04.jpg',
        'mins'  => 5,
        'excerpt' => 'Chemotherapy and radiation can change appetite and taste. Practical ways to keep your strength up — and why to check every supplement with your oncologist.',
        'html' => '<p>Good nutrition helps people cope with cancer treatment, heal and keep their strength. But treatment often brings nausea, mouth sores, taste changes and tiredness, so ordinary meals can feel impossible.</p>'
            . '<h3>Practical tips</h3><ul><li><b>Small meals often</b> — six small meals are easier than three large ones.</li><li><b>Protein first</b>: eggs, yoghurt, maas, beans, fish, chicken and peanut butter help repair tissue.</li><li><b>For nausea</b>, try dry crackers, ginger tea and cold foods with little smell.</li><li><b>For a sore mouth</b>, choose soft foods like porridge, mashed vegetables and smoothies; avoid chilli and citrus.</li><li><b>Drink</b> eight glasses of fluid a day unless told otherwise.</li></ul>'
            . '<h3>Supplements need your oncologist\'s approval</h3><p>Some vitamins, antioxidants and herbs can change how chemotherapy or radiation works. Show your oncology team every product before you take it — including anything from Phola.</p>' . $careNote,
    ],
    [
        'slug'  => 'asthma-and-copd-breathing-easier',
        'title' => 'Asthma and COPD: breathing easier every day',
        'image' => 'assets/img/products/23.jpg',
        'mins'  => 4,
        'excerpt' => 'Know your triggers, use your inhaler properly and keep an action plan. Everyday steps for healthier lungs.',
        'html' => '<p>Asthma narrows the airways in attacks; COPD (often from smoking or years of smoke and dust) slowly damages the lungs. Both are managed, not cured — and both improve with the right daily routine.</p>'
            . '<h3>Everyday steps</h3><ul><li><b>Use your preventer inhaler every day</b> if one is prescribed, even when you feel fine.</li><li><b>Check your technique</b>: a spacer helps the medicine reach the lungs. Ask your pharmacist to watch you use it.</li><li><b>Avoid triggers</b>: cigarette and fire smoke, paraffin fumes, dust, strong perfumes and cold air (cover your mouth and nose).</li><li><b>Stop smoking</b> — the single biggest thing you can do for COPD.</li><li><b>Get your flu vaccine</b> every autumn.</li></ul>'
            . '<h3>Get help fast if</h3><p>your reliever inhaler is not helping, you cannot finish a sentence, or your lips turn blue. Call 10177 or go to the nearest emergency unit.</p>' . $careNote,
    ],
    [
        'slug'  => 'joint-pain-and-arthritis-staying-mobile',
        'title' => 'Joint pain and arthritis: staying mobile',
        'image' => 'assets/img/products/19.jpg',
        'mins'  => 4,
        'excerpt' => 'Movement is medicine for stiff joints. How to stay active with arthritis, eat to calm inflammation and protect your joints.',
        'html' => '<p>Osteoarthritis (wear-and-tear) and rheumatoid arthritis (an immune condition) cause pain and stiffness, but resting too much makes joints stiffer. Gentle, regular movement keeps them working.</p>'
            . '<h3>What helps</h3><ul><li><b>Low-impact exercise</b>: walking, swimming, cycling and chair exercises, a little every day.</li><li><b>Strength work</b> for the muscles around the knee and hip takes load off the joint.</li><li><b>Healthy weight</b>: every kilogram lost takes about four kilograms of pressure off each knee.</li><li><b>Warmth</b> eases stiffness in the morning; cold packs calm a hot, swollen joint.</li><li><b>Anti-inflammatory foods</b>: oily fish such as pilchards and sardines, vegetables, beans and olive or canola oil.</li></ul>'
            . '<p>Swelling with fever, or a joint that suddenly becomes red and very painful, needs a doctor the same day.</p>' . $careNote,
    ],
    [
        'slug'  => 'protecting-your-kidneys',
        'title' => 'Protecting your kidneys with diabetes or high blood pressure',
        'image' => 'assets/img/products/28.jpg',
        'mins'  => 4,
        'excerpt' => 'Diabetes and high blood pressure are the main causes of kidney disease. Simple checks and habits that protect your kidneys for life.',
        'html' => '<p>Your kidneys filter about 180 litres of blood a day. Diabetes and high blood pressure slowly damage those filters, often without any symptoms until a lot of function is lost.</p>'
            . '<h3>Ask for these checks every year</h3><ul><li>A <b>urine test</b> for protein (albumin).</li><li>A <b>blood test</b> for kidney function (eGFR).</li></ul>'
            . '<h3>Habits that protect them</h3><ul><li>Keep sugar and blood pressure in target.</li><li>Cut salt and processed meat.</li><li>Drink water rather than cool drinks.</li><li><b>Be careful with painkillers</b> such as ibuprofen and diclofenac — regular use harms the kidneys. Ask your pharmacist for safer options.</li><li>Check with your clinic before taking any herbal remedy or supplement; some are hard on the kidneys.</li></ul>' . $careNote,
    ],
];
