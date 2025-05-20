<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Lazeez Restaurant</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        /* Fallback styles in case style.css fails to load */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding-top: 80px;
            background-color: #F8E9D2;
            color: #2E2E2E;
        }

        /* Typing Animation */
        .typing-effect {
            display: inline-block;
            overflow: hidden;
            white-space: nowrap;
            border-right: 3px solid #E67E22;
            animation: typing 4s steps(var(--char-count, 50), end) forwards, blink 0.75s step-end infinite;
        }

        @keyframes typing {
            from {
                width: 0;
            }
            to {
                width: 100%;
            }
        }

        @keyframes blink {
            50% {
                border-color: transparent;
            }
        }

        /* Our Story */
        .our-story {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 60px;
            justify-content: space-between;
            margin-left: 10%;
        }

        .our-story .content {
            width: 50%;
            max-width: 600px;
        }

        .our-story h2.typing-effect {
            font-size: 2.2rem;
            color: #E67E22;
        }

        .our-story h2.typing-effect::after {
            content: none !important;
        }

        .our-story p {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #2E2E2E;
        }

        .our-story img {
            width: 30%;
            height: 450px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.5s ease-in-out;
            margin-left: 20%;
        }

        /* Our Mission */
        .our-mission {
            display: flex;
            align-items: center;
            gap: 30px;
            padding: 60px;
        }

        .our-mission .content {
            width: 50%;
            max-width: 600px;
        }

        .our-mission h2.typing-effect {
            font-size: 2.2rem;
            color: #E67E22;
        }

        .our-mission h2.typing-effect::after {
            content: none !important;
        }

        .our-mission p {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #2E2E2E;
        }

        .our-mission img {
            width: 45%;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.5s ease-in-out;
        }

        /* Why Choose Us */
        .why-choose-us {
            text-align: center;
            padding: 60px;
            background: #0A1F44;
            color: white;
        }

        .why-choose-us h2 {
            font-size: 2.2rem;
            margin-bottom: 30px;
        }

        .why-choose-us h2.typing-effect::after {
            content: none !important;
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .feature {
            width: 220px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
        }

        .feature img {
            width: 120px;
            height: 120px;
            margin-bottom: 15px;
        }

        .feature h3 {
            font-size: 1.6rem;
            color: #E67E22;
        }

        .feature p {
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .our-story, .our-mission {
                flex-direction: column;
                text-align: center;
            }

            .our-story img, .our-mission img {
                width: 80%;
                margin-left: 0;
            }

            .our-story .content, .our-mission .content {
                width: 90%;
            }

            .our-story {
                margin-left: 0;
            }

            .features {
                flex-direction: column;
                align-items: center;
            }

            .feature img {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>

<!-- Hero Section -->
<section class="about-hero">
    <div class="overlay"></div>
    <div class="hero-content">
        <h1 class="animated-title">Welcome to <span>Lazeez Restaurant</span></h1>
        <p>Where every meal is a masterpiece!</p>
        
    </div>
</section>

<!-- Our Story -->
<section class="our-story">
    <div class="content animate-slide-up">
        <h2 class="typing-effect">Our Story</h2>
        <p>Established in 1990, Lazeez Restaurant has been serving delightful cuisines with authentic flavors. Our mission is to bring people together over great food and warm hospitality.</p>
    </div>
    <img src="images/chef-special.jpg" alt="Chef cooking">
</section>

<!-- Our Mission -->
<section class="our-mission">
    <img src="images/restaurant-table.jpg" alt="Restaurant ambiance">
    <div class="content">
        <h2 class="typing-effect1">Our Mission</h2>
        <p>To provide an extraordinary dining experience with high-quality ingredients, innovative dishes, and top-notch service. We strive to create moments of joy through food.</p>
    </div>
</section>

<!-- Why Choose Us -->
<section class="why-choose-us">
   <a href="video.php"> <h2>Why Choose Us?</h2></a>
    <div class="features">
        <div class="feature">
            <img src="images/fresh-food.jpg" loading="lazy" alt="Fresh Ingredients">
            <h3>Fresh Ingredients</h3>
            <p>We use only the freshest ingredients to bring out the best flavors.</p>
        </div>
        <div class="feature">
            <img src="images/chef-special.jpg" alt="Expert chefs" loading="lazy" />
            <h3>Expert Chefs</h3>
            <p>Our chefs craft each dish with passion and precision.</p>
        </div>
        <div class="feature">
            <img src="images/restaurant-service.jpg" loading="lazy" alt="Excellent Service">
            <h3>Excellent Service</h3>
            <p>Our team is dedicated to making your visit unforgettable.</p>
        </div>
    </div>
</section>

<!-- Footer -->
<?php include 'footer.php'; ?>

</body>
</html>