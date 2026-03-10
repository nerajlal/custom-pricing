<!DOCTYPE html>
<html>
<head>
    <title>Installing App...</title>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
</head>
<body>
    <div style="text-align: center; padding: 50px;">
        <h2>Installing Custom Pricing Manager...</h2>
        <p>Please wait...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var host = "{{ $host ?? '' }}";
            var apiKey = "{{ $apiKey }}";
            var redirectUrl = "{{ $redirectUrl ?? '' }}";
            
            if (redirectUrl) {
                // Perform top-level redirect for OAuth
                window.top.location.href = redirectUrl;
                return;
            }

            if (host && apiKey) {
                // Use Shopify App Bridge to get shop
                var AppBridge = window['app-bridge'];
                var createApp = AppBridge.default;
                
                var app = createApp({
                    apiKey: apiKey,
                    host: host,
                });
                
                // Get shop from config
                var shopOrigin = app.hostOrigin;
                var shop = shopOrigin.replace('https://', '').replace('/admin', '');
                
                // Redirect with shop parameter
                window.location.href = '/install?shop=' + shop;
            } else {
                document.body.innerHTML = '<div style="text-align:center;padding:50px;"><h2>Error</h2><p>Please install this app from your Shopify admin Apps page.</p></div>';
            }
        });
    </script>
</body>
</html>
