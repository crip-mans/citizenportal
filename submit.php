<?php
require_once 'config.php';
require_once 'AICategorizer.php';

$categorizer = new AICategorizer();
$departments = $categorizer->getAllDepartments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Concern - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <?php echo getFlashMessage(); ?>
        
        <div class="bg-white rounded-xl shadow-lg p-8" x-data="concernForm()">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-file-alt text-indigo-600 mr-2"></i>
                Submit a Concern or Complaint
            </h2>

            <form action="api/submit_ticket.php" method="POST" @submit="submitForm">
                <!-- Concern Title -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Concern Title <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        x-model="form.title"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Brief summary of your concern"
                    >
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Detailed Description <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        name="description" 
                        x-model="form.description"
                        @input.debounce.500ms="analyzeDescription()"
                        rows="5"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Describe your concern in detail..."
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1" x-show="form.description.length > 20">
                        <i class="fas fa-robot"></i> AI is analyzing your description...
                    </p>
                </div>

                <!-- AI Suggestions -->
                <div x-show="suggestedDepts.length > 0" x-cloak class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-indigo-600 text-xl mr-3 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-indigo-900 mb-2">
                                AI Suggested Departments (Confidence: <span x-text="confidence + '%'"></span>):
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="dept in suggestedDepts" :key="dept.id">
                                    <span class="px-3 py-1 bg-white text-indigo-700 rounded-full text-sm font-medium border border-indigo-300">
                                        <span x-text="dept.name"></span>
                                        <span class="text-xs ml-1">(<span x-text="dept.score.toFixed(1)"></span>)</span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select Departments <span class="text-red-500">*</span> (can select multiple)
                    </label>
                    <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-lg p-4">
                        <?php foreach ($departments as $dept): ?>
                        <label class="flex items-start cursor-pointer hover:bg-gray-50 p-2 rounded">
                            <input 
                                type="checkbox" 
                                name="departments[]" 
                                value="<?php echo $dept['id']; ?>"
                                class="mt-1 mr-3"
                            >
                            <div>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($dept['name']); ?></span>
                                <?php if ($dept['description']): ?>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($dept['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Select all departments that should handle this concern
                    </p>
                </div>

                <!-- Location and Priority -->
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Location
                        </label>
                        <input 
                            type="text" 
                            name="location"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="e.g., Barangay, Street"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Priority
                        </label>
                        <select 
                            name="priority"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Your Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="contact_name"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="Full name"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Contact Number <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="tel" 
                            name="contact_phone"
                            required
                            pattern="[0-9]{11}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="09XXXXXXXXX"
                        >
                    </div>
                </div>

                <!-- Email (Optional) -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address (Optional)
                    </label>
                    <input 
                        type="email" 
                        name="contact_email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="your.email@example.com"
                    >
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 pt-4">
                    <button 
                        type="reset"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                    >
                        <i class="fas fa-undo mr-2"></i>Clear Form
                    </button>
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>Submit Concern
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function concernForm() {
            return {
                form: {
                    title: '',
                    description: ''
                },
                suggestedDepts: [],
                confidence: 0,
                
                async analyzeDescription() {
                    if (this.form.description.length < 20) {
                        this.suggestedDepts = [];
                        this.confidence = 0;
                        return;
                    }
                    
                    try {
                        const response = await fetch('api/categorize.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                description: this.form.description
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.suggestedDepts = data.data.suggested_departments;
                            this.confidence = data.data.confidence;
                            
                            // Auto-select top suggestion if confidence is high
                            if (this.confidence >= 70 && this.suggestedDepts.length > 0) {
                                const topDeptId = this.suggestedDepts[0].id;
                                const checkbox = document.querySelector(`input[name="departments[]"][value="${topDeptId}"]`);
                                if (checkbox && !checkbox.checked) {
                                    checkbox.checked = true;
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Categorization error:', error);
                    }
                },
                
                submitForm(e) {
                    // Form validation
                    const checkboxes = document.querySelectorAll('input[name="departments[]"]:checked');
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one department.');
                        return false;
                    }
                }
            }
        }
    </script>
</body>
</html>