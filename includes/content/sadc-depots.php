<?php
/**
 * Well-known cross-border bus and taxi routes from South Africa, shown on
 * delivery.php and offered as suggestions in the basket's depot field.
 * Departure points are the big South African ranks and terminals; arrival
 * points are the main long-distance depots in each country. The exact
 * operator and drop-off are confirmed with the customer on WhatsApp.
 *
 * country => [flag emoji, [ [from (South Africa), to (depot)], ... ] ]
 */
return [
    'Zimbabwe' => ['🇿🇼', [
        ['Roodepoort, Johannesburg', 'Harare — Roadport terminal'],
        ['Johannesburg Park Station', 'Harare — Roadport terminal'],
        ['Johannesburg Park Station', 'Bulawayo — Renkini terminus'],
        ['Pretoria — Bosman Station', 'Bulawayo — Renkini terminus'],
        ['Johannesburg Park Station', 'Gweru, Masvingo and Mutare'],
        ['Polokwane / Musina', 'Beitbridge'],
    ]],
    'Mozambique' => ['🇲🇿', [
        ['Johannesburg Park Station', 'Maputo — Junta terminal'],
        ['Pretoria — Bosman Station', 'Maputo — Junta terminal'],
        ['Nelspruit (Mbombela)', 'Maputo'],
    ]],
    'Botswana' => ['🇧🇼', [
        ['Johannesburg Park Station', 'Gaborone — main bus rank'],
        ['Pretoria — Bosman Station', 'Gaborone — main bus rank'],
        ['Johannesburg', 'Francistown bus rank'],
    ]],
    'Lesotho' => ['🇱🇸', [
        ['Johannesburg — Park Station / Noord taxi rank', 'Maseru — bus and taxi rank'],
        ['Bloemfontein', 'Maseru — bus and taxi rank'],
    ]],
    'Eswatini' => ['🇸🇿', [
        ['Johannesburg Park Station', 'Mbabane bus rank'],
        ['Johannesburg — Park Station / Noord taxi rank', 'Manzini bus rank'],
        ['Durban', 'Manzini bus rank'],
    ]],
    'Namibia' => ['🇳🇦', [
        ['Johannesburg Park Station', 'Windhoek'],
        ['Cape Town', 'Windhoek'],
    ]],
    'Zambia' => ['🇿🇲', [
        ['Johannesburg Park Station', 'Lusaka — Intercity bus terminus'],
        ['Johannesburg Park Station', 'Livingstone'],
    ]],
    'Malawi' => ['🇲🇼', [
        ['Johannesburg Park Station', 'Lilongwe bus depot'],
        ['Johannesburg Park Station', 'Blantyre — Wenela bus depot'],
    ]],
];
