<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ __('Terms of Service') }} - {{ config('app.name', 'CARDSWAP') }}</title>
    <meta name="description" content="{{ __('Terms of Service for CARDSWAP platform - Learn about your rights and obligations when using our service.') }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="{{ route('home') }}" class="flex items-center">
                            <img src="{{ asset('images/logos/cardswap-logo.svg') }}" alt="CARDSWAP" class="h-8 w-auto">
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">
                            {{ __('Back to Home') }}
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-sm p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Terms of Service') }}</h1>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-600 mb-6">
                        <strong>{{ __('Last updated:') }}</strong> {{ date('d/m/Y') }}
                    </p>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. {{ __('Acceptance of Terms') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('By accessing and using CARDSWAP ("the Platform"), you accept and agree to be bound by the terms and provision of this agreement.') }}
                        </p>
                        <p class="text-gray-700">
                            {{ __('If you do not agree to abide by the above, please do not use this service.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. {{ __('Description of Service') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('CARDSWAP is a multi-vendor platform that allows users to buy and sell collectible cards. The platform facilitates transactions between buyers and sellers but is not a party to individual transactions.') }}
                        </p>
                        <p class="text-gray-700">
                            {{ __('We provide:') }}
                        </p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('A marketplace for buying and selling collectible cards') }}</li>
                            <li>{{ __('Payment processing through Stripe') }}</li>
                            <li>{{ __('User verification and KYC services') }}</li>
                            <li>{{ __('Dispute resolution and customer support') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. {{ __('User Accounts') }}</h2>
                        
                        <h3 class="text-xl font-medium text-gray-900 mb-3">3.1 {{ __('Registration') }}</h3>
                        <p class="text-gray-700 mb-4">
                            {{ __('To use our platform, you must create an account and provide accurate, complete information. You are responsible for maintaining the confidentiality of your account credentials.') }}
                        </p>

                        <h3 class="text-xl font-medium text-gray-900 mb-3">3.2 {{ __('Account Types') }}</h3>
                        <ul class="list-disc pl-6 text-gray-700 mb-4">
                            <li><strong>{{ __('Buyer Account:') }}</strong> {{ __('Can purchase cards and leave reviews') }}</li>
                            <li><strong>{{ __('Seller Account:') }}</strong> {{ __('Can list and sell cards (requires verification)') }}</li>
                            <li><strong>{{ __('Verified Seller:') }}</strong> {{ __('Complete KYC verification for enhanced features') }}</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 mb-3">3.3 {{ __('Account Security') }}</h3>
                        <p class="text-gray-700">
                            {{ __('You are responsible for all activities that occur under your account. Notify us immediately of any unauthorized use.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. {{ __('Buying and Selling') }}</h2>
                        
                        <h3 class="text-xl font-medium text-gray-900 mb-3">4.1 {{ __('For Buyers') }}</h3>
                        <ul class="list-disc pl-6 text-gray-700 mb-4">
                            <li>{{ __('You must be at least 18 years old to make purchases') }}</li>
                            <li>{{ __('All purchases are final unless the item is significantly not as described') }}</li>
                            <li>{{ __('Payment must be made through our secure payment system') }}</li>
                            <li>{{ __('You must provide accurate shipping information') }}</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 mb-3">4.2 {{ __('For Sellers') }}</h3>
                        <ul class="list-disc pl-6 text-gray-700 mb-4">
                            <li>{{ __('You must accurately describe all items for sale') }}</li>
                            <li>{{ __('You must have legal ownership of items you list') }}</li>
                            <li>{{ __('You must ship items within the specified timeframe') }}</li>
                            <li>{{ __('You must comply with all applicable laws and regulations') }}</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 mb-3">4.3 {{ __('Prohibited Items') }}</h3>
                        <p class="text-gray-700 mb-2">{{ __('You may not sell:') }}</p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Counterfeit or fake cards') }}</li>
                            <li>{{ __('Stolen property') }}</li>
                            <li>{{ __('Items that violate intellectual property rights') }}</li>
                            <li>{{ __('Items that are illegal in your jurisdiction') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. {{ __('Fees and Payments') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('Our fee structure is as follows:') }}
                        </p>
                        <ul class="list-disc pl-6 text-gray-700 mb-4">
                            <li>{{ __('Buyers: No additional fees beyond item price and shipping') }}</li>
                            <li>{{ __('Sellers: 5% commission on successful sales') }}</li>
                            <li>{{ __('Payment processing: 2.9% + €0.30 per transaction (via Stripe)') }}</li>
                        </ul>
                        <p class="text-gray-700">
                            {{ __('Fees are automatically deducted from seller payments. All prices are displayed in EUR.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. {{ __('Disputes and Refunds') }}</h2>
                        
                        <h3 class="text-xl font-medium text-gray-900 mb-3">6.1 {{ __('Dispute Resolution') }}</h3>
                        <p class="text-gray-700 mb-4">
                            {{ __('If you have a dispute with another user, contact our support team. We will investigate and attempt to resolve disputes fairly.') }}
                        </p>

                        <h3 class="text-xl font-medium text-gray-900 mb-3">6.2 {{ __('Refund Policy') }}</h3>
                        <p class="text-gray-700 mb-4">
                            {{ __('Refunds may be issued in the following circumstances:') }}
                        </p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Item significantly not as described') }}</li>
                            <li>{{ __('Item not received within reasonable timeframe') }}</li>
                            <li>{{ __('Item damaged during shipping (if properly packaged)') }}</li>
                            <li>{{ __('Seller fails to fulfill order') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. {{ __('Intellectual Property') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('The CARDSWAP platform and its content are protected by intellectual property laws. You may not:') }}
                        </p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Copy, modify, or distribute our platform content') }}</li>
                            <li>{{ __('Use our trademarks without permission') }}</li>
                            <li>{{ __('Reverse engineer our software') }}</li>
                            <li>{{ __('Create derivative works based on our platform') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. {{ __('Prohibited Activities') }}</h2>
                        <p class="text-gray-700 mb-4">{{ __('You may not:') }}</p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Violate any applicable laws or regulations') }}</li>
                            <li>{{ __('Infringe on intellectual property rights') }}</li>
                            <li>{{ __('Engage in fraudulent or deceptive practices') }}</li>
                            <li>{{ __('Harass or abuse other users') }}</li>
                            <li>{{ __('Attempt to gain unauthorized access to our systems') }}</li>
                            <li>{{ __('Use automated systems to access our platform') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. {{ __('Termination') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('We may terminate or suspend your account at any time for violations of these terms. You may also terminate your account at any time.') }}
                        </p>
                        <p class="text-gray-700">
                            {{ __('Upon termination, your right to use the platform ceases immediately, but certain provisions of these terms will survive.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. {{ __('Limitation of Liability') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('CARDSWAP is not liable for:') }}
                        </p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Losses arising from transactions between users') }}</li>
                            <li>{{ __('Damages from shipping or delivery issues') }}</li>
                            <li>{{ __('Losses from fraudulent activities by other users') }}</li>
                            <li>{{ __('Indirect, incidental, or consequential damages') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. {{ __('Governing Law') }}</h2>
                        <p class="text-gray-700">
                            {{ __('These terms are governed by Italian law. Any disputes will be resolved in the courts of Rome, Italy.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. {{ __('Changes to Terms') }}</h2>
                        <p class="text-gray-700">
                            {{ __('We may modify these terms at any time. We will notify users of significant changes via email or platform notification. Continued use constitutes acceptance of modified terms.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">13. {{ __('Contact Information') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('For questions about these terms, contact us:') }}
                        </p>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-700">
                                <strong>{{ __('Email:') }}</strong> legal@cardswap.com<br>
                                <strong>{{ __('Address:') }}</strong> CARDSWAP Legal Team<br>
                                Via Roma 123, 00100 Roma, Italia
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-gray-400">
                        &copy; {{ date('Y') }} CARDSWAP. {{ __('All rights reserved.') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
