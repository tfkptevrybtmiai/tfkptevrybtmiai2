<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="assets/bootstrap.css" rel="stylesheet" type="text/css" >
    <link href="calling.css" rel="stylesheet" type="text/css" >
    <script src="assets/jquery.js"></script>
</head>
<body>
    <main id="calling-form" name="callKeyboard">
        <div class="container-fluid">
            <div class="call_keyboard">
                <ul class="call_number_area">
                    <li class="call_minus">&nbsp;</li>
                    <li class="call_number"><input id="next-number" type="text" value="000"></input></li>
                    <li class="call_plus">&nbsp;</li>
                </ul>
                <button id="call" class="action-button call_button">叫號</button>
                <ul class="call_number_button">
                    <li data-number="1">1</li>
                    <li data-number="2">2</li>
                    <li data-number="3">3</li>
                    <li data-number="4">4</li>
                    <li data-number="5">5</li>
                    <li data-number="6">6</li>
                    <li data-number="7">7</li>
                    <li data-number="8">8</li>
                    <li data-number="9">9</li>
                    <li class="call_number_stop">C</li>
                    <li data-number="0">0</li>
                    <li class="call_number_delete">&nbsp;</li>
                </ul>
            </div>
        </div>
    </main>
    <script src="calling.js"></script>
</body>
</html>
