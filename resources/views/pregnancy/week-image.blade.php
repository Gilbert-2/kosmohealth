<!DOCTYPE html>
<html>
<head>
    <title>Pregnancy Week {{ $week }}</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            width: 400px;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            font-weight: 300;
        }
        p {
            font-size: 1.2em;
            margin: 5px 0;
            opacity: 0.9;
        }
        .baby-size {
            font-size: 1.5em;
            font-weight: bold;
            margin: 15px 0;
            color: #ffd700;
        }
        .week-number {
            font-size: 3em;
            font-weight: bold;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="week-number">{{ $week }}</div>
        <h1>Week {{ $week }}</h1>
        <p>Pregnancy Development</p>
        <div class="baby-size">
            @php
                $babySizes = [
                    1 => 'Poppy Seed', 2 => 'Sesame Seed', 3 => 'Poppy Seed', 4 => 'Poppy Seed',
                    5 => 'Sesame Seed', 6 => 'Lentil', 7 => 'Blueberry', 8 => 'Kidney Bean',
                    9 => 'Grape', 10 => 'Kumquat', 11 => 'Fig', 12 => 'Lime',
                    13 => 'Pea Pod', 14 => 'Lemon', 15 => 'Apple', 16 => 'Avocado',
                    17 => 'Pear', 18 => 'Bell Pepper', 19 => 'Mango', 20 => 'Banana',
                    21 => 'Carrot', 22 => 'Coconut', 23 => 'Grapefruit', 24 => 'Corn',
                    25 => 'Cauliflower', 26 => 'Lettuce', 27 => 'Broccoli', 28 => 'Eggplant',
                    29 => 'Butternut Squash', 30 => 'Cabbage', 31 => 'Coconut', 32 => 'Squash',
                    33 => 'Pineapple', 34 => 'Cantaloupe', 35 => 'Honeydew', 36 => 'Romaine Lettuce',
                    37 => 'Swiss Chard', 38 => 'Leek', 39 => 'Mini Watermelon', 40 => 'Pumpkin'
                ];
                $babySize = $babySizes[$week] ?? 'Growing Baby';
            @endphp
            {{ $babySize }}
        </div>
        <p>Baby is growing beautifully!</p>
    </div>
</body>
</html> 