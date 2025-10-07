<!DOCTYPE html>
<html>
<head>
    <title>Installation Guide - Custom Pricing</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6">Setup Instructions</h1>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <p class="font-semibold">One-time setup required</p>
            <p class="text-sm text-gray-700">Add the following code to your theme to enable custom pricing display</p>
        </div>

        <h2 class="text-xl font-semibold mb-3">Step 1: Edit your theme</h2>
        <ol class="list-decimal list-inside space-y-2 mb-6">
            <li>Go to <strong>Online Store → Themes</strong></li>
            <li>Click <strong>Actions → Edit code</strong></li>
            <li>Open <strong>layout/theme.liquid</strong></li>
            <li>Find the <code class="bg-gray-100 px-2 py-1 rounded">&lt;/head&gt;</code> tag</li>
            <li>Paste this code <strong>before</strong> the <code class="bg-gray-100 px-2 py-1 rounded">&lt;/head&gt;</code> tag:</li>
        </ol>

        <div class="bg-gray-900 text-white p-4 rounded-lg mb-6 overflow-x-auto">
            <pre><code>{% raw %}{% if customer %}
  <meta name="customer-id" content="{{ customer.id }}">
  <script src="https://{{ shop.permanent_domain }}/apps/custom-pricing/script.js" defer></script>
{% endif %}{% endraw %}</code></pre>
        </div>

        <button onclick="copyCode()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Copy Code
        </button>

        <h2 class="text-xl font-semibold mt-8 mb-3">Step 2: Save and test</h2>
        <p>Click <strong>Save</strong> and visit your store as a logged-in customer to see custom prices!</p>
    </div>

    <script>
        function copyCode() {
            const code = `{% raw %}{% if customer %}
  <meta name="customer-id" content="{{ customer.id }}">
  <script src="https://{{ shop.permanent_domain }}/apps/custom-pricing/script.js" defer></script>
{% endif %}{% endraw %}`;
            
            navigator.clipboard.writeText(code);
            alert('Code copied to clipboard!');
        }
    </script>
</body>
</html>