<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ __('Privacy Policy') }} - {{ config('app.name', 'CARDSWAP') }}</title>
    <meta name="description" content="{{ __('Privacy Policy for CARDSWAP platform - Learn how we collect, use, and protect your personal data.') }}">
    
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
                <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Privacy Policy') }}</h1>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-600 mb-6">
                        <strong>{{ __('Last updated:') }}</strong> {{ date('d/m/Y') }}
                    </p>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. {{ __('Introduction') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('This Privacy Policy describes how CARDSWAP ("we", "our", or "us") collects, uses, and protects your personal information when you use our platform for buying and selling collectible cards.') }}
                        </p>
                        <p class="text-gray-700">
                            {{ __('By using our service, you agree to the collection and use of information in accordance with this policy.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. {{ __('Information We Collect') }}</h2>
                        
                        <h3 class="text-xl font-medium text-gray-900 mb-3">2.1 {{ __('Personal Information') }}</h3>
                        <ul class="list-disc pl-6 text-gray-700 mb-4">
                            <li>{{ __('Name and contact information (email, phone number)') }}</li>
                            <li>{{ __('Billing and shipping addresses') }}</li>
                            <li>{{ __('Payment information (processed securely through Stripe)') }}</li>
                            <li>{{ __('Identity verification documents (for KYC compliance)') }}</li>
                            <li>{{ __('Profile information and preferences') }}</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 mb-3">2.2 {{ __('Usage Information') }}</h3>
                        <ul class="list-disc pl-6 text-gray-700 mb-4">
                            <li>{{ __('Platform usage data and interactions') }}</li>
                            <li>{{ __('Search queries and browsing behavior') }}</li>
                            <li>{{ __('Transaction history and preferences') }}</li>
                            <li>{{ __('Device information and IP address') }}</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 mb-3">2.3 {{ __('Cookies and Tracking') }}</h3>
                        <p class="text-gray-700 mb-2">{{ __('We use cookies and similar technologies to:') }}</p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Essential cookies for platform functionality') }}</li>
                            <li>{{ __('Analytics cookies to improve our service') }}</li>
                            <li>{{ __('Marketing cookies for personalized content') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. {{ __('How We Use Your Information') }}</h2>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Process transactions and manage your account') }}</li>
                            <li>{{ __('Verify your identity for security purposes') }}</li>
                            <li>{{ __('Provide customer support and communicate with you') }}</li>
                            <li>{{ __('Improve our platform and develop new features') }}</li>
                            <li>{{ __('Comply with legal obligations and prevent fraud') }}</li>
                            <li>{{ __('Send marketing communications (with your consent)') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. {{ __('Data Sharing and Disclosure') }}</h2>
                        <p class="text-gray-700 mb-4">{{ __('We may share your information with:') }}</p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Payment processors (Stripe) for transaction processing') }}</li>
                            <li>{{ __('Shipping providers for order fulfillment') }}</li>
                            <li>{{ __('Legal authorities when required by law') }}</li>
                            <li>{{ __('Service providers who assist in platform operations') }}</li>
                        </ul>
                        <p class="text-gray-700 mt-4">
                            {{ __('We do not sell your personal information to third parties.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. {{ __('Data Security') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('We implement appropriate security measures to protect your personal information:') }}
                        </p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Encryption of sensitive data in transit and at rest') }}</li>
                            <li>{{ __('Regular security audits and updates') }}</li>
                            <li>{{ __('Access controls and authentication systems') }}</li>
                            <li>{{ __('Secure data centers and backup systems') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. {{ __('Your Rights (GDPR)') }}</h2>
                        <p class="text-gray-700 mb-4">{{ __('Under GDPR, you have the right to:') }}</p>
                        <ul class="list-disc pl-6 text-gray-700">
                            <li>{{ __('Access your personal data') }}</li>
                            <li>{{ __('Rectify inaccurate or incomplete data') }}</li>
                            <li>{{ __('Erase your data ("right to be forgotten")') }}</li>
                            <li>{{ __('Restrict processing of your data') }}</li>
                            <li>{{ __('Data portability') }}</li>
                            <li>{{ __('Object to processing') }}</li>
                            <li>{{ __('Withdraw consent at any time') }}</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. {{ __('Data Retention') }}</h2>
                        <p class="text-gray-700">
                            {{ __('We retain your personal information only as long as necessary for the purposes outlined in this policy, or as required by law. Transaction records are typically retained for 7 years for tax and legal compliance.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. {{ __('International Transfers') }}</h2>
                        <p class="text-gray-700">
                            {{ __('Your data may be transferred to and processed in countries outside the European Economic Area. We ensure appropriate safeguards are in place to protect your data in accordance with GDPR requirements.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. {{ __('Children\'s Privacy') }}</h2>
                        <p class="text-gray-700">
                            {{ __('Our service is not intended for children under 16. We do not knowingly collect personal information from children under 16. If we become aware of such collection, we will take steps to delete the information.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. {{ __('Changes to This Policy') }}</h2>
                        <p class="text-gray-700">
                            {{ __('We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.') }}
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. {{ __('Contact Us') }}</h2>
                        <p class="text-gray-700 mb-4">
                            {{ __('If you have any questions about this Privacy Policy or our data practices, please contact us:') }}
                        </p>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-700">
                                <strong>{{ __('Email:') }}</strong> privacy@cardswap.com<br>
                                <strong>{{ __('Address:') }}</strong> CARDSWAP Privacy Team<br>
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
