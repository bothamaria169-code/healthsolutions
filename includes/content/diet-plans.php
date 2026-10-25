<?php
/**
 * Diet plans shown on diet-plans.php, one per chronic condition. Built around
 * everyday South African food so the plan is affordable and realistic.
 * Each plan: icon, colour, aim, eat-more list, limit list, a sample day and a tip.
 * The product slugs link to related Phola products (only published ones show).
 */
return [
    'diabetes' => [
        'name' => 'Type 2 diabetes', 'icon' => 'fa-droplet', 'colour' => '#2E8B3E',
        'aim'  => 'Keep blood sugar steady through the day: regular meals, fibre with every meal, small portions of starch.',
        'more' => ['Non-starchy vegetables: spinach, morogo, cabbage, broccoli, green beans', 'Beans, lentils and samp-and-beans', 'Oats, brown rice, wholewheat bread', 'Eggs, fish, skinless chicken, peanut butter', 'Water, unsweetened rooibos'],
        'less' => ['Cool drinks, juice and sugar in tea', 'White bread, large servings of soft pap', 'Cakes, biscuits, sweets', 'Fried takeaways and vetkoek'],
        'day'  => [
            'Breakfast' => 'Oats made with water or low-fat milk, a sprinkle of cinnamon, a boiled egg',
            'Morning snack' => 'An apple or a handful of peanuts',
            'Lunch'     => 'Two slices of brown bread with pilchards and tomato, side salad',
            'Afternoon snack' => 'Plain yoghurt or maas',
            'Supper'    => 'Fist-size stiff pap, grilled chicken, half a plate of morogo and carrots',
        ],
        'tip'  => 'Stiff, cooled pap raises sugar more slowly than soft pap eaten hot.',
        'products' => ['glucobalance-blood-sugar-support', 'nativa-diabetic-multivitamin', 'glucobalance-pen'],
    ],
    'hypertension' => [
        'name' => 'High blood pressure', 'icon' => 'fa-heart-pulse', 'colour' => '#C93E28',
        'aim'  => 'Cut salt to under one teaspoon a day and eat more potassium-rich vegetables and fruit (the DASH way of eating).',
        'more' => ['Vegetables and fruit at every meal: bananas, oranges, spinach, butternut', 'Beans, lentils and unsalted nuts', 'Low-fat milk, maas and yoghurt', 'Fish twice a week', 'Herbs, garlic, lemon and spices instead of salt'],
        'less' => ['Salt, stock cubes, soup powders and aromat', 'Polony, viennas, russians and other processed meat', 'Chips, salty snacks, instant noodles', 'Alcohol'],
        'day'  => [
            'Breakfast' => 'Wholewheat porridge with a sliced banana',
            'Morning snack' => 'An orange',
            'Lunch'     => 'Bean and vegetable soup made without stock cubes, one slice of brown bread',
            'Afternoon snack' => 'A handful of unsalted peanuts',
            'Supper'    => 'Baked fish with garlic and lemon, brown rice, butternut and green beans',
        ],
        'tip'  => 'Taste before you salt — and keep the salt cellar off the table.',
        'products' => ['now-blood-pressure-health', 'ultimate-male-blood-flow'],
    ],
    'hiv' => [
        'name' => 'Living with HIV', 'icon' => 'fa-shield-heart', 'colour' => '#7A4FB5',
        'aim'  => 'Keep a strong, well-nourished body alongside daily ARVs: enough protein and energy, plenty of vegetables, and safe food.',
        'more' => ['Protein at every meal: eggs, beans, fish, chicken, peanut butter, maas', 'Colourful vegetables and fruit', 'Whole grains for steady energy', 'Plenty of clean water'],
        'less' => ['Alcohol', 'Raw or undercooked eggs, meat and fish', 'Unwashed fruit and vegetables', 'Very fatty and sugary foods'],
        'day'  => [
            'Breakfast' => 'Maize-meal porridge with peanut butter and milk',
            'Morning snack' => 'A banana and a glass of maas',
            'Lunch'     => 'Rice, beans and a tomato-and-onion relish',
            'Afternoon snack' => 'Boiled egg and an orange',
            'Supper'    => 'Chicken stew with potatoes, carrots and spinach',
        ],
        'tip'  => 'Take ARVs at the same time every day. If they upset your stomach, ask your clinic whether to take them with food.',
        'products' => ['shieldwell-immune-support', 'viracure-natural-formula', 'hpdrx-one'],
    ],
    'tb' => [
        'name' => 'TB recovery', 'icon' => 'fa-lungs', 'colour' => '#2F5F8A',
        'aim'  => 'Rebuild weight and muscle lost to TB: three meals and two snacks a day, extra protein, and no alcohol while on treatment.',
        'more' => ['Energy-rich meals: porridge, rice, potatoes, bread', 'Protein: eggs, beans, milk, fish, chicken, peanut butter', 'Vitamin-rich vegetables and fruit', 'Fortified porridge'],
        'less' => ['All alcohol (it strains the liver during treatment)', 'Smoking', 'Skipping meals'],
        'day'  => [
            'Breakfast' => 'Fortified maize porridge with milk and peanut butter',
            'Morning snack' => 'Bread with egg',
            'Lunch'     => 'Samp and beans with cabbage',
            'Afternoon snack' => 'Yoghurt and a fruit',
            'Supper'    => 'Rice, beef or chicken stew, pumpkin and spinach',
        ],
        'tip'  => 'Finish every dose of TB medicine, even once you feel well.',
        'products' => ['tuberashield-respiratory-support', 'lung-assistance'],
    ],
    'cancer' => [
        'name' => 'During cancer treatment', 'icon' => 'fa-hand-holding-medical', 'colour' => '#B5793C',
        'aim'  => 'Keep strength and weight up when appetite is low: small, frequent, protein-rich meals that are gentle on the mouth and stomach.',
        'more' => ['Soft protein: eggs, yoghurt, maas, fish, mashed beans', 'Smoothies and soups when solid food is hard', 'Ginger tea and dry crackers for nausea', 'Plenty of fluids'],
        'less' => ['Spicy, very acidic or rough foods if your mouth is sore', 'Raw or undercooked food when immunity is low', 'Any supplement your oncologist has not approved'],
        'day'  => [
            'Breakfast' => 'Soft porridge with milk and honey',
            'Morning snack' => 'Banana-and-yoghurt smoothie',
            'Lunch'     => 'Chicken and vegetable soup with soft bread',
            'Afternoon snack' => 'Scrambled egg',
            'Supper'    => 'Mashed potato, steamed fish and soft-cooked carrots',
        ],
        'tip'  => 'Eat when you feel best — often the morning — and keep easy snacks within reach.',
        'products' => ['oncoharbor-supportive-care', 'essiac-herbal-tea'],
    ],
    'heart' => [
        'name' => 'Heart health & cholesterol', 'icon' => 'fa-heart', 'colour' => '#E2563F',
        'aim'  => 'Lower bad cholesterol and protect your heart: swap saturated fat for healthy oils, add fibre and oily fish.',
        'more' => ['Oats and beans (soluble fibre)', 'Pilchards, sardines and mackerel', 'Olive, canola or sunflower oil in small amounts', 'Avocado and unsalted nuts', 'Vegetables and fruit'],
        'less' => ['Fatty red meat, boerewors and chicken skin', 'Fried food and pastries', 'Butter, cream and hard margarine', 'Sugar and white flour'],
        'day'  => [
            'Breakfast' => 'Oats with apple and a few nuts',
            'Morning snack' => 'A pear',
            'Lunch'     => 'Brown bread with pilchards, tomato and lettuce',
            'Afternoon snack' => 'Half an avocado on crackers',
            'Supper'    => 'Grilled chicken without skin, brown rice, a big salad with olive oil',
        ],
        'tip'  => 'Grill, bake, steam or boil rather than fry.',
        'products' => ['now-blood-pressure-health', 'now-nad-resveratrol'],
    ],
    'kidney' => [
        'name' => 'Kidney care', 'icon' => 'fa-filter', 'colour' => '#1BA39C',
        'aim'  => 'Ease the load on your kidneys: less salt and processed food, moderate protein. Your clinic may also set potassium, phosphate or fluid limits — follow theirs.',
        'more' => ['Fresh, home-cooked food', 'Moderate portions of chicken, fish and eggs', 'Rice, pasta and bread in sensible portions', 'Lower-potassium fruit such as apples and pears (if your clinic advises)'],
        'less' => ['Salt and processed meat', 'Cola drinks (high in phosphate)', 'Over-the-counter painkillers like ibuprofen', 'Herbal remedies not cleared by your clinic'],
        'day'  => [
            'Breakfast' => 'Porridge with a little milk and an apple',
            'Morning snack' => 'Rice cakes',
            'Lunch'     => 'Chicken and vegetable stir-fry with rice, no added salt',
            'Afternoon snack' => 'A pear',
            'Supper'    => 'Fish, pasta and cabbage cooked with herbs instead of salt',
        ],
        'tip'  => 'Kidney diets differ a lot by stage — ask for a dietitian referral at your clinic.',
        'products' => [],
    ],
    'arthritis' => [
        'name' => 'Arthritis & gout', 'icon' => 'fa-person-walking', 'colour' => '#5C7A2E',
        'aim'  => 'Calm inflammation and keep a healthy weight. For gout, also cut foods high in purines and drink plenty of water.',
        'more' => ['Oily fish twice a week', 'Vegetables, fruit and whole grains', 'Low-fat milk and yoghurt', 'Water — two litres a day (helps clear uric acid in gout)'],
        'less' => ['Red meat, liver, kidneys and other organ meat (gout)', 'Beer and spirits (gout)', 'Sugary drinks and sweets', 'Fried and highly processed food'],
        'day'  => [
            'Breakfast' => 'Oats with yoghurt and berries or banana',
            'Morning snack' => 'An orange',
            'Lunch'     => 'Lentil curry with brown rice and spinach',
            'Afternoon snack' => 'Low-fat yoghurt',
            'Supper'    => 'Baked sardines or chicken, sweet potato, broccoli',
        ],
        'tip'  => 'Pair the diet with gentle daily movement — it is the best medicine for stiff joints.',
        'products' => ['now-shark-cartilage-750mg', 'soothestep-gel', 'wellvita-ashwagandha'],
    ],
];
