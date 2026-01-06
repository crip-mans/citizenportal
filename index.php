<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Get statistics for display
$stats = $db->query("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        COUNT(DISTINCT citizen_id) as total_citizens
    FROM tickets
")->fetch();

$resolution_rate = $stats['total_tickets'] > 0 
    ? round(($stats['resolved_tickets'] / $stats['total_tickets']) * 100) 
    : 0;

// Get popular FAQs
$popular_faqs = $db->query("
    SELECT question, answer 
    FROM faq_knowledge 
    WHERE is_active = 1 
    ORDER BY view_count DESC 
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Smart Citizen Services</title>
    <meta name="description" content="Report concerns, track complaints, and get instant answers with AI-powered assistance. Your voice matters in building a better community.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    
    <!-- Navigation -->
    <nav class="fixed w-full top-0 z-50 glass-effect shadow-md" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-building text-indigo-600 text-2xl"></i>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
                        <p class="text-xs text-gray-500 hidden sm:block"><?php echo SITE_TAGLINE; ?></p>
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="#features" class="text-gray-700 hover:text-indigo-600 transition font-medium">Features</a>
                    <a href="#how-it-works" class="text-gray-700 hover:text-indigo-600 transition font-medium">How It Works</a>
                    <a href="#faq" class="text-gray-700 hover:text-indigo-600 transition font-medium">FAQ</a>
                    <a href="portal.php" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                        Get Started
                    </a>
                    <a href="admin/login.php" class="text-gray-700 hover:text-indigo-600 transition">
                        <i class="fas fa-user-shield mr-1"></i>Admin
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2">
                    <i class="fas fa-bars text-2xl text-gray-700"></i>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileOpen" x-cloak class="md:hidden pb-4 space-y-3">
                <a href="#features" class="block py-2 text-gray-700 hover:text-indigo-600">Features</a>
                <a href="#how-it-works" class="block py-2 text-gray-700 hover:text-indigo-600">How It Works</a>
                <a href="#faq" class="block py-2 text-gray-700 hover:text-indigo-600">FAQ</a>
                <a href="portal.php" class="block py-2 px-4 bg-indigo-600 text-white rounded-lg text-center">Get Started</a>
                <a href="admin/login.php" class="block py-2 text-gray-700">
                    <i class="fas fa-user-shield mr-1"></i>Admin Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-block px-4 py-2 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium mb-6">
                        <i class="fas fa-sparkles mr-2"></i>AI-Powered Citizen Services
                    </div>
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                        Your Voice Matters in 
                        <span class="gradient-text">Building Better Communities</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8">
                        Report concerns, track complaints, and get instant answers with our intelligent platform. 
                        Fast, transparent, and always available.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="index.php" class="px-8 py-4 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition font-semibold text-center shadow-lg hover:shadow-xl">
                            <i class="fas fa-rocket mr-2"></i>Get Started Free
                        </a>
                        <a href="#how-it-works" class="px-8 py-4 border-2 border-indigo-600 text-indigo-600 rounded-xl hover:bg-indigo-50 transition font-semibold text-center">
                            <i class="fas fa-play-circle mr-2"></i>Learn How
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 mt-12">
                        <div>
                            <p class="text-3xl font-bold text-indigo-600"><?php echo number_format($stats['total_tickets']); ?>+</p>
                            <p class="text-sm text-gray-600 mt-1">Tickets Processed</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-green-600"><?php echo $resolution_rate; ?>%</p>
                            <p class="text-sm text-gray-600 mt-1">Resolved Rate</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-purple-600"><?php echo number_format($stats['total_citizens']); ?>+</p>
                            <p class="text-sm text-gray-600 mt-1">Citizens Served</p>
                        </div>
                    </div>
                </div>

                <!-- Hero Image/Illustration -->
                <div class="relative">
                    <div class="float-animation">
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-8 shadow-2xl">
                            <div class="bg-white rounded-2xl p-6 space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-robot text-indigo-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="h-3 bg-gray-200 rounded w-3/4 mb-2"></div>
                                        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                    </div>
                                </div>
                                <div class="bg-indigo-50 rounded-xl p-4">
                                    <p class="text-sm text-indigo-900">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        Your concern has been automatically routed to the Infrastructure Department
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <div class="flex-1 bg-gray-100 rounded-lg p-3">
                                        <i class="fas fa-ticket-alt text-indigo-600 mb-2"></i>
                                        <p class="text-xs text-gray-600">Track Status</p>
                                    </div>
                                    <div class="flex-1 bg-gray-100 rounded-lg p-3">
                                        <i class="fas fa-comments text-green-600 mb-2"></i>
                                        <p class="text-xs text-gray-600">Get Updates</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Floating elements -->
                    <div class="absolute top-10 -right-10 w-20 h-20 bg-yellow-400 rounded-full opacity-50 blur-xl"></div>
                    <div class="absolute bottom-10 -left-10 w-32 h-32 bg-pink-400 rounded-full opacity-50 blur-xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Powerful Features for Modern Governance
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Everything you need to connect with your LGU efficiently and transparently
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="group p-6 rounded-2xl hover:bg-indigo-50 transition border-2 border-transparent hover:border-indigo-200">
                    <div class="w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-indigo-600 transition">
                        <i class="fas fa-brain text-2xl text-indigo-600 group-hover:text-white transition"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">AI Auto-Routing</h3>
                    <p class="text-gray-600">
                        Smart algorithms automatically classify and route your concerns to the right department instantly.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="group p-6 rounded-2xl hover:bg-green-50 transition border-2 border-transparent hover:border-green-200">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-600 transition">
                        <i class="fas fa-comments text-2xl text-green-600 group-hover:text-white transition"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Q&A Assistant</h3>
                    <p class="text-gray-600">
                        Get instant answers to common questions about permits, certificates, and LGU services.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="group p-6 rounded-2xl hover:bg-purple-50 transition border-2 border-transparent hover:border-purple-200">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-600 transition">
                        <i class="fas fa-chart-line text-2xl text-purple-600 group-hover:text-white transition"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Real-Time Tracking</h3>
                    <p class="text-gray-600">
                        Monitor your ticket status with live updates and full transparency from submission to resolution.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="group p-6 rounded-2xl hover:bg-yellow-50 transition border-2 border-transparent hover:border-yellow-200">
                    <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-yellow-600 transition">
                        <i class="fas fa-sitemap text-2xl text-yellow-600 group-hover:text-white transition"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Multi-Department Support</h3>
                    <p class="text-gray-600">
                        Complex issues? Submit to multiple departments in one ticket for coordinated resolution.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-gradient-to-br from-indigo-600 to-purple-600 text-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">How It Works</h2>
                <p class="text-xl text-indigo-100 max-w-3xl mx-auto">
                    Get your concerns addressed in three simple steps
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
                        <span class="text-3xl font-bold text-indigo-600">1</span>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Submit Your Concern</h3>
                    <p class="text-indigo-100">
                        Describe your issue or question in the submission form. Our AI will analyze and categorize it automatically.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
                        <span class="text-3xl font-bold text-indigo-600">2</span>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Automatic Routing</h3>
                    <p class="text-indigo-100">
                        Your ticket is instantly routed to the appropriate department(s) and staff are notified immediately.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
                        <span class="text-3xl font-bold text-indigo-600">3</span>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Track & Resolve</h3>
                    <p class="text-indigo-100">
                        Monitor progress in real-time, receive updates, and get notified when your concern is resolved.
                    </p>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="index.php" class="inline-block px-8 py-4 bg-white text-indigo-600 rounded-xl hover:bg-indigo-50 transition font-bold shadow-xl text-lg">
                    <i class="fas fa-arrow-right mr-2"></i>Start Now
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-gray-600">Quick answers to common questions</p>
            </div>

            <div class="space-y-4" x-data="{ open: null }">
                <?php foreach ($popular_faqs as $index => $faq): ?>
                <div class="border-2 border-gray-200 rounded-xl overflow-hidden">
                    <button 
                        @click="open = open === <?php echo $index; ?> ? null : <?php echo $index; ?>"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition"
                    >
                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($faq['question']); ?></span>
                        <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': open === <?php echo $index; ?> }"></i>
                    </button>
                    <div 
                        x-show="open === <?php echo $index; ?>" 
                        x-cloak
                        class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                    >
                        <p class="text-gray-600"><?php echo htmlspecialchars($faq['answer']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="border-2 border-gray-200 rounded-xl overflow-hidden">
                    <button 
                        @click="open = open === 'more' ? null : 'more'"
                        class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition"
                    >
                        <span class="font-semibold text-gray-900">How do I track my submitted ticket?</span>
                        <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': open === 'more' }"></i>
                    </button>
                    <div 
                        x-show="open === 'more'" 
                        x-cloak
                        class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                    >
                        <p class="text-gray-600">
                            After submitting your concern, you'll receive a unique ticket number. Use this number on the 
                            "Track Tickets" page along with your contact number to view real-time status updates and resolution progress.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="assistant.php" class="inline-block px-6 py-3 border-2 border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 transition font-medium">
                    <i class="fas fa-robot mr-2"></i>Ask Our Smart Assistant
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">Ready to Make Your Voice Heard?</h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-2xl mx-auto">
                Join thousands of citizens using our intelligent platform to connect with their local government.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="index.php" class="px-8 py-4 bg-white text-indigo-600 rounded-xl hover:bg-indigo-50 transition font-bold text-lg shadow-xl">
                    <i class="fas fa-rocket mr-2"></i>Get Started Free
                </a>
                <a href="assistant.php" class="px-8 py-4 border-2 border-white text-white rounded-xl hover:bg-white hover:text-indigo-600 transition font-bold text-lg">
                    <i class="fas fa-robot mr-2"></i>Try Smart Assistant
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-white font-bold text-lg mb-4"><?php echo SITE_NAME; ?></h3>
                    <p class="text-sm text-gray-400">
                        Empowering citizens through intelligent technology and transparent governance.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="index.php" class="hover:text-white transition">Home</a></li>
                        <li><a href="submit.php" class="hover:text-white transition">Submit Concern</a></li>
                        <li><a href="track.php" class="hover:text-white transition">Track Tickets</a></li>
                        <li><a href="assistant.php" class="hover:text-white transition">Smart Assistant</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">For Officials</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="admin/login.php" class="hover:text-white transition">Admin Login</a></li>
                        <li><a href="admin/dashboard.php" class="hover:text-white transition">Dashboard</a></li>
                        <li><a href="admin/reports.php" class="hover:text-white transition">Reports</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Contact Us</h4>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <i class="fas fa-phone mr-2"></i>
                            <?php echo CONTACT_PHONE; ?>
                        </li>
                        <li>
                            <i class="fas fa-envelope mr-2"></i>
                            <?php echo CONTACT_EMAIL; ?>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <?php echo SITE_TAGLINE; ?>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_TAGLINE; ?> LGU - <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <p class="mt-2">Powered by AI-Driven Citizen Engagement Platform</p>
            </div>
        </div>
    </footer>

</body>
</html>