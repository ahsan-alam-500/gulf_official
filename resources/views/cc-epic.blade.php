<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epic Cache Clear</title>
    <style>
        body {
            background: black;
            color: #0f0;
            font-family: "Courier New", monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            transition: all 0.5s ease;
        }

        #output {
            white-space: pre;
            font-size: 16px;
            line-height: 1.3;
        }

        .flash {
            animation: flash 1s infinite;
        }

        @keyframes flash {

            0%,
            50%,
            100% {
                opacity: 1;
            }

            25%,
            75% {
                opacity: 0;
            }
        }

        /* New theme after 3s */
        .theme2 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }
    </style>
</head>

<body>
    <pre id="output"></pre>

    <script>
        const lines = [
            `╔════════════════════════════════╗`,
            `║   ⚡ Laravel Epic Cache Clear ⚡  ║`,
            `╠════════════════════════════════╣`,
            `║  Config: Cleared               ║`,
            `║  Route: Cleared                ║`,
            `║  Cache: Cleared                ║`,
            `║  Views: Cleared                ║`,
            `╠════════════════════════════════╣`,
            `║   ✅ All caches cleared!       ║`,
            `╚════════════════════════════════╝`,
            ``,
            `🚀 SYSTEM HACK INITIATED...`
        ];

        const output = document.getElementById('output');
        let i = 0;

        function printLine() {
            if (i < lines.length) {
                const span = document.createElement('span');
                span.textContent = lines[i] + '\n';
                span.classList.add('flash');
                output.appendChild(span);
                i++;
                setTimeout(printLine, 300); // line by line animation
            }
        }

        printLine();

        // Change theme after 3 seconds
        setTimeout(() => {
            document.body.classList.add('theme2');
        }, 3000);
    </script>
</body>

</html>
