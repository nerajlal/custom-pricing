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
                try {
                    var decodedHost = atob(host);
                    var shop = "";
                    
                    if (decodedHost.includes('myshopify.com')) {
                        shop = decodedHost.split('/')[0];
                    } else if (decodedHost.includes('admin.shopify.com/store/')) {
                        shop = decodedHost.split('admin.shopify.com/store/')[1].split('/')[0] + '.myshopify.com';
                    }

                    if (shop) {
                        console.log("Redirecting to install with shop:", shop);
                        window.location.href = '/install?shop=' + shop + '&host=' + host;
                    } else {
                        throw new Error("Could not determine shop from host: " + decodedHost);
                    }
                } catch (e) {
                    console.error("Installation Error:", e);
                    document.body.innerHTML = '<div style="text-align:center;padding:50px;"><h2>Error</h2><p>' + e.message + '</p><p>Please try installing from Shopify Admin.</p></div>';
                }
            } else {
                document.body.innerHTML = '<div style="text-align:center;padding:50px;"><h2>Error</h2><p>Please install this app from your Shopify admin Apps page.</p></div>';
            }
        });
    </script>
</body>
</html>
