-- ============================================================================
--  Phola — starter catalogue and content
--
--  Every price, product and article here is a starting point for the admin to
--  edit in Admin → Products. Photos are from Unsplash and Pexels (free to use
--  commercially) and live in assets/img/stock/.
-- ============================================================================
SET NAMES utf8mb4;

-- ── Accounts ─────────────────────────────────────────────────────────────
-- Staff:   info@pholahealthsolutions.co.za — the password is in ADMIN-LOGIN.txt on the computer that
--          built the site (never in GitHub). Change it after your first sign-in.
-- Patient: phone 082 000 0001 / PIN 1234           (demo — delete before launch)
INSERT INTO `users` (`id`,`name`,`email`,`password`,`role`,`phone`,`pin_hash`,`conditions`,`reminders_enabled`) VALUES
(1,'Phola Admin','info@pholahealthsolutions.co.za','$2y$12$2680eWZk6uw0AkA6wjpM2eFISR0Xwnr0ct/qdI7T6xfs6zgKtx.WK','admin','27630437385',NULL,NULL,0),
(2,'Demo Patient',NULL,NULL,'customer','27820000001','$2y$12$lR8efExa90tvU1hJKVSyXeiQPFAI9elcS1M0l0dVCBpVs5Ri51Rjm','Immune support',1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- -- Brands --
INSERT INTO `brands` (`id`,`name`,`slug`,`tagline`,`blurb`,`accent`,`sort_order`) VALUES
(1,'Phola Wellness','phola-wellness','Our own-label daily support ranges.','Targeted supplements chosen and packed for Phola customers, each with clear directions and an honest note on what it can and cannot do.','#1E7A3C',1),
(2,'NOW Foods','now-foods','Trusted supplements since 1968.','Well-known US supplement brand stocked by Phola: shark cartilage, NAD+ and blood pressure support.','#E8681F',2),
(3,'Partner Brands','partner-brands','Favourites from brands we trust.','Skin care, herbal teas, multivitamins and specialist formulas from established health brands.','#2F5F8A',3)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `categories` (`id`,`name`,`slug`,`icon`,`blurb`,`image`,`sort_order`) VALUES
(1,'Immune Support','immune-support','fa-shield-heart','Daily support for a stronger immune system.','assets/img/products/01.jpg',1),
(2,'Blood Sugar Support','blood-sugar-support','fa-droplet','Support for healthy glucose balance.','assets/img/products/02.jpg',2),
(3,'Heart & Circulation','heart-circulation','fa-heart-pulse','Blood pressure and circulation support.','assets/img/products/26.jpg',3),
(4,'Men\'s Health','mens-health','fa-mars','Vitality, energy and performance support for men.','assets/img/products/08.jpg',4),
(5,'Women\'s Health','womens-health','fa-venus','Balance and everyday wellness for women.','assets/img/products/09.jpg',5),
(6,'Weight Management','weight-management','fa-weight-scale','Support for a healthy weight and metabolism.','assets/img/products/10.jpg',6),
(7,'Brain, Energy & Ageing','brain-energy','fa-brain','Focus, memory, energy and healthy ageing.','assets/img/products/25.jpg',7),
(8,'Lungs & Breathing','lungs-breathing','fa-lungs','Herbal and nutritional support for the respiratory system.','assets/img/products/23.jpg',8),
(9,'Supportive Care','supportive-care','fa-hand-holding-medical','Nutritional support during demanding health journeys.','assets/img/products/04.jpg',9),
(10,'Skin & Body Care','skin-body','fa-spa','Creams and gels for skin, feet and joints.','assets/img/products/27.jpg',10)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Product photos: NN.jpg = wide shop header, NNb.jpg = pack shot. Products 24-33 have no pack shot yet,
-- so featured_image is NULL and the site shows its 'photo coming soon' placeholder.
-- Prices are starting points: confirm them in Admin -> Products before launch.
INSERT INTO `products`
(`id`,`category_id`,`brand_id`,`name`,`slug`,`short_desc`,`description`,`benefits`,`usage_notes`,`type`,`retail_price`,`sale_price`,`pack_size`,`sku`,`stock_status`,`stock_qty`,`featured_image`,`header_image`,`is_featured`,`is_sale`,`is_bestseller`,`is_new`,`status`,`tags`) VALUES
(1,1,1,'ShieldWell Immune Support','shieldwell-immune-support','A daily immune-support capsule from our ShieldWell range, for adults who want extra support for their immune system.','<p>A daily immune-support capsule from our ShieldWell range, for adults who want extra support for their immune system.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports normal immune function
Helps fill common nutrient gaps
One capsule a day
Take alongside — never instead of — prescribed treatment','Take one capsule daily with food.','simple',450.00,NULL,'30 capsules','PHL-01','in_stock',50,'assets/img/products/01b.jpg','assets/img/products/01.jpg',1,0,1,0,'published','immune,shieldwell,daily'),
(2,2,1,'GlucoBalance Blood Sugar Support','glucobalance-blood-sugar-support','Daily capsules that support healthy blood glucose balance as part of a healthy diet and lifestyle.','<p>Daily capsules that support healthy blood glucose balance as part of a healthy diet and lifestyle.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports healthy blood sugar balance
Natural herbal extracts
Supports overall metabolic wellness
30 capsules — one month','Take one capsule daily with your main meal. Speak to your clinic first if you use insulin or diabetes tablets.','simple',395.00,NULL,'30 capsules','PHL-02','in_stock',50,'assets/img/products/02b.jpg','assets/img/products/02.jpg',1,0,1,0,'published','glucobalance,blood sugar,glucose,diabetes support'),
(3,2,1,'GlucoBalance Pen','glucobalance-pen','The GlucoBalance blood glucose support pen — a convenient way to add GlucoBalance to your daily routine.','<p>The GlucoBalance blood glucose support pen — a convenient way to add GlucoBalance to your daily routine.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Blood glucose support
Compact, easy-to-carry pen
Pairs with GlucoBalance capsules','Use exactly as directed on the pack insert. Ask us on WhatsApp if you are unsure how to use it.','simple',650.00,NULL,'1 pen','PHL-03','in_stock',50,'assets/img/products/03b.jpg','assets/img/products/03.jpg',1,0,0,1,'published','glucobalance,pen,blood sugar'),
(4,9,1,'OncoHarbor Supportive Care','oncoharbor-supportive-care','Nutritional supportive-care capsules for people going through demanding treatment who want daily nutritional support.','<p>Nutritional supportive-care capsules for people going through demanding treatment who want daily nutritional support.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Daily nutritional support
Gentle once-a-day capsule
Use only with your oncology team’s approval','Take one capsule daily with food — only after checking with your treating doctor, as some supplements interact with cancer treatment.','simple',520.00,NULL,'30 capsules','PHL-04','in_stock',50,'assets/img/products/04b.jpg','assets/img/products/04.jpg',0,0,0,1,'published','supportive care,oncoharbor,wellness'),
(5,7,1,'MemoryHarbor Cognitive Support','memoryharbor-cognitive-support','Memory-care capsules with nutrients that support normal cognitive function, focus and recall.','<p>Memory-care capsules with nutrients that support normal cognitive function, focus and recall.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports normal cognitive function
Focus and memory support
One capsule a day','Take one capsule daily with breakfast.','simple',420.00,NULL,'30 capsules','PHL-05','in_stock',50,'assets/img/products/05b.jpg','assets/img/products/05.jpg',1,0,0,1,'published','memory,brain,focus,cognitive'),
(6,8,1,'TuberaShield Respiratory Support','tuberashield-respiratory-support','Respiratory and immune support capsules for people who want to look after their lungs during recovery.','<p>Respiratory and immune support capsules for people who want to look after their lungs during recovery.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports lung and immune health
Nutritional support during recovery
Never replaces TB treatment — finish every course','Take one capsule daily with food. If you are on TB treatment, keep taking every dose exactly as your clinic prescribes.','simple',480.00,NULL,'30 capsules','PHL-06','in_stock',50,'assets/img/products/06b.jpg','assets/img/products/06.jpg',0,0,0,1,'published','lungs,respiratory,immune,tuberashield'),
(7,2,1,'MellitusCare Metformin 500 mg','mellituscare-metformin-500mg','Metformin 500 mg tablets.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'30 tablets','PHL-07','out_of_stock',0,'assets/img/products/07b.jpg','assets/img/products/07.jpg',0,0,0,0,'draft','rx,metformin'),
(8,4,1,'VirilityAxis Men’s Vitality Complex','virilityaxis-mens-vitality','A men’s vitality complex with KSM-66 ashwagandha, black maca and vegetarian capsules for energy and drive.','<p>A men’s vitality complex with KSM-66 ashwagandha, black maca and vegetarian capsules for energy and drive.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','KSM-66 ashwagandha and black maca
Supports energy, stamina and drive
Vegetarian capsules','Take two capsules daily with food.','simple',495.00,449.00,'60 capsules','PHL-08','in_stock',50,'assets/img/products/08b.jpg','assets/img/products/08.jpg',1,1,1,0,'published','men,vitality,maca,ashwagandha,energy'),
(9,5,1,'CurveAura Women’s Wellness','curveaura-womens-wellness','A daily women’s wellness supplement with vitamins and botanicals for natural balance.','<p>A daily women’s wellness supplement with vitamins and botanicals for natural balance.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Vitamins, botanicals and balance
Supports everyday wellness
60 capsules, 500 mg','Take two capsules daily with a meal.','simple',420.00,NULL,'60 capsules','PHL-09','in_stock',50,'assets/img/products/09b.jpg','assets/img/products/09.jpg',1,0,1,0,'published','women,wellness,balance,curveaura'),
(10,6,1,'LeanLine Weight Wellness','leanline-weight-wellness','A once-daily dietary supplement that supports a healthy weight alongside sensible eating and regular activity.','<p>A once-daily dietary supplement that supports a healthy weight alongside sensible eating and regular activity.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports a healthy lifestyle and weight
One capsule a day
60 capsules — two months','Take one capsule daily before your first meal, with a full glass of water.','simple',380.00,339.00,'60 capsules','PHL-10','in_stock',50,'assets/img/products/10b.jpg','assets/img/products/10.jpg',1,1,1,0,'published','weight,slimming,leanline,metabolism'),
(11,5,1,'ReproChoice Reproductive Health Kit','reprochoice-kit','Reproductive health support kit.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'1 kit','PHL-11','out_of_stock',0,'assets/img/products/11b.jpg','assets/img/products/11.jpg',0,0,0,0,'draft','rx'),
(12,9,1,'Gammora (30 units)','gammora-30-units','Medical-use-only product.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'30 units','PHL-12','out_of_stock',0,'assets/img/products/12b.jpg','assets/img/products/12.jpg',0,0,0,0,'draft','rx'),
(13,9,1,'Gammora (10 units)','gammora-10-units','Medical-use-only product.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'10 units','PHL-13','out_of_stock',0,'assets/img/products/13b.jpg','assets/img/products/13.jpg',0,0,0,0,'draft','rx'),
(14,1,1,'ViraCure Natural Antiviral Formula','viracure-natural-formula','A natural herbal formula that supports the immune system through cold and flu season.','<p>A natural herbal formula that supports the immune system through cold and flu season.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports immune system health
Herbal extracts
Supports general wellness
60 capsules','Take two capsules daily with food.','simple',450.00,NULL,'60 capsules','PHL-14','in_stock',50,'assets/img/products/14b.jpg','assets/img/products/14.jpg',1,0,1,0,'published','immune,viracure,herbal,cold and flu'),
(15,6,3,'Wegovy (semaglutide) 2.4 mg','wegovy-2-4mg','Prescription-only weight-management injection.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'4 pens','PHL-15','out_of_stock',0,'assets/img/products/15b.jpg','assets/img/products/15.jpg',0,0,0,0,'draft','rx,glp-1'),
(16,6,3,'Ozempic (semaglutide) 2 mg','ozempic-2mg','Prescription-only medicine.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'30 tablets','PHL-16','out_of_stock',0,'assets/img/products/16b.jpg','assets/img/products/16.jpg',0,0,0,0,'draft','rx,glp-1'),
(17,6,3,'Mounjaro (tirzepatide) 15 mg','mounjaro-15mg','Prescription-only medicine.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'30 tablets','PHL-17','out_of_stock',0,'assets/img/products/17b.jpg','assets/img/products/17.jpg',0,0,0,0,'draft','rx,glp-1'),
(18,9,3,'Ivermectin + Fenbendazole','ivermectin-fenbendazole','Antiparasitic capsules.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'30 capsules','PHL-18','out_of_stock',0,'assets/img/products/18b.jpg','assets/img/products/18.jpg',0,0,0,0,'draft','rx'),
(19,9,2,'NOW Shark Cartilage 750 mg','now-shark-cartilage-750mg','NOW Foods shark cartilage with naturally occurring mucopolysaccharides and calcium for skeletal health.','<p>NOW Foods shark cartilage with naturally occurring mucopolysaccharides and calcium for skeletal health.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','750 mg per capsule
Promotes skeletal health
Naturally occurring mucopolysaccharides and calcium','Take as directed on the label, with food.','simple',420.00,NULL,'100 capsules','PHL-19','in_stock',50,'assets/img/products/19b.jpg','assets/img/products/19.jpg',0,0,0,0,'published','now,shark cartilage,joints,bones'),
(20,7,2,'NOW NAD+ Resveratrol 1000 mg','now-nad-resveratrol','NOW Foods NAD+ with resveratrol for cellular energy and healthy ageing.','<p>NOW Foods NAD+ with resveratrol for cellular energy and healthy ageing.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports NAD+ levels
Antioxidant protection
Promotes healthy ageing','Take as directed on the label.','simple',690.00,NULL,'60 capsules','PHL-20','in_stock',50,'assets/img/products/20b.jpg','assets/img/products/20.jpg',1,0,1,0,'published','now,nad,resveratrol,ageing,energy'),
(21,1,3,'Essiac Herbal Tea','essiac-herbal-tea','The traditional Essiac herbal tea blend from Rene M. Caisse, RN, for immune system support.','<p>The traditional Essiac herbal tea blend from Rene M. Caisse, RN, for immune system support.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Traditional four-herb blend
Immune system support
Makes a daily herbal tea','Prepare as directed on the pack.','simple',520.00,NULL,'42.5 g','PHL-21','in_stock',50,'assets/img/products/21b.jpg','assets/img/products/21.jpg',0,0,0,0,'published','essiac,herbal tea,immune'),
(22,1,3,'HPDRx ONE Immune & Cellular Support','hpdrx-one','A high-potency multivitamin, mineral and antioxidant complex for immune system and cellular health support.','<p>A high-potency multivitamin, mineral and antioxidant complex for immune system and cellular health support.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Vitamins, minerals and antioxidants in one
Immune and cellular support
180 capsules','Take as directed on the label, with food.','simple',890.00,NULL,'180 capsules','PHL-22','in_stock',50,'assets/img/products/22b.jpg','assets/img/products/22.jpg',0,0,0,0,'published','multivitamin,immune,antioxidant'),
(23,8,1,'Lung Assistance 60-Herb Supplement','lung-assistance','A 60-herb formula that supports respiratory health, clear airways and the body’s natural defences.','<p>A 60-herb formula that supports respiratory health, clear airways and the body’s natural defences.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports respiratory health
Helps clear airways
Boosts natural defences','Take two capsules daily with food.','simple',380.00,NULL,'60 capsules','PHL-23','in_stock',50,'assets/img/products/23b.jpg','assets/img/products/23.jpg',1,0,0,0,'published','lungs,herbal,respiratory'),
(24,7,1,'NAD+ Resveratrol 1000 mg','nad-resveratrol-1000mg','NAD+ with resveratrol for cellular energy, antioxidant support and healthy ageing.','<p>NAD+ with resveratrol for cellular energy, antioxidant support and healthy ageing.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports cellular energy
Powerful antioxidant
Healthy ageing support','Take two capsules daily.','simple',590.00,NULL,'60 capsules','PHL-24','in_stock',50,NULL,'assets/img/products/24.jpg',0,0,0,1,'published','nad,resveratrol,ageing'),
(25,7,1,'Phola Magnesium Brain Focus','magnesium-brain-focus','Our high-absorption magnesium formula for focus, memory, clarity and mental performance.','<p>Our high-absorption magnesium formula for focus, memory, clarity and mental performance.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','High-absorption magnesium
Focus, memory and clarity
Supports mental performance','Take two capsules daily, one in the morning and one in the evening.','simple',320.00,NULL,'60 capsules','PHL-25','in_stock',50,NULL,'assets/img/products/25.jpg',1,0,0,1,'published','magnesium,brain,focus,phola'),
(26,3,2,'NOW Blood Pressure Health','now-blood-pressure-health','NOW Foods cardiovascular support that helps maintain blood pressure already within the healthy range.','<p>NOW Foods cardiovascular support that helps maintain blood pressure already within the healthy range.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Cardiovascular support
Helps maintain healthy blood pressure
Vegetarian/vegan capsules','Take as directed on the label. Keep taking any prescribed blood pressure tablets.','simple',450.00,NULL,'90 capsules','PHL-26','in_stock',50,NULL,'assets/img/products/26.jpg',1,0,0,0,'published','blood pressure,heart,now'),
(27,10,3,'CeraVe Psoriasis Moisturizing Cream','cerave-psoriasis-cream','CeraVe moisturizing cream with salicylic acid and ceramides, developed with dermatologists for psoriasis-prone skin.','<p>CeraVe moisturizing cream with salicylic acid and ceramides, developed with dermatologists for psoriasis-prone skin.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Helps reduce scaling
Three essential ceramides
Developed with dermatologists','Apply to affected areas as directed on the pack.','simple',380.00,NULL,'227 g','PHL-27','in_stock',50,NULL,'assets/img/products/27.jpg',0,0,0,0,'published','cerave,psoriasis,skin,cream'),
(28,2,3,'Nativa Complex Diabetic Multivitamin','nativa-diabetic-multivitamin','A multivitamin made for people managing diabetes, covering the nutrients they most often run low on.','<p>A multivitamin made for people managing diabetes, covering the nutrients they most often run low on.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Multivitamin for people with diabetes
Supports healthy metabolism
One capsule a day','Take one capsule daily with food.','simple',260.00,NULL,'30 capsules','PHL-28','in_stock',50,NULL,'assets/img/products/28.jpg',0,0,0,0,'published','multivitamin,diabetes,vitamins'),
(29,9,3,'Stem Cell Supplement with Sea Buckthorn','stem-cell-supplement','A plant-based stem cell support supplement made with sea buckthorn berry, red grape and black currant.','<p>A plant-based stem cell support supplement made with sea buckthorn berry, red grape and black currant.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Made with sea buckthorn berry
Red grape and black currant
Plant-based support','Take two capsules daily.','simple',480.00,NULL,'60 capsules','PHL-29','in_stock',50,NULL,'assets/img/products/29.jpg',0,0,0,1,'published','stem cell,sea buckthorn,antioxidant'),
(30,4,3,'Ultimate Male Blood Flow','ultimate-male-blood-flow','A men’s circulation and blood-flow support supplement.','<p>A men’s circulation and blood-flow support supplement.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Supports healthy circulation
Men’s vitality formula
90 capsules','Take three capsules daily with food.','simple',450.00,NULL,'90 capsules','PHL-30','in_stock',50,NULL,'assets/img/products/30.jpg',0,0,1,0,'published','men,blood flow,circulation'),
(31,7,3,'Wellvita Ashwagandha Root Extract','wellvita-ashwagandha','Ashwagandha root extract for stress and fatigue recovery, with antioxidant support.','<p>Ashwagandha root extract for stress and fatigue recovery, with antioxidant support.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Stress and fatigue recovery
Antioxidant and cell protection
Veggie capsules','Take one capsule daily.','simple',220.00,NULL,'30 capsules','PHL-31','in_stock',50,NULL,'assets/img/products/31.jpg',0,0,0,0,'published','ashwagandha,stress,sleep,energy'),
(32,5,3,'Misoprostol Tablets 200 mcg','misoprostol-200mcg','Prescription-only medicine.','<p><b>Prescription item — not currently for sale.</b> This product is saved as a draft until Phola has the licence, prescription process and proof of genuine supply that South African law requires.</p>',NULL,NULL,'simple',0.00,NULL,'Tablets','PHL-32','out_of_stock',0,NULL,'assets/img/products/32.jpg',0,0,0,0,'draft','rx'),
(33,10,3,'SootheStep Relieving Topical Gel','soothestep-gel','A cooling, soothing gel for swollen feet and ankles, with aloe vera and arnica.','<p>A cooling, soothing gel for swollen feet and ankles, with aloe vera and arnica.</p><p><b>This is a supplement, not a medicine.</b> It does not diagnose, treat, cure or prevent any disease and never replaces treatment prescribed by your doctor or clinic. Keep taking your prescribed medication and tell your clinic you are using it.</p>','Cooling and soothing
Aloe vera and arnica
For swollen feet and ankles','Massage gently into feet and ankles up to three times a day. Not for broken skin.','simple',190.00,NULL,'100 ml','PHL-33','in_stock',50,NULL,'assets/img/products/33.jpg',0,0,0,1,'published','feet,gel,swelling,arnica')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- The wide shop photo is also the second gallery image on every product page.
INSERT INTO `product_gallery` (`product_id`,`image`,`caption`,`sort_order`) VALUES
(1,'assets/img/products/01.jpg','In store at Phola',1),
(2,'assets/img/products/02.jpg','In store at Phola',1),
(3,'assets/img/products/03.jpg','In store at Phola',1),
(4,'assets/img/products/04.jpg','In store at Phola',1),
(5,'assets/img/products/05.jpg','In store at Phola',1),
(6,'assets/img/products/06.jpg','In store at Phola',1),
(7,'assets/img/products/07.jpg','In store at Phola',1),
(8,'assets/img/products/08.jpg','In store at Phola',1),
(9,'assets/img/products/09.jpg','In store at Phola',1),
(10,'assets/img/products/10.jpg','In store at Phola',1),
(11,'assets/img/products/11.jpg','In store at Phola',1),
(12,'assets/img/products/12.jpg','In store at Phola',1),
(13,'assets/img/products/13.jpg','In store at Phola',1),
(14,'assets/img/products/14.jpg','In store at Phola',1),
(15,'assets/img/products/15.jpg','In store at Phola',1),
(16,'assets/img/products/16.jpg','In store at Phola',1),
(17,'assets/img/products/17.jpg','In store at Phola',1),
(18,'assets/img/products/18.jpg','In store at Phola',1),
(19,'assets/img/products/19.jpg','In store at Phola',1),
(20,'assets/img/products/20.jpg','In store at Phola',1),
(21,'assets/img/products/21.jpg','In store at Phola',1),
(22,'assets/img/products/22.jpg','In store at Phola',1),
(23,'assets/img/products/23.jpg','In store at Phola',1),
(24,'assets/img/products/24.jpg','In store at Phola',1),
(25,'assets/img/products/25.jpg','In store at Phola',1),
(26,'assets/img/products/26.jpg','In store at Phola',1),
(27,'assets/img/products/27.jpg','In store at Phola',1),
(28,'assets/img/products/28.jpg','In store at Phola',1),
(29,'assets/img/products/29.jpg','In store at Phola',1),
(30,'assets/img/products/30.jpg','In store at Phola',1),
(31,'assets/img/products/31.jpg','In store at Phola',1),
(32,'assets/img/products/32.jpg','In store at Phola',1),
(33,'assets/img/products/33.jpg','In store at Phola',1)
;

-- ── Testimonials ─────────────────────────────────────────────────────────
INSERT INTO `testimonials` (`name`,`role`,`location`,`quote`,`rating`,`is_reseller`,`sort_order`) VALUES
('Thandi M.','Patient','Soweto, Gauteng','The email at eight every morning is the reason I have not missed a dose in two months. I tap the button and it is logged.',5,0,1),
('Sipho K.','Patient','Durban, KZN','I asked on WhatsApp which vitamins were safe with my treatment. They answered the same afternoon and my order came two days later.',5,0,2),
('Lerato D.','Caregiver','Centurion','I look after my mother\'s medication. Having her pill times and side effects written down in one place makes the clinic visits much easier.',5,0,3),
('Johan P.','Patient','Bloemfontein','Bought the blood pressure monitor and now log my readings every morning. My doctor liked seeing the chart.',4,0,4);

-- ── FAQs ─────────────────────────────────────────────────────────────────
INSERT INTO `faqs` (`question`,`answer`,`topic`,`sort_order`) VALUES
('How do I order?','Press "Enquire on WhatsApp" on any product, or add a few products to your list and send them all at once. Your message arrives at +27 63 043 7385 with the products and prices filled in. We confirm stock, the total and delivery, and send payment details.','Ordering',1),
('How do I pay?','Once we confirm your order on WhatsApp we send our banking details for an EFT. Send the proof of payment back on the same WhatsApp chat and we dispatch the same or next working day.','Ordering',2),
('How long does delivery take?','Courier delivery takes 2–4 working days to main centres and up to 5 working days to outlying areas. We send the tracking number on WhatsApp.','Delivery',1),
('What is the patient portal?','After you buy, we set up your medication schedule. You sign in with your cellphone number and a 4-digit PIN to tick off doses, log how you feel and report any side effects. If you give us your email address, we email you a reminder at every dose time.','Patient portal',1),
('I forgot my PIN. What now?','Send us a WhatsApp from the number you registered with and we will reset it. For your privacy we never send PINs by email or SMS.','Patient portal',2),
('What should I do if I get a side effect?','Report it in the portal under "Side effects" so our team can see it, and stop the product if it is bothering you. If it is severe — difficulty breathing, swelling of the face or a rash that spreads — go to your nearest clinic or call 10177 immediately.','Patient portal',3),
('Can I take supplements with my chronic medication?','Many people do, but some combinations interact. Always tell your doctor or clinic what you take. Supplements never replace prescribed medication — including ARVs, blood pressure and diabetes treatment.','Health',1),
('Are the supplements registered?','Supplements sold here are complementary medicines. Unless stated otherwise they have not been evaluated by SAHPRA for quality, safety or intended use, and are not intended to diagnose, treat, cure or prevent any disease.','Health',2),
('Is my health information private?','Yes. Only you and authorised Phola staff can see your medication, readings and side-effect reports. We handle your information under POPIA and never sell or share it.','Privacy',1),
('Can I return something?','Unopened products in their original packaging can be returned within 7 days of delivery. Opened health products cannot be returned for hygiene reasons. Anything damaged in transit is replaced free.','Delivery',2);

-- ── Learn hub ────────────────────────────────────────────────────────────
INSERT INTO `blogs` (`title`,`slug`,`image`,`excerpt`,`content`,`category`,`author_id`,`read_mins`,`is_featured`) VALUES
('Taking supplements alongside chronic medication','supplements-with-chronic-medication','assets/img/stock/patient-care.jpg',
 'Supplements can help fill gaps, but some combinations matter. What to ask your clinic before you start anything new.',
 '<p>Most people managing a long-term condition can take everyday supplements safely. A few combinations, though, change how well medication works.</p><h3>Tell your clinic everything</h3><p>Bring the bottle to your next visit, or a photo of the label. Your nurse or doctor can check it against your treatment in a minute.</p><h3>Timing matters</h3><p>Iron, calcium and zinc can reduce how much of some medicines your body absorbs. Taking them a few hours apart usually solves it — your clinic will tell you if it applies to you.</p><h3>Never swap one for the other</h3><p>No supplement replaces prescribed treatment, including ARVs, blood pressure tablets or insulin. Keep taking your medication exactly as prescribed.</p>',
 'Health',1,5,1),

('Five ways to stop missing doses','five-ways-to-stop-missing-doses','assets/img/stock/pill-box.jpg',
 'Missed doses are rarely about willpower. Small systems — a pill box, a fixed time, a reminder — do most of the work.',
 '<p>Nearly everyone on daily medication misses a dose sometimes. The fix is a routine that does not depend on memory.</p><h3>1. Tie it to something you already do</h3><p>Breakfast, brushing your teeth, the evening news. Same time, same place.</p><h3>2. Use a weekly pill box</h3><p>Fill it on Sunday. An empty compartment tells you that you took it.</p><h3>3. Turn on reminders</h3><p>Add your email in the Phola portal and we will email you at each dose time, with a one-tap button to log it.</p><h3>4. Keep a spare dose in your bag</h3><p>For the days you do not make it home.</p><h3>5. Track it</h3><p>Seeing your adherence climb week by week is surprisingly motivating.</p>',
 'Routine',1,4,1),

('When a side effect needs attention','when-a-side-effect-needs-attention','assets/img/stock/nurse-bp.jpg',
 'Most side effects are mild and fade. Some need a clinic today. How to tell the difference — and how to report them.',
 '<p>New products can cause mild effects in the first week or two: an upset stomach, a headache, changes in sleep. These usually settle.</p><h3>Report it anyway</h3><p>Log it in the portal under Side effects. Our team reviews every report and replies there.</p><h3>Go to a clinic straight away if you have</h3><ul><li>difficulty breathing or swelling of the lips, face or throat</li><li>a rash that spreads or blisters</li><li>yellowing of the skin or eyes</li><li>severe stomach pain or vomiting that will not stop</li></ul><p>In an emergency call 10177 or go to your nearest emergency unit.</p>',
 'Health',1,4,0);

-- ── Settings ─────────────────────────────────────────────────────────────
INSERT INTO `settings` (`key_name`,`value`) VALUES
('site_name','Phola Health Solutions'),
('site_tagline','Health & Wellness, Delivered.'),
('currency_symbol','R'),
('free_shipping_threshold','1000'),
('shipping_flat_rate','100'),
('contact_phone','+27 63 043 7385'),
('contact_whatsapp','27630437385'),
('contact_email','info@pholahealthsolutions.co.za'),
('contact_address','South Africa — nationwide courier delivery'),
('facebook_url',''),
('instagram_url',''),
('tiktok_url',''),
('maintenance_mode','0'),
('newsletter_gate_mode','off'),
('announcement','Order on WhatsApp: +27 63 043 7385 · Courier delivery nationwide in 2–4 working days')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- ── Demo patient: one medication and a few days of history ───────────────
INSERT INTO `patient_medications` (`id`,`user_id`,`product_id`,`name`,`dosage`,`instructions`,`dose_times`,`start_date`,`units_left`,`units_per_dose`,`added_by`) VALUES
(1,2,1,'ShieldWell Immune Support','1 capsule','With breakfast, alongside ARVs','08:00',CURDATE() - INTERVAL 10 DAY,50,1,1),
(2,2,2,'GlucoBalance Blood Sugar Support','1 capsule','After supper','19:00',CURDATE() - INTERVAL 10 DAY,52,1,1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

UPDATE `products` SET `sold_count` = FLOOR(RAND()*120)+15, `view_count` = FLOOR(RAND()*700)+90;

-- Demo patient history, so the tracker and admin charts have something to show. Remove before launch.
INSERT IGNORE INTO `dose_logs` (`medication_id`,`user_id`,`scheduled_for`,`status`,`source`) VALUES
(1,2,CONCAT(CURDATE() - INTERVAL 10 DAY, ' 08:00:00'),'taken','portal'),
(2,2,CONCAT(CURDATE() - INTERVAL 10 DAY, ' 19:00:00'),'taken','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 9 DAY, ' 08:00:00'),'taken','email'),
(2,2,CONCAT(CURDATE() - INTERVAL 9 DAY, ' 19:00:00'),'taken','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 8 DAY, ' 08:00:00'),'taken','portal'),
(2,2,CONCAT(CURDATE() - INTERVAL 8 DAY, ' 19:00:00'),'taken','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 7 DAY, ' 08:00:00'),'taken','portal'),
(2,2,CONCAT(CURDATE() - INTERVAL 7 DAY, ' 19:00:00'),'skipped','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 6 DAY, ' 08:00:00'),'taken','email'),
(2,2,CONCAT(CURDATE() - INTERVAL 6 DAY, ' 19:00:00'),'taken','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 5 DAY, ' 08:00:00'),'taken','portal'),
(2,2,CONCAT(CURDATE() - INTERVAL 5 DAY, ' 19:00:00'),'taken','portal'),
(2,2,CONCAT(CURDATE() - INTERVAL 4 DAY, ' 19:00:00'),'taken','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:00:00'),'taken','email'),
(2,2,CONCAT(CURDATE() - INTERVAL 3 DAY, ' 19:00:00'),'taken','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:00:00'),'taken','portal'),
(2,2,CONCAT(CURDATE() - INTERVAL 2 DAY, ' 19:00:00'),'skipped','portal'),
(1,2,CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:00:00'),'taken','portal'),
(2,2,CONCAT(CURDATE() - INTERVAL 1 DAY, ' 19:00:00'),'taken','portal');
INSERT IGNORE INTO `health_readings` (`user_id`,`recorded_on`,`systolic`,`diastolic`,`weight_kg`,`glucose`,`mood`,`energy`) VALUES
(2,CURDATE() - INTERVAL 9 DAY,132,86,78.4,6.1,3,3),
(2,CURDATE() - INTERVAL 7 DAY,128,84,78.1,5.8,4,3),
(2,CURDATE() - INTERVAL 5 DAY,126,82,77.9,NULL,4,4),
(2,CURDATE() - INTERVAL 3 DAY,124,81,77.6,5.6,4,4),
(2,CURDATE() - INTERVAL 1 DAY,121,80,77.4,5.5,5,4);
INSERT INTO `side_effects` (`user_id`,`medication_id`,`symptom`,`severity`,`started_on`,`details`,`status`,`staff_reply`,`reviewed_by`,`reviewed_at`,`created_at`) VALUES
(2,1,'Mild nausea in the morning','mild',CURDATE() - INTERVAL 8 DAY,'Feels queasy for about an hour after the tablet.','reviewed','Thanks for telling us. Try taking it with a full breakfast rather than just tea — that usually settles it. Let us know if it carries on past a week.',1,NOW() - INTERVAL 7 DAY,NOW() - INTERVAL 8 DAY);
