<?php
/**
 * Exercise plans by age group, shown on exercise-plans.php. Targets follow the
 * WHO physical-activity guidelines; the moves need no gym and no equipment
 * (a chair, a wall, two 2-litre water bottles), so anyone can start at home.
 *
 *   age groups:  key => name, ages, icon, colour, goal, targets, week (day => [title, detail]), moves, tip
 *   conditions:  key => name, icon, do, avoid   (adjustments for people with a chronic condition)
 */
return [
    'groups' => [
        'kids' => [
            'name' => 'Children & teens', 'ages' => '5–17 years', 'icon' => 'fa-child-reaching', 'colour' => '#E8681F',
            'goal' => 'At least 60 minutes of active play every day, mostly running-about activity, with harder play that builds muscles and bones three days a week.',
            'targets' => ['60 min a day, every day', 'Huffing-and-puffing play on 3 days', 'Jumping, climbing or ball games for bones', 'Under 2 hours of screens for fun'],
            'week' => [
                'Mon' => ['Skipping & tag', '20 min skipping rope or tag, then 40 min walking to school / playing outside'],
                'Tue' => ['Ball games', 'Soccer, netball or catch — 60 min in total through the day'],
                'Wed' => ['Dance party', '30 min dancing to music + 30 min active play'],
                'Thu' => ['Strong body day', 'Climbing, hopscotch, push-ups against a wall, frog jumps — make it a game'],
                'Fri' => ['Sports or swimming', 'School sport, swimming or a long walk with family'],
                'Sat' => ['Family outing', 'Walk to the park, cycle, or play diketo and skipping games'],
                'Sun' => ['Easy active day', 'Gentle walk, stretching, helping in the garden'],
            ],
            'moves' => [['Frog jumps', '10 jumps, rest, repeat 3 times'], ['Wall push-ups', '10 slow push-ups against a wall'], ['Skipping', '1 minute on, 1 minute rest, 5 rounds'], ['Bear crawl', 'Crawl on hands and feet across the room and back']],
            'tip' => 'Children copy adults — the best way to get them moving is to move with them.',
        ],
        'young' => [
            'name' => 'Young adults', 'ages' => '18–39 years', 'icon' => 'fa-person-running', 'colour' => '#2E8B3E',
            'goal' => '150–300 minutes of moderate activity a week (or 75–150 minutes of vigorous activity), plus strength work on two or more days.',
            'targets' => ['30–45 min, 5 days a week', 'Strength training 2–3 days', 'Break up long sitting every hour', 'One rest or stretch day'],
            'week' => [
                'Mon' => ['Brisk walk or jog', '30–40 min. You can talk, but not sing'],
                'Tue' => ['Strength circuit', 'Squats, lunges, push-ups, plank — 3 rounds (see moves below)'],
                'Wed' => ['Cardio you enjoy', '30 min dancing, cycling, soccer or a fitness video'],
                'Thu' => ['Strength circuit', 'Repeat Tuesday, add 2 reps to each move'],
                'Fri' => ['Intervals', '20 min: 1 min fast walk / jog, 1 min easy, repeat'],
                'Sat' => ['Long & social', '45–60 min walk, hike, park run or a game with friends'],
                'Sun' => ['Rest & stretch', '10–15 min gentle stretching'],
            ],
            'moves' => [['Squats', '12–15 reps, 3 rounds'], ['Lunges', '10 each leg, 3 rounds'], ['Push-ups', '8–12 (knees down is fine), 3 rounds'], ['Plank', 'Hold 20–40 seconds, 3 times'], ['Water-bottle rows', '12 each arm with a full 2-litre bottle']],
            'tip' => 'Book exercise into your day like a meeting. Morning sessions are the hardest to cancel.',
        ],
        'adults' => [
            'name' => 'Adults', 'ages' => '40–64 years', 'icon' => 'fa-person-walking', 'colour' => '#2F5F8A',
            'goal' => '150–300 minutes of moderate activity a week and strength work on two days — the age when it does the most to prevent diabetes, high blood pressure and heart disease.',
            'targets' => ['30 min brisk walking, 5 days', 'Strength 2 days a week', 'Stretch daily — hips, back, calves', 'Check BP & sugar before starting'],
            'week' => [
                'Mon' => ['Brisk walk', '30 min — split into 3 × 10 min if easier'],
                'Tue' => ['Strength at home', 'Chair squats, wall push-ups, bottle curls, bridges — 2–3 rounds'],
                'Wed' => ['Brisk walk', '30 min, try a slightly hillier route'],
                'Thu' => ['Low-impact cardio', '30 min cycling, swimming, dancing or an aerobics class'],
                'Fri' => ['Strength at home', 'Repeat Tuesday'],
                'Sat' => ['Long walk', '45 min with family or a walking group'],
                'Sun' => ['Rest & stretch', 'Gentle stretching, deep breathing'],
            ],
            'moves' => [['Chair squats', 'Sit down and stand up without using your hands — 10–12, 3 rounds'], ['Wall push-ups', '12–15, 3 rounds'], ['Bottle bicep curls', '12 each arm with a full 2-litre bottle'], ['Glute bridges', 'Lie on your back, lift hips — 12, 3 rounds'], ['Calf raises', 'Hold a chair, rise onto your toes — 15']],
            'tip' => 'A brisk 10-minute walk after supper lowers blood sugar more than the same walk at any other time.',
        ],
        'seniors' => [
            'name' => 'Older adults', 'ages' => '65 + years', 'icon' => 'fa-person-cane', 'colour' => '#7A4FB5',
            'goal' => 'Stay as active as your health allows: about 150 minutes of gentle-to-moderate activity a week, strength on two days, and balance exercises on three days to prevent falls.',
            'targets' => ['20–30 min gentle activity most days', 'Balance exercises 3 days', 'Strength 2 days (seated is fine)', 'Always have a chair or wall nearby'],
            'week' => [
                'Mon' => ['Gentle walk', '15–20 min at a comfortable pace, indoors or outside'],
                'Tue' => ['Seated strength', 'Chair stands, seated leg lifts, bottle curls — 2 rounds'],
                'Wed' => ['Balance practice', 'Heel-to-toe walk, standing on one leg (holding a chair) — 10 min'],
                'Thu' => ['Gentle walk', '20 min, or walk around the house every hour'],
                'Fri' => ['Seated strength + balance', 'Repeat Tuesday, then 5 min balance'],
                'Sat' => ['Social activity', 'Church walk, gardening, a dance or exercise group'],
                'Sun' => ['Rest & stretch', 'Gentle seated stretches and deep breathing'],
            ],
            'moves' => [['Chair stands', 'Stand up and sit down slowly — 8–10'], ['Seated leg lifts', 'Straighten one knee, hold 3 seconds — 10 each leg'], ['Heel-to-toe walk', '10 steps along a wall'], ['One-leg stand', 'Hold a chair, stand on one leg 10 seconds, switch'], ['Seated marching', 'March in your chair for 1–2 minutes']],
            'tip' => 'Wear closed shoes with a good grip, and keep a phone with you on walks.',
        ],
    ],
    'conditions' => [
        'diabetes' => ['name' => 'Diabetes', 'icon' => 'fa-droplet',
            'do' => 'Walk after meals — it lowers sugar. Check your sugar before exercise if you use insulin or sulphonylureas, and carry sweets in case it drops.',
            'avoid' => 'Avoid exercise if your sugar is above 15 mmol/L with ketones, or below 4. Check your feet after every session.'],
        'hypertension' => ['name' => 'High blood pressure', 'icon' => 'fa-heart-pulse',
            'do' => 'Regular brisk walking, cycling and swimming lower blood pressure. Breathe out as you lift.',
            'avoid' => "Don't hold your breath when lifting, and avoid very heavy weights. Skip exercise if your reading is above 180/110 — see your clinic."],
        'hiv' => ['name' => 'HIV', 'icon' => 'fa-shield-heart',
            'do' => 'Exercise protects bones, heart and mood, and helps with ARV side effects such as tiredness. Mix walking with strength work.',
            'avoid' => 'Rest on days with a fever or infection, and drink plenty of water.'],
        'tb' => ['name' => 'TB recovery', 'icon' => 'fa-lungs',
            'do' => 'Start with short walks once your clinic says it is safe, and build up slowly to rebuild lung capacity and muscle.',
            'avoid' => 'No hard exercise in the first weeks of treatment or while you have a fever or cough up blood.'],
        'arthritis' => ['name' => 'Arthritis & joint pain', 'icon' => 'fa-person-walking',
            'do' => 'Low-impact moves — walking, swimming, cycling, chair exercises. Warm up for 5–10 minutes; warmth eases stiffness.',
            'avoid' => 'Rest a joint that is hot and swollen. Mild discomfort is normal; sharp pain means stop.'],
        'heart' => ['name' => 'Heart conditions', 'icon' => 'fa-heart',
            'do' => 'Ask your doctor for a safe level first. Gentle walking that builds up week by week is usually the start.',
            'avoid' => 'Stop at once and get help if you have chest pain, severe breathlessness or dizziness.'],
        'pregnancy' => ['name' => 'Pregnancy', 'icon' => 'fa-person-pregnant',
            'do' => 'Most pregnant women can aim for 150 minutes of moderate activity a week — walking, swimming, pregnancy yoga.',
            'avoid' => 'Avoid contact sports, lying flat on your back after the first trimester, and anything with a risk of falling. Check with your clinic.'],
    ],
];
