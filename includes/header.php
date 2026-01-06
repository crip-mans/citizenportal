<!-- Header -->
<header class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-3 hover:opacity-80 transition">
                <i class="fas fa-building text-indigo-600 text-3xl"></i>
                <div>
                    <h1 class="text-xl font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
                    <p class="text-xs text-gray-500"><?php echo SITE_TAGLINE; ?></p>
                </div>
            </a>
            <nav class="hidden md:flex space-x-4">
                <a href="index.php" class="px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'; ?> rounded-lg transition">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="submit.php" class="px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'submit.php') ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'; ?> rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Submit Concern
                </a>
                <a href="assistant.php" class="px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'assistant.php') ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'; ?> rounded-lg transition">
                    <i class="fas fa-robot mr-2"></i>Ask Assistant
                </a>
                <a href="track.php" class="px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'track.php') ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'; ?> rounded-lg transition">
                    <i class="fas fa-search mr-2"></i>Track Tickets
                </a>
            </nav>
            <button class="md:hidden" onclick="toggleMobileMenu()">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden mt-4 space-y-2">
            <a href="index.php" class="block px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?> rounded-lg">
                <i class="fas fa-home mr-2"></i>Home
            </a>
            <a href="submit.php" class="block px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'submit.php') ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?> rounded-lg">
                <i class="fas fa-plus mr-2"></i>Submit Concern
            </a>
            <a href="assistant.php" class="block px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'assistant.php') ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?> rounded-lg">
                <i class="fas fa-robot mr-2"></i>Ask Assistant
            </a>
            <a href="track.php" class="block px-4 py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'track.php') ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?> rounded-lg">
                <i class="fas fa-search mr-2"></i>Track Tickets
            </a>
        </div>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('hidden');
}
</script>