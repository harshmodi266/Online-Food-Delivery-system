<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Bill</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 700px; margin: auto; padding: 20px; border: 1px solid #ccc; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; }
        .print-btn { margin-top: 20px; display: block; text-align: center; }
    </style>
</head>
<body>

<div class="container">
    <h2>LAZEEZ RESTAURANT</h2>
    <p><i>..GOOD FOOD GOOD MOOD..</i></p>
    <p>34 Vishal Chambers, Behind National Plaza, Alkapuri, Vadodara, Gujarat, India,pin-code 390007 <br> Phone:+91 95106 88453</p>

    <form method="POST">
        <label>Customer Name: <input type="text" name="customer_name" required></label><br>
        <label>No. of Persons: <input type="number" name="num_persons" min="1" required></label><br><br>

        <h3>Order Details</h3>
        <table>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Amount</th>
            </tr>
            <?php
            $menu = [
                "Foodman Burger" => ["desc" => "Medium Rare/Onions", "price" => 15.00],
                "Ginger Ale" => ["desc" => "Fresh", "price" => 1.50],
                "Diet 7Up" => ["desc" => "1 cold and 1 normal", "price" => 1.40],
                "Cappuccino" => ["desc" => "Fresh", "price" => 2.00],
                "Choc Rasp. Truffles" => ["desc" => "Fresh", "price" => 5.00],
                "Grilled Chicken" => ["desc" => "Fried", "price" => 7.95],
                "Beefsteak Tomatoes" => ["desc" => "Fresh", "price" => 8.00],
                "Bacon" => ["desc" => "Fresh", "price" => 6.00],
                "French Bread" => ["desc" => "Fresh", "price" => 4.00]
            ];

            foreach ($menu as $item => $details) {
                echo "<tr>
                        <td>$item</td>
                        <td>{$details['desc']}</td>
                        <td><input type='number' name='quantity[$item]' min='0' value='0'></td>
                        <td>₹{$details['price']}</td>
                        <td></td>
                      </tr>";
            }
            ?>
        </table>
        <label>Tax Rate (%): <input type="number" name="tax_rate" value="2" required></label><br>
        <label>Other Charges: <input type="number" name="other_charges" value="50" required></label><br><br>
        <input type="submit" name="generate_bill" value="Generate Bill">
    </form>

    <?php
    if (isset($_POST['generate_bill'])) {
        echo "<h3>Invoice</h3>";
        echo "<p><b>Customer:</b> {$_POST['customer_name']}</p>";
        echo "<p><b>No. of Persons:</b> {$_POST['num_persons']}</p>";
        echo "<p><b>Date:</b> " . date("F j, Y") . " | <b>Time:</b> " . date("g:i A") . "</p>";

        echo "<table>";
        echo "<tr><th>Item</th><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr>";

        $total = 0;
        foreach ($_POST['quantity'] as $item => $qty) {
            if ($qty > 0) {
                $rate = $menu[$item]['price'];
                $desc = $menu[$item]['desc'];
                $subtotal = $rate * $qty;
                $total += $subtotal;
                echo "<tr>
                        <td>$item</td>
                        <td>$desc</td>
                        <td>$qty</td>
                        <td>₹$rate</td>
                        <td>₹$subtotal</td>
                      </tr>";
            }
        }

        $tax = ($total * $_POST['tax_rate']) / 100;
        $other_charges = $_POST['other_charges'];
        $grand_total = $total + $tax + $other_charges;

        echo "<tr class='total-row'><td colspan='4'>Subtotal</td><td>₹$total</td></tr>";
        echo "<tr class='total-row'><td colspan='4'>Sales Tax ({$_POST['tax_rate']}%)</td><td>₹$tax</td></tr>";
        echo "<tr class='total-row'><td colspan='4'>Other Charges</td><td>₹$other_charges</td></tr>";
        echo "<tr class='total-row'><td colspan='4'>TOTAL</td><td>₹$grand_total</td></tr>";
        echo "</table>";

        echo "<button onclick='window.print()' class='print-btn'>Print Bill</button>";
    }
    ?>
</div>

</body>
</html>