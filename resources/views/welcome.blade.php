<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }} - Monitoring API</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1a202c;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #06b6d4 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 100px 0;
            color: white;
            position: relative;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #06b6d4 100%);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="60" cy="30" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="60" r="1.5" fill="white" opacity="0.1"/><circle cx="30" cy="70" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="80" r="1.2" fill="white" opacity="0.08"/><circle cx="70" cy="20" r="0.8" fill="white" opacity="0.12"/></svg>');
            opacity: 0.6;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to top, rgba(255, 255, 255, 0.1), transparent);
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 16px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .tagline {
            font-size: 1.25rem;
            margin-bottom: 32px;
            opacity: 0.95;
            font-weight: 400;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* Main Content */
        .main-content {
            background: white;
            margin-top: -80px;
            border-radius: 40px 40px 0 0;
            position: relative;
            z-index: 2;
            box-shadow: 0 -20px 60px rgba(0, 0, 0, 0.1);
            padding-bottom: 80px;
        }

        .section {
            padding: 80px 20px;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 64px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            margin-bottom: 80px;
        }

        .feature-card {
            background: white;
            padding: 40px 32px;
            border-radius: 24px;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 2px solid #f1f5f9;
        }

        .feature-card:nth-child(1) .feature-icon {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: #0284c7;
            border: 2px solid #bae6fd;
        }

        .feature-card:nth-child(2) .feature-icon {
            background: linear-gradient(135deg, #fff1f2, #fecaca);
            color: #dc2626;
            border: 2px solid #fca5a5;
        }

        .feature-card:nth-child(3) .feature-icon {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            color: #16a34a;
            border: 2px solid #bbf7d0;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1e293b;
        }

        .feature-description {
            color: #64748b;
            line-height: 1.6;
        }

        /* Capabilities Section */
        .capabilities {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 32px;
            padding: 60px 40px;
            margin-bottom: 80px;
        }

        .capabilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            margin-top: 48px;
        }

        .capability-group h3 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1e293b;
        }

        .capability-list {
            list-style: none;
            space-y: 12px;
        }

        .capability-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            color: #475569;
            font-size: 0.95rem;
        }

        .capability-list li::before {
            content: "✦";
            color: #6366f1;
            font-weight: 600;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Quick Start */
        .quick-start {
            background: #1e293b;
            border-radius: 24px;
            padding: 48px 40px;
            margin-bottom: 80px;
            position: relative;
            overflow: hidden;
        }

        .quick-start::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        .quick-start-content {
            position: relative;
            z-index: 1;
        }

        .quick-start h2 {
            color: white;
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 32px;
            text-align: center;
        }

        .code-examples {
            background: #0f172a;
            border-radius: 16px;
            padding: 32px;
            font-family: 'Fira Code', 'Monaco', 'Cascadia Code', monospace;
            overflow-x: auto;
        }

        .code-comment {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
        }

        .code-command {
            color: #e2e8f0;
            font-size: 0.85rem;
            line-height: 1.6;
            margin-bottom: 24px;
            display: block;
            word-break: break-all;
        }

        .code-command:last-child {
            margin-bottom: 0;
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 0 20px;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 16px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.4);
        }

        .cta-description {
            margin-top: 16px;
            color: #64748b;
            font-size: 0.95rem;
        }

        /* Footer */
        .footer {
            margin-top: 80px;
            padding: 40px 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo {
                font-size: 2.5rem;
            }

            .section {
                padding: 60px 20px;
            }

            .section-title {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .capabilities {
                padding: 40px 24px;
            }

            .quick-start {
                padding: 32px 24px;
            }

            .code-examples {
                padding: 24px;
            }
        }

        /* Animations */
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

        .feature-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .feature-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .feature-card:nth-child(3) {
            animation-delay: 0.4s;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">Monitoring API</h1>
                <p class="tagline">Professional Application Monitoring & Incident Management Platform</p>
                <div class="badge">
                    <span>⚡</span>
                    Laravel  Powered API
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Features Section -->
            <section class="section">
                <h2 class="section-title">Core Features</h2>
                <p class="section-subtitle">
                    Everything you need to monitor your applications, manage incidents, and stay informed with
                    intelligent notifications.
                </p>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3 class="feature-title">Real-time Monitoring</h3>
                        <p class="feature-description">
                            Monitor application health with automated status checks, uptime tracking, and performance
                            metrics. Group applications for better organization and oversight.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🚨</div>
                        <h3 class="feature-title">Advanced Incident Management</h3>
                        <p class="feature-description">
                            Create, track, and resolve incidents with detailed logging, severity levels, automated
                            workflows, and comprehensive timeline tracking for faster resolution.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔔</div>
                        <h3 class="feature-title">Intelligent Notifications</h3>
                        <p class="feature-description">
                            Multi-channel notification system with webhooks, email alerts, SMS support, and customizable
                            subscription management for instant team alerts.
                        </p>
                    </div>
                </div>
            </section>

            <!-- API Capabilities -->
            <section class="capabilities">
                <h2 class="section-title">API Capabilities</h2>
                <p class="section-subtitle">
                    Comprehensive REST API designed for modern applications with robust features and excellent developer
                    experience.
                </p>

                <div class="capabilities-grid">
                    <div class="capability-group">
                        <h3>🏢 Application Management</h3>
                        <ul class="capability-list">
                            <li>Create and manage applications with metadata</li>
                            <li>Organize applications into logical groups</li>
                            <li>Real-time health status monitoring</li>
                            <li>Automated uptime tracking and analytics</li>
                            <li>Custom application configuration settings</li>
                            <li>RESTful API endpoints for all operations</li>
                        </ul>
                    </div>

                    <div class="capability-group">
                        <h3>🚨 Incident Tracking</h3>
                        <ul class="capability-list">
                            <li>Create, update, and resolve incidents</li>
                            <li>Multiple severity levels (low, medium, high, critical)</li>
                            <li>Detailed incident timeline and history</li>
                            <li>Automatic escalation workflows</li>
                            <li>Incident statistics and reporting</li>
                            <li>Status transitions and validation rules</li>
                        </ul>
                    </div>

                    <div class="capability-group">
                        <h3>🔔 Notification System</h3>
                        <ul class="capability-list">
                            <li>Webhook notifications with retry logic</li>
                            <li>Email and SMS notification channels</li>
                            <li>Flexible subscription management</li>
                            <li>Test notification endpoints</li>
                            <li>Notification history and delivery tracking</li>
                            <li>Custom notification templates</li>
                        </ul>
                    </div>

                    <div class="capability-group">
                        <h3>🔐 Security & Authentication</h3>
                        <ul class="capability-list">
                            <li>JWT-based authentication system</li>
                            <li>User registration and profile management</li>
                            <li>API rate limiting and throttling</li>
                            <li>Secure webhook signature validation</li>
                            <li>Policy-based access control</li>
                            <li>Laravel Sanctum integration</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Quick Start -->
            <section class="quick-start">
                <div class="quick-start-content">
                    <h2>Quick Start Examples</h2>
                    <div class="code-examples">
                        <code class="code-comment"># Register a new user</code>
                        <code
                            class="code-command">curl -X POST {{ config('app.url') }}/api/auth/register \<br>&nbsp;&nbsp;-H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"name":"John Doe","email":"john@example.com","password":"secure123"}'</code>

                        <code class="code-comment"># Create an application</code>
                        <code
                            class="code-command">curl -X POST {{ config('app.url') }}/api/applications \<br>&nbsp;&nbsp;-H "Authorization: Bearer YOUR_TOKEN" \<br>&nbsp;&nbsp;-H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"name":"My App","url":"https://myapp.com"}'</code>

                        <code class="code-comment"># Check application status</code>
                        <code
                            class="code-command">curl {{ config('app.url') }}/api/applications/1/status \<br>&nbsp;&nbsp;-H "Authorization: Bearer YOUR_TOKEN"</code>

                        <code class="code-comment"># Create an incident</code>
                        <code
                            class="code-command">curl -X POST {{ config('app.url') }}/api/incidents \<br>&nbsp;&nbsp;-H "Authorization: Bearer YOUR_TOKEN" \<br>&nbsp;&nbsp;-H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"application_id":1,"title":"API Down","description":"Service unavailable","severity":"high"}'</code>
                    </div>
                </div>
            </section>

            <!-- API Overview -->
            <section class="section"
                style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: 32px; margin-bottom: 80px; padding: 60px 40px;">
                <h2 class="section-title">API Endpoint Overview</h2>
                <p class="section-subtitle">
                    Comprehensive REST API with intuitive endpoints for all monitoring operations.
                </p>

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; margin-top: 48px;">
                    <div
                        style="background: white; padding: 32px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                        <h3
                            style="display: flex; align-items: center; gap: 12px; font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; color: #1e293b;">
                            🔐 Authentication
                        </h3>
                        <ul style="list-style: none; space-y: 8px;">
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">POST</span>
                                /api/auth/register
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">POST</span>
                                /api/auth/login
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">GET</span>
                                /api/auth/user
                            </li>
                        </ul>
                    </div>

                    <div
                        style="background: white; padding: 32px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                        <h3
                            style="display: flex; align-items: center; gap: 12px; font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; color: #1e293b;">
                            🏢 Applications
                        </h3>
                        <ul style="list-style: none; space-y: 8px;">
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">GET</span>
                                /api/applications
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">POST</span>
                                /api/applications
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">GET</span>
                                /api/applications/{id}/status
                            </li>
                        </ul>
                    </div>

                    <div
                        style="background: white; padding: 32px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                        <h3
                            style="display: flex; align-items: center; gap: 12px; font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; color: #1e293b;">
                            🚨 Incidents
                        </h3>
                        <ul style="list-style: none; space-y: 8px;">
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">GET</span>
                                /api/incidents
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">POST</span>
                                /api/incidents
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">PUT</span>
                                /api/incidents/{id}/resolve
                            </li>
                        </ul>
                    </div>

                    <div
                        style="background: white; padding: 32px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                        <h3
                            style="display: flex; align-items: center; gap: 12px; font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; color: #1e293b;">
                            🔔 Notifications
                        </h3>
                        <ul style="list-style: none; space-y: 8px;">
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">GET</span>
                                /api/subscriptions
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">POST</span>
                                /api/subscriptions/{id}/test
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.9rem; color: #475569;">
                                <span
                                    style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">GET</span>
                                /api/user/notification-history
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-section">
                <a href="{{ route('scribe') }}" class="cta-button">
                    <span>📚</span>
                    Explore Complete API Documentation
                </a>
                <p class="cta-description">
                    Discover all endpoints, request/response examples, and integration guides to get started quickly.
                </p>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>Built with ❤️ using Laravel {{ app()->version() }} • Professional monitoring API for modern applications
            </p>
            <p style="margin-top: 8px; font-size: 0.8rem; opacity: 0.7;">
                Base API URL: {{ config('app.url') }}/api •
                <a href="{{ route('scribe') }}" style="color: #6366f1; text-decoration: none;">Documentation</a> •
                <a href="{{ config('app.url') }}/api/status" style="color: #6366f1; text-decoration: none;">API
                    Status</a>
            </p>
        </div>
    </footer>
</body>

</html>