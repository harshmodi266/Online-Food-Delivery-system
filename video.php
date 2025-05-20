<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reel Player</title>
    <style>
        /* Reset and Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
            overflow: hidden; /* Prevents scrolling */
        }

        body {
            background-color: black;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100vw;
        }

        /* Video Container */
        .video-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* Full-screen vertical video */
        video {
            height: 90vh;
            border-radius: 12px;
            box-shadow: 0px 4px 20px rgba(255, 255, 255, 0.2);
            object-fit: cover;
        }

        /* Controls (Optional, for buttons like Like, Share) */
        .controls {
            position: absolute;
            right: 20px;
            bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .control-btn {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .control-btn:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        .control-btn img {
            width: 30px;
            height: 30px;
        }
    </style>
</head>
<body>

    <div class="video-container">
        <video autoplay loop muted controls>
            <source src="video/video.mp4" type="video/mp4">
            <!-- <source src="video.webm" type="video/webm">
            <source src="video.ogg" type="video/ogg"> -->
            Your browser does not support the video tag.
        </video>

        <!-- Optional Controls (Like, Share Buttons) -->
        <div class="controls">
            <div class="control-btn">
                <!-- <img src="like.png" alt="Like"> -->
            </div>
            <div class="control-btn">
                <!-- <img src="share.png" alt="Share"> -->
            </div>
        </div>
    </div>

</body>
</html>
