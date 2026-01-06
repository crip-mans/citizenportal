<!-- Footer -->
<footer class="bg-white border-t border-gray-200 mt-12">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <!-- About -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2">About the Portal</h3>
                <p class="text-sm text-gray-600">
                    The Intelligent Citizen Portal streamlines LGU service delivery with AI-powered routing and real-time tracking.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2">Quick Links</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><a href="index.php" class="hover:text-indigo-600"><i class="fas fa-chevron-right mr-2"></i>Home</a></li>
                    <li><a href="submit.php" class="hover:text-indigo-600"><i class="fas fa-chevron-right mr-2"></i>Submit Concern</a></li>
                    <li><a href="assistant.php" class="hover:text-indigo-600"><i class="fas fa-chevron-right mr-2"></i>Ask Assistant</a></li>
                    <li><a href="track.php" class="hover:text-indigo-600"><i class="fas fa-chevron-right mr-2"></i>Track Tickets</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2">Contact Us</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><i class="fas fa-phone mr-2 text-indigo-600"></i><?php echo CONTACT_PHONE; ?></li>
                    <li><i class="fas fa-envelope mr-2 text-indigo-600"></i><?php echo CONTACT_EMAIL; ?></li>
                    <li><i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i><?php echo SITE_TAGLINE; ?></li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-6 text-center text-gray-600 text-sm">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_TAGLINE; ?> LGU - <?php echo SITE_NAME; ?></p>
            <p class="mt-1">Powered by AI-Driven Citizen Engagement Platform</p>
        </div>
    </div>
</footer>