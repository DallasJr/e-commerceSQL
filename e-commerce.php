<?php
require 'vendor/autoload.php';

use Faker\Factory;

$host = 'localhost';
$db = 'e-commerce';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$faker = Factory::create();

for ($i = 0; $i < 10; $i++) {
    $name = $faker->name;
    $email = $faker->unique()->email;
    $password = password_hash('password', PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES ('$name', '$email', '$password')";
    $conn->query($sql);
    $user_id = $conn->insert_id;

    $streetAddress = $faker->streetAddress;
    $city = $faker->city;
    $postal_code = $faker->postcode;
    $country = $faker->country;

    $sql = "INSERT INTO address (user_id, street, city, postal_code, country) VALUES ('$user_id', '$streetAddress', '$city', '$postal_code', '$country')";
    $conn->query($sql);
}

$product_ids = [];
for ($i = 1; $i <= 20; $i++) {
    $productName = $faker->name;
    $price = $faker->randomFloat(2, 5, 100);
    $stock_quantity = $faker->numberBetween(10, 100);
    $description = $faker->sentence;
    $sql = "INSERT INTO product (name, description, price, stock_quantity) VALUES ('$productName', '$description', '$price', '$stock_quantity')";
    $conn->query($sql);
    $product_ids[$i] = $conn->insert_id;
}

for ($i = 1; $i <= 10; $i++) {
    $total_price = 0;
    $sql = "INSERT INTO cart (user_id, total_price) VALUES ('$i', '$total_price')";
    $conn->query($sql);
    $cart_id = $conn->insert_id;

    $numProducts = $faker->numberBetween(1, 5);
    $addedProducts = [];
    for ($j = 0; $j < $numProducts; $j++) {
        do {
            $product_id = $faker->randomElement($product_ids);
        } while (in_array($product_id, $addedProducts));
        $addedProducts[] = $product_id;

        $quantity = $faker->numberBetween(1, 3);
        $result = $conn->query("SELECT price FROM product WHERE product_id = $product_id");
        $product = $result->fetch_assoc();
        $price = $product['price'];
        $total_price += $price * $quantity;
        $sql = "INSERT INTO cart_product (cart_id, product_id, quantity) VALUES ('$cart_id', '$product_id', '$quantity')";
        $conn->query($sql);
    }

    $conn->query("UPDATE cart SET total_price = $total_price WHERE cart_id = $cart_id");
}

for ($i = 1; $i <= 10; $i++) {
    $status = $faker->randomElement(['pending', 'shipped', 'delivered']);
    $sql = "INSERT INTO command (user_id, status) VALUES ('$i', '$status')";
    $conn->query($sql);
    $command_id = $conn->insert_id;

    $result = $conn->query("SELECT cart_id FROM cart WHERE user_id = $i");
    $cart = $result->fetch_assoc();
    $cart_id = $cart['cart_id'];

    $result = $conn->query("SELECT * FROM cart_product WHERE cart_id = $cart_id");
    $total_amount = 0;
    while ($cartProduct = $result->fetch_assoc()) {
        $product_id = $cartProduct['product_id'];
        $quantity = $cartProduct['quantity'];

        $resultPrice = $conn->query("SELECT price FROM product WHERE product_id = $product_id");
        $product = $resultPrice->fetch_assoc();
        $price_at_purchase = $product['price'];
        $total_amount += $price_at_purchase * $quantity;

        $sql = "INSERT INTO command_product (command_id, product_id, quantity, price_at_purchase) VALUES ('$command_id', '$product_id', '$quantity', '$price_at_purchase')";
        $conn->query($sql);
    }

    $payment_status = $faker->randomElement(['paid', 'unpaid']);
    $sql = "INSERT INTO invoices (command_id, total_amount, payment_status) VALUES ('$command_id', '$total_amount', '$payment_status')";
    $conn->query($sql);
}

$conn->close();