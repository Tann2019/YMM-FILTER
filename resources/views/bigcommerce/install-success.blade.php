<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Installed Successfully</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #10B981;
            border-radius: 50%;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .success-icon::before {
            content: '✓';
            color: white;
            font-size: 40px;
            font-weight: bold;
        }

        h1 {
            color: #1F2937;
            margin: 0 0 16px;
            font-size: 28px;
            font-weight: 700;
        }

        .subtitle {
            color: #6B7280;
            margin: 0 0 32px;
            font-size: 16px;
        }

        .store-info {
            background: #F3F4F6;
            border-radius: 8px;
            padding: 16px;
            margin: 24px 0;
        }

        .store-hash {
            font-family: 'Monaco', 'Menlo', monospace;
            color: #4F46E5;
            font-weight: 600;
        }

        .next-steps {
            text-align: left;
            margin: 32px 0;
        }

        .next-steps h3 {
            color: #1F2937;
            margin: 0 0 16px;
            font-size: 18px;
        }

        .step {
            display: flex;
            align-items: flex-start;
            margin: 12px 0;
            padding: 12px;
            background: #F9FAFB;
            border-radius: 8px;
            border-left: 4px solid #10B981;
        }

        .step-number {
            background: #10B981;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .step-text {
            color: #374151;
            line-height: 1.5;
        }

        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #E5E7EB;
            color: #6B7280;
            font-size: 14px;
        }

        .brand {
            color: #4F46E5;
            font-weight: 600;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-icon"></div>

        <h1>{{ $message }}</h1>
        <p class="subtitle">Your YMM Filter app is now ready to use in your BigCommerce store.</p>

        <div class="store-info">
            <strong>Store Hash:</strong> <span class="store-hash">{{ $store_hash }}</span>
        </div>

        <div class="next-steps">
            <h3>Next Steps:</h3>
            @foreach ($next_steps as $index => $step)
                <div class="step">
                    <div class="step-number">{{ $index + 1 }}</div>
                    <div class="step-text">{{ $step }}</div>
                </div>
            @endforeach
        </div>

        <div class="footer">
            <p>Powered by <span class="brand">YMM Filter</span> • Automotive Compatibility Made Easy</p>
        </div>
    </div>
</body>

</html>
