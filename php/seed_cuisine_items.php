<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

function seed_items_for_restaurant($conn, $restaurant_id, $cuisine, $count = 20) {
    $cuisine = strtolower(trim($cuisine));

    $catalog = [
        'japanese' => [
            ['name' => 'California Roll', 'category' => 'non-veg', 'price' => 320, 'desc' => 'Crab, avocado, cucumber rolled sushi'],
            ['name' => 'Veg California Roll', 'category' => 'veg', 'price' => 300, 'desc' => 'Avocado, cucumber, carrot rolled sushi'],
            ['name' => 'Salmon Nigiri', 'category' => 'non-veg', 'price' => 360, 'desc' => 'Fresh salmon over sushi rice'],
            ['name' => 'Tuna Nigiri', 'category' => 'non-veg', 'price' => 370, 'desc' => 'Tuna slice over vinegar rice'],
            ['name' => 'Tempura Udon', 'category' => 'non-veg', 'price' => 420, 'desc' => 'Udon noodle soup with prawn tempura'],
            ['name' => 'Vegetable Tempura', 'category' => 'veg', 'price' => 350, 'desc' => 'Crispy fried seasonal vegetables'],
            ['name' => 'Chicken Teriyaki', 'category' => 'non-veg', 'price' => 440, 'desc' => 'Grilled chicken with teriyaki glaze'],
            ['name' => 'Tofu Teriyaki', 'category' => 'veg', 'price' => 380, 'desc' => 'Pan-fried tofu with teriyaki sauce'],
            ['name' => 'Spicy Tuna Roll', 'category' => 'non-veg', 'price' => 340, 'desc' => 'Tuna with spicy mayo roll'],
            ['name' => 'Avocado Maki', 'category' => 'veg', 'price' => 280, 'desc' => 'Avocado sushi roll'],
            ['name' => 'Miso Soup', 'category' => 'veg', 'price' => 120, 'desc' => 'Classic Japanese soybean soup'],
            ['name' => 'Edamame', 'category' => 'veg', 'price' => 180, 'desc' => 'Steamed young soybeans with sea salt'],
            ['name' => 'Chicken Katsu Curry', 'category' => 'non-veg', 'price' => 460, 'desc' => 'Crispy chicken cutlet with curry and rice'],
            ['name' => 'Veg Katsu Curry', 'category' => 'veg', 'price' => 420, 'desc' => 'Crispy veg cutlet with curry and rice'],
            ['name' => 'Ramen Shoyu', 'category' => 'non-veg', 'price' => 430, 'desc' => 'Soy sauce broth ramen with chicken'],
            ['name' => 'Veg Ramen', 'category' => 'veg', 'price' => 410, 'desc' => 'Vegetable broth ramen with tofu'],
            ['name' => 'Gyoza (Chicken)', 'category' => 'non-veg', 'price' => 260, 'desc' => 'Pan-fried chicken dumplings'],
            ['name' => 'Gyoza (Veg)', 'category' => 'veg', 'price' => 240, 'desc' => 'Pan-fried vegetable dumplings'],
            ['name' => 'Matcha Cheesecake', 'category' => 'veg', 'price' => 250, 'desc' => 'Green tea cheesecake slice'],
            ['name' => 'Mochi Ice Cream', 'category' => 'veg', 'price' => 230, 'desc' => 'Chewy mochi with ice cream filling'],
        ],
        'mexican' => [
            ['name' => 'Chicken Tacos', 'category' => 'non-veg', 'price' => 280, 'desc' => 'Soft tortillas with spiced chicken'],
            ['name' => 'Veg Tacos', 'category' => 'veg', 'price' => 260, 'desc' => 'Soft tortillas with sautéed veggies'],
            ['name' => 'Beef Burrito', 'category' => 'non-veg', 'price' => 340, 'desc' => 'Flour tortilla with beef, beans, rice'],
            ['name' => 'Bean & Cheese Burrito', 'category' => 'veg', 'price' => 300, 'desc' => 'Flour tortilla with beans and cheese'],
            ['name' => 'Chicken Quesadilla', 'category' => 'non-veg', 'price' => 320, 'desc' => 'Griddled tortilla with chicken & cheese'],
            ['name' => 'Cheese Quesadilla', 'category' => 'veg', 'price' => 280, 'desc' => 'Griddled tortilla with cheese'],
            ['name' => 'Nachos Supreme', 'category' => 'veg', 'price' => 290, 'desc' => 'Corn chips with cheese, beans, salsa'],
            ['name' => 'Loaded Nachos (Chicken)', 'category' => 'non-veg', 'price' => 320, 'desc' => 'Corn chips with chicken & toppings'],
            ['name' => 'Chicken Enchiladas', 'category' => 'non-veg', 'price' => 360, 'desc' => 'Rolled tortillas in chili sauce'],
            ['name' => 'Veg Enchiladas', 'category' => 'veg', 'price' => 330, 'desc' => 'Rolled tortillas with veg filling'],
            ['name' => 'Taco Salad', 'category' => 'veg', 'price' => 260, 'desc' => 'Fresh salad with tortilla crunch'],
            ['name' => 'Chipotle Chicken Bowl', 'category' => 'non-veg', 'price' => 350, 'desc' => 'Rice bowl with chipotle chicken'],
            ['name' => 'Black Bean Bowl', 'category' => 'veg', 'price' => 300, 'desc' => 'Rice bowl with black beans & salsa'],
            ['name' => 'Fajitas (Chicken)', 'category' => 'non-veg', 'price' => 380, 'desc' => 'Sizzling chicken with peppers & onions'],
            ['name' => 'Fajitas (Veg)', 'category' => 'veg', 'price' => 340, 'desc' => 'Sizzling veggies with peppers & onions'],
            ['name' => 'Churros', 'category' => 'veg', 'price' => 180, 'desc' => 'Cinnamon sugar fried dough sticks'],
            ['name' => 'Guacamole & Chips', 'category' => 'veg', 'price' => 220, 'desc' => 'Fresh avocado dip with corn chips'],
            ['name' => 'Salsa Trio', 'category' => 'veg', 'price' => 170, 'desc' => 'Assorted salsas with chips'],
            ['name' => 'Tostadas (Chicken)', 'category' => 'non-veg', 'price' => 300, 'desc' => 'Crispy tortilla topped with chicken'],
            ['name' => 'Tostadas (Veg)', 'category' => 'veg', 'price' => 280, 'desc' => 'Crispy tortilla topped with veggies'],
        ],
        'thai' => [
            ['name' => 'Pad Thai (Chicken)', 'category' => 'non-veg', 'price' => 360, 'desc' => 'Stir-fried rice noodles with chicken'],
            ['name' => 'Pad Thai (Veg)', 'category' => 'veg', 'price' => 340, 'desc' => 'Stir-fried rice noodles with veggies'],
            ['name' => 'Green Curry (Chicken)', 'category' => 'non-veg', 'price' => 380, 'desc' => 'Coconut milk curry with basil'],
            ['name' => 'Green Curry (Veg)', 'category' => 'veg', 'price' => 360, 'desc' => 'Coconut milk curry with vegetables'],
            ['name' => 'Red Curry (Chicken)', 'category' => 'non-veg', 'price' => 380, 'desc' => 'Spicy red curry with chicken'],
            ['name' => 'Red Curry (Veg)', 'category' => 'veg', 'price' => 360, 'desc' => 'Spicy red curry with vegetables'],
            ['name' => 'Tom Yum Soup', 'category' => 'non-veg', 'price' => 240, 'desc' => 'Hot & sour Thai soup'],
            ['name' => 'Tom Kha Soup', 'category' => 'non-veg', 'price' => 250, 'desc' => 'Coconut milk soup with chicken'],
            ['name' => 'Som Tam (Papaya Salad)', 'category' => 'veg', 'price' => 220, 'desc' => 'Green papaya salad with lime & chili'],
            ['name' => 'Pad See Ew', 'category' => 'non-veg', 'price' => 340, 'desc' => 'Stir-fried flat noodles with chicken'],
            ['name' => 'Basil Fried Rice (Chicken)', 'category' => 'non-veg', 'price' => 330, 'desc' => 'Thai fried rice with basil & chili'],
            ['name' => 'Basil Fried Rice (Veg)', 'category' => 'veg', 'price' => 310, 'desc' => 'Veg fried rice with basil & chili'],
            ['name' => 'Spring Rolls (Veg)', 'category' => 'veg', 'price' => 180, 'desc' => 'Crispy veg spring rolls'],
            ['name' => 'Chicken Satay', 'category' => 'non-veg', 'price' => 260, 'desc' => 'Grilled skewers with peanut sauce'],
            ['name' => 'Mango Sticky Rice', 'category' => 'veg', 'price' => 240, 'desc' => 'Sweet sticky rice with mango'],
            ['name' => 'Pineapple Fried Rice', 'category' => 'veg', 'price' => 320, 'desc' => 'Thai fried rice with pineapple'],
            ['name' => 'Massaman Curry (Chicken)', 'category' => 'non-veg', 'price' => 390, 'desc' => 'Rich curry with peanuts & potato'],
            ['name' => 'Massaman Curry (Veg)', 'category' => 'veg', 'price' => 370, 'desc' => 'Rich veg curry with peanuts & potato'],
            ['name' => 'Thai Iced Tea', 'category' => 'veg', 'price' => 160, 'desc' => 'Sweet creamy black tea'],
            ['name' => 'Coconut Ice Cream', 'category' => 'veg', 'price' => 180, 'desc' => 'Creamy coconut ice cream'],
        ],
    ];

    if (!isset($catalog[$cuisine])) {
        return [0, 'Unsupported cuisine'];
    }

    $items = $catalog[$cuisine];

    // Ensure count items by cycling the catalog
    $to_insert = [];
    for ($i = 0; $i < $count; $i++) {
        $src = $items[$i % count($items)];
        // Add variant suffix every loop over base catalog to keep names unique
        $suffix = $i >= count($items) ? ' ' . (int)floor($i / count($items) + 1) : '';
        $to_insert[] = [
            'name' => $src['name'] . $suffix,
            'category' => $src['category'],
            'price' => $src['price'],
            'discount' => 0,
            'desc' => $src['desc'],
            'image' => null,
            'is_available' => 1,
        ];
    }

    $stmt = $conn->prepare("INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = 0; $err = 0;
    foreach ($to_insert as $it) {
        $stmt->bind_param('issddssi', $restaurant_id, $it['name'], $it['category'], $it['price'], $it['discount'], $it['desc'], $it['image'], $it['is_available']);
        if ($stmt->execute()) { $ok++; } else { $err++; }
    }
    $stmt->close();
    return [$ok, $err];
}

header('Content-Type: text/plain');

$map = [
    // id => cuisine
    11 => 'mexican',
    12 => 'japanese',
    13 => 'thai',
];

$target_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;
$cuisine = isset($_GET['cuisine']) ? strtolower(trim($_GET['cuisine'])) : '';
$count = isset($_GET['count']) ? max(1, intval($_GET['count'])) : 20;

if ($target_id > 0 && $cuisine !== '') {
    [$ok, $err] = seed_items_for_restaurant($conn, $target_id, $cuisine, $count);
    echo "Seeded $ok items (errors: $err) for restaurant #$target_id [$cuisine]"; 
    exit;
}

// Default: seed the three restaurants above
$total_ok = 0; $total_err = 0;
foreach ($map as $rid => $cui) {
    [$ok, $err] = seed_items_for_restaurant($conn, $rid, $cui, $count);
    $total_ok += $ok; $total_err += $err;
    echo "#{$rid} {$cui}: OK=$ok ERR=$err\n";
}

echo "\nTotal inserted: $total_ok | Errors: $total_err\n";
