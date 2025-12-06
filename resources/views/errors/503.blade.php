<!DOCTYPE html>
<html lang="en" class="bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Unavailable - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white flex items-center justify-center font-sans text-gray-900 antialiased">
    <div class="max-w-md mx-auto p-8 text-center">
        @php
            // Gracefully handle database failures when loading logo
            $companyLogo = null;
            try {
                $companyLogo = \App\Models\Setting::get('company_logo');
            } catch (\Exception $e) {
                // Database is down, use default logo
                $companyLogo = null;
            }
        @endphp

        <!-- Logo Section -->
        <div class="mb-6">
            @if($companyLogo && \Illuminate\Support\Facades\Storage::exists($companyLogo))
                <img src="{{ \Illuminate\Support\Facades\Storage::url($companyLogo) }}"
                     alt="{{ config('app.name') }}"
                     class="h-16 mx-auto mb-4">
            @else
                <img src="{{ asset('logo.png') }}"
                     alt="{{ config('app.name') }}"
                     class="h-16 mx-auto mb-4">
            @endif
        </div>

        <!-- Icon and Message -->
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-100 rounded-full mb-4">
                <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Service Temporarily Unavailable</h1>
            <p class="text-gray-600 mb-6">
                We're performing scheduled maintenance or experiencing temporary technical difficulties. Our team is working to get things back up and running.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-4">
            <button onclick="window.location.reload()"
                    class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150"
                    style="background-color: #16a34a; hover:background-color: #15803d;">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Try Again
            </button>

            <a href="/admin"
               class="block px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150">
                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Return to Dashboard
            </a>

            <p class="text-sm text-gray-500 mt-4">
                Error Code: 503
            </p>
        </div>

        <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-xs text-gray-400">
                If this problem persists, please contact support.
            </p>
        </div>
    </div>
</body>
</html>