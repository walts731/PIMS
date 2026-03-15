<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waving Ocean</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        body {
            overflow: auto;
            background: linear-gradient(to bottom, #87CEEB 0%, #98D8E8 50%, #B0E0E6 100%);
            height: 200vh;
            position: relative;
        .ocean-container {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 50vh;
            overflow: auto;
        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
            background: linear-gradient(to bottom, 
                rgba(0, 119, 190, 0.8) 0%,
                rgba(0, 89, 150, 0.9) 30%,
                rgba(0, 59, 110, 1) 100%);
            border-radius: 40% 60% 30% 70% / 60% 30% 70% 40%;
            animation: wave 8s ease-in-out infinite;
        .wave:nth-child(2) {
            background: linear-gradient(to bottom, 
                rgba(0, 150, 200, 0.7) 0%,
                rgba(0, 120, 170, 0.8) 30%,
                rgba(0, 90, 140, 0.9) 100%);
            animation: wave 6s ease-in-out infinite;
            animation-delay: -2s;
            opacity: 0.8;
            height: 90%;
        .wave:nth-child(3) {
            background: linear-gradient(to bottom, 
                rgba(0, 180, 220, 0.6) 0%,
                rgba(0, 150, 190, 0.7) 30%,
                rgba(0, 120, 160, 0.8) 100%);
            animation: wave 4s ease-in-out infinite;
            animation-delay: -4s;
            opacity: 0.6;
            height: 80%;
        .wave:nth-child(4) {
            background: linear-gradient(to bottom, 
                rgba(0, 200, 240, 0.5) 0%,
                rgba(0, 170, 210, 0.6) 30%,
                rgba(0, 140, 180, 0.7) 100%);
            animation: wave 3s ease-in-out infinite;
            animation-delay: -1s;
            opacity: 0.4;
            height: 70%;
        @keyframes wave {
            0% {
                transform: translateX(0) translateY(0) rotate(0deg);
                border-radius: 40% 60% 30% 70% / 60% 30% 70% 40%;
            25% {
                transform: translateX(-25%) translateY(-20px) rotate(1deg);
                border-radius: 60% 40% 70% 30% / 40% 60% 30% 70%;
            50% {
                transform: translateX(-50%) translateY(0) rotate(0deg);
                border-radius: 30% 70% 40% 60% / 70% 40% 60% 30%;
            75% {
                transform: translateX(-25%) translateY(-15px) rotate(-1deg);
                border-radius: 50% 50% 60% 40% / 50% 60% 40% 50%;
            100% {
                transform: translateX(0) translateY(0) rotate(0deg);
                border-radius: 40% 60% 30% 70% / 60% 30% 70% 40%;
        .foam {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 20px;
            background: linear-gradient(90deg, 
                transparent 0%,
                rgba(255, 255, 255, 0.3) 20%,
                rgba(255, 255, 255, 0.6) 50%,
                rgba(255, 255, 255, 0.3) 80%,
                transparent 100%);
            animation: foam 3s linear infinite;
        @keyframes foam {
            0% {
                transform: translateX(-100%);
            100% {
                transform: translateX(100%);
        .sun {
            position: absolute;
            top: 10%;
            right: 15%;
            width: 80px;
            height: 80px;
            background: radial-gradient(circle, #FFD700, #FFA500);
            border-radius: 50%;
            box-shadow: 0 0 50px rgba(255, 215, 0, 0.8);
            animation: sun-glow 4s ease-in-out infinite alternate;
        @keyframes sun-glow {
            0% {
                box-shadow: 0 0 50px rgba(255, 215, 0, 0.8);
            100% {
                box-shadow: 0 0 80px rgba(255, 215, 0, 1);
        .cloud {
            position: absolute;
            background: white;
            border-radius: 100px;
            opacity: 0.7;
        .cloud::before,
        .cloud::after {
            content: '';
            position: absolute;
            background: white;
            border-radius: 100px;
        .cloud1 {
            width: 100px;
            height: 40px;
            top: 20%;
            left: 20%;
            animation: float 20s infinite;
        .cloud1::before {
            width: 50px;
            height: 50px;
            top: -25px;
            left: 10px;
        .cloud1::after {
            width: 60px;
            height: 40px;
            top: -15px;
            right: 10px;
        .cloud2 {
            width: 80px;
            height: 35px;
            top: 15%;
            left: 60%;
            animation: float 25s infinite;
            animation-delay: -5s;
        .cloud2::before {
            width: 40px;
            height: 40px;
            top: -20px;
            left: 15px;
        .cloud2::after {
            width: 50px;
            height: 35px;
            top: -10px;
            right: 15px;
        @keyframes float {
            0%, 100% {
                transform: translateX(0) translateY(0);
            25% {
                transform: translateX(20px) translateY(-10px);
            50% {
                transform: translateX(-10px) translateY(5px);
            75% {
                transform: translateX(15px) translateY(-5px);
        .boat {
            position: absolute;
            bottom: 45vh;
            left: 15%;
            transform: translateX(-50%);
            width: 120px;
            height: 80px;
            animation: boat-float 4s ease-in-out infinite;
            z-index: 10;
        .boat-hull {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 30px;
            background: linear-gradient(to bottom, #8B4513, #654321);
            border-radius: 0 0 50% 50% / 0 0 100% 100%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        .boat-sail {
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 40px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 50px solid #FFFFFF;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
        .boat-mast {
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            width: 3px;
            height: 55px;
            background: #654321;
            z-index: -1;
        .boat-flag {
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-left: 15px solid #FF0000;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            animation: flag-wave 2s ease-in-out infinite;
        @keyframes boat-float {
            0%, 100% {
                transform: translateY(0) rotate(-2deg);
            25% {
                transform: translateY(-8px) rotate(1deg);
            50% {
                transform: translateY(0) rotate(2deg);
            75% {
                transform: translateY(-5px) rotate(-1deg);
        @keyframes flag-wave {
            0%, 100% {
                transform: rotateY(0deg);
            50% {
                transform: rotateY(20deg);
    </style>
</head>
<body>
    <div class="sun"></div>
    <div class="cloud cloud1"></div>
    <div class="cloud cloud2"></div>
    
    <div class="boat">
        <div class="boat-hull"></div>
        <div class="boat-mast"></div>
        <div class="boat-sail"></div>
    
    <div class="ocean-container">
    
    <div class="ocean-container">
    
    <div class="ocean-container">
    
    <div class="ocean-container">
    
    <div class="ocean-container">
    
    <div class="ocean-container">
    
    <div class="ocean-container">
    
    <div class="ocean-container">
    
    <div class="ocean-container">
            
            // Check ocean container
                    exists: true,
            
                    exists: true,
                    position: boatStyles.position,
                    bottom: boatStyles.bottom,
                    left: boatStyles.left,
                    width: boatStyles.width,
                    height: boatStyles.height,
                    zIndex: boatStyles.zIndex,
            
                    position: waveStyles.position,
                    bottom: waveStyles.bottom,
                    height: waveStyles.height,
            
                height: bodyStyles.height,
                overflow: bodyStyles.overflow,
                position: bodyStyles.position
            
                height: window.innerHeight + "px",
        
        
            
            
        
        
        window.addEventListener(\"scroll\", function() {
            const scrolled = window.pageYOffset;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercentage = scrolled / maxScroll;
            
            // Move boat from left (15%) to right (85%) as user scrolls
            const currentBoatLeft = boatStartLeft + (boatEndLeft - boatStartLeft) * scrollPercentage;
            boat.style.left = currentBoatLeft + \"%\";
            
            // Move clouds from their current positions to the left as user scrolls
            const cloud1 = document.querySelector(\".cloud1\");
            const cloud2 = document.querySelector(\".cloud2\");
            
            const cloud1StartLeft = 20;
            const cloud1EndLeft = -30;
            const currentCloud1Left = cloud1StartLeft + (cloud1EndLeft - cloud1StartLeft) * scrollPercentage;
            cloud1.style.left = currentCloud1Left + \"%\";
            
            const cloud2StartLeft = 60;
            const cloud2EndLeft = 10;
            const currentCloud2Left = cloud2StartLeft + (cloud2EndLeft - cloud2StartLeft) * scrollPercentage;
            cloud2.style.left = currentCloud2Left + \"%\";
</body>
</html>
