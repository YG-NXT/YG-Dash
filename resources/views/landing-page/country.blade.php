<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $hero['title'] ?? 'DashSaaS UK' }} - ERP Software for UK Businesses</title>
    <meta name="description" content="{{ $hero['subtitle'] ?? 'HMRC MTD compliant ERP for UK businesses. VAT invoicing, PAYE payroll, NHS integration.' }}">
    <meta name="keywords" content="ERP UK, HMRC MTD, VAT software, UK payroll, NHS integration, CQC compliance">
    <meta name="country" content="GB">
    <meta name="currency" content="GBP">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $hero['title'] ?? 'DashSaaS UK' }}">
    <meta property="og:description" content="{{ $hero['subtitle'] ?? 'HMRC MTD compliant ERP for UK businesses' }}">
    <meta property="og:country" content="GB">
    
    <!-- Schema.org for local business -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "DashSaaS UK",
        "description": "UK's leading ERP software. HMRC MTD compliant.",
        "country": "GB",
        "currency": "GBP"
    }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .hero { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 100px 0; text-align: center; }
        .hero h1 { font-size: 3rem; margin-bottom: 20px; }
        .hero p { font-size: 1.25rem; margin-bottom: 30px; opacity: 0.9; }
        .btn { display: inline-block; padding: 15px 30px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 0 10px; }
        .btn:hover { background: #059669; }
        .features { padding: 80px 0; background: #f8fafc; }
        .features h2 { text-align: center; font-size: 2.5rem; margin-bottom: 50px; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .feature-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .feature-card h3 { color: #1e40af; margin-bottom: 10px; }
        .pricing { padding: 80px 0; }
        .pricing h2 { text-align: center; font-size: 2.5rem; margin-bottom: 50px; }
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1000px; margin: 0 auto; }
        .pricing-card { background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 30px; text-align: center; position: relative; }
        .pricing-card.popular { border-color: #10b981; transform: scale(1.05); }
        .pricing-card h3 { font-size: 1.5rem; margin-bottom: 10px; }
        .price { font-size: 2.5rem; font-weight: bold; color: #1e40af; margin: 20px 0; }
        .price span { font-size: 1rem; color: #6b7280; }
        .compliance { padding: 80px 0; background: #f8fafc; }
        .compliance h2 { text-align: center; font-size: 2.5rem; margin-bottom: 50px; }
        .badge-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; }
        .badge { display: flex; align-items: center; gap: 8px; background: white; padding: 10px 20px; border-radius: 50px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .cta { padding: 80px 0; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; text-align: center; }
        .cta h2 { font-size: 2.5rem; margin-bottom: 20px; }
        .cta .btn { background: #10b981; margin-top: 20px; }
        footer { background: #1f2937; color: white; padding: 40px 0; text-align: center; }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>{{ $hero['title'] ?? "UK's Leading ERP Software" }}</h1>
            <p>{{ $hero['subtitle'] ?? 'HMRC MTD compliant. VAT invoicing built-in. NHS-ready for healthcare.' }}</p>
            <a href="{{ $hero['cta_link'] ?? '/uk/onboarding' }}" class="btn">{{ $hero['cta_text'] ?? 'Start your free UK trial' }}</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>Why Choose DashSaaS UK?</h2>
            <div class="feature-grid">
                @foreach($features as $feature)
                    <div class="feature-card">
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing">
        <div class="container">
            <h2>Simple, Transparent Pricing</h2>
            <div class="pricing-grid">
                @foreach($pricing['plans'] as $plan)
                    <div class="pricing-card {{ $plan['popular'] ?? false ? 'popular' : '' }}">
                        <h3>{{ $plan['name'] }}</h3>
                        <div class="price">
                            {{ $pricing['symbol'] ?? '£' }}{{ $plan['price'] }}
                            <span>/{{ $plan['period'] }}</span>
                        </div>
                        <ul>
                            @foreach($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ $hero['cta_link'] ?? '/uk/onboarding' }}" class="btn">Start Free Trial</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Compliance Section -->
    <section class="compliance">
        <div class="container">
            <h2>UK Compliance Built-In</h2>
            <div class="badge-grid">
                @foreach($compliance['badges'] as $badge)
                    <div class="badge">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="font-medium">{{ $badge['name'] }}</span>
                        <span class="text-gray-600">{{ $badge['description'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>{{ $cta['title'] ?? 'Ready to transform your UK business?' }}</h2>
            <p>{{ $cta['subtitle'] ?? 'Join 2,000+ UK companies using DashSaaS' }}</p>
            <a href="{{ $cta['button_link'] ?? '/uk/onboarding' }}" class="btn">{{ $cta['button_text'] ?? 'Start your free trial' }}</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p> DashSaaS UK. HMRC MTD compliant ERP software.</p>
            <p style="margin-top: 10px; opacity: 0.7;"> Registered in England & Wales | VAT Reg No: GB123456789</p>
        </div>
    </footer>
</body>
</html>
