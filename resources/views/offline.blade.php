

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>You Are Offline!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }

        html {
            box-sizing: border-box;
            font-size: 62.5%;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .wrapper h1 {
            font-size: 8rem;
            line-height: 9rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: #111215;
        }

        .wrapper h2 {
            font-size: 4rem;
            line-height: 5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #313235;
        }

        .wrapper h3 {
            font-size: 3rem;
            line-height: 3.5rem;
            font-weight: 400;
            margin-bottom: 2rem;
            color: #414245;
        }

        button {
            background-color: #cd2122;
            color: #ffffff;
            border-radius: 0.75rem;
            border: none;
            font-weight: 600;
            cursor: pointer;
            padding: 1rem 2rem;
            font-size: 2rem;
            line-height: 2.5rem;
        }

        button:hover {
            background-color: #951515;
        }
    </style>
</head>

<body>
    <!-- Button causing page reload -->
    <div class="wrapper">
        <h1>OOOPS!</h1>
        <h2>It looks like you lost connection.</h2>
        <h3>Please check your network and try again!</h3>

        <button type="button" onClick="window.history.back();">
            Refresh Page
        </button>
    </div>

    <script>
        window.addEventListener("online", (event) => {
            history.back();
        });
        document.addEventListener("DOMContentLoaded", function () {
            if(navigator.onLine) {
                history.back();
            }
        });
    </script>
</body>

</html>
