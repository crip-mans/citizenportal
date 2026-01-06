<?php
// ============================================
// AI CATEGORIZATION ENGINE
// ============================================

class AICategorizer {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Analyze complaint description and suggest departments
     */
    public function categorizeConcern($description) {
        if (strlen($description) < 20) {
            return ['suggested_departments' => [], 'confidence' => 0];
        }
        
        // Convert to lowercase for matching
        $text = strtolower($description);
        
        // Get all keywords with their departments and weights
        $stmt = $this->db->prepare("
            SELECT 
                ai_keywords.keyword,
                ai_keywords.weight,
                ai_keywords.department_id,
                departments.name as department_name
            FROM ai_keywords
            INNER JOIN departments ON ai_keywords.department_id = departments.id
            WHERE departments.is_active = 1
        ");
        $stmt->execute();
        $keywords = $stmt->fetchAll();
        
        // Calculate scores for each department
        $scores = [];
        foreach ($keywords as $kw) {
            if (stripos($text, $kw['keyword']) !== false) {
                $dept_id = $kw['department_id'];
                if (!isset($scores[$dept_id])) {
                    $scores[$dept_id] = [
                        'name' => $kw['department_name'],
                        'score' => 0,
                        'id' => $dept_id
                    ];
                }
                // Add weighted score
                $scores[$dept_id]['score'] += $kw['weight'];
            }
        }
        
        // Sort by score descending
        uasort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Get top departments
        $suggested = array_slice(array_values($scores), 0, 3);
        
        // Calculate confidence (0-100)
        $confidence = 0;
        if (!empty($suggested)) {
            $max_score = $suggested[0]['score'];
            $confidence = min(100, ($max_score / 5) * 100); // Normalize to 100
        }
        
        return [
            'suggested_departments' => $suggested,
            'confidence' => round($confidence)
        ];
    }
    
    /**
     * Get all active departments
     */
    public function getAllDepartments() {
        $stmt = $this->db->prepare("
            SELECT id, name, description 
            FROM departments 
            WHERE is_active = 1 
            ORDER BY name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Log categorization for analytics
     */
    public function logCategorization($ticketId, $description, $suggestedDepts, $selectedDepts) {
        // This can be used for improving the AI over time
        // Store in a separate analytics table if needed
        return true;
    }
}

// ============================================
// SMART Q&A ENGINE
// ============================================

class SmartQA {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Search FAQ knowledge base
     */
    public function searchAnswer($query) {
        if (strlen($query) < 3) {
            return null;
        }
        
        $query_lower = strtolower($query);
        
        // Try fulltext search first
        $stmt = $this->db->prepare("
            SELECT 
                faq.id,
                faq.question,
                faq.answer,
                faq.related_departments,
                MATCH(keywords, question, answer) AGAINST (? IN NATURAL LANGUAGE MODE) as relevance
            FROM faq_knowledge faq
            WHERE faq.is_active = 1
            AND MATCH(keywords, question, answer) AGAINST (? IN NATURAL LANGUAGE MODE)
            ORDER BY relevance DESC
            LIMIT 1
        ");
        $stmt->execute([$query, $query]);
        $result = $stmt->fetch();
        
        // If fulltext didn't find anything, try keyword matching
        if (!$result) {
            $stmt = $this->db->prepare("
                SELECT 
                    faq.id,
                    faq.question,
                    faq.answer,
                    faq.related_departments
                FROM faq_knowledge faq
                WHERE faq.is_active = 1
                AND (
                    LOWER(faq.keywords) LIKE ? OR
                    LOWER(faq.question) LIKE ? OR
                    LOWER(faq.answer) LIKE ?
                )
                LIMIT 1
            ");
            $search_term = "%{$query_lower}%";
            $stmt->execute([$search_term, $search_term, $search_term]);
            $result = $stmt->fetch();
        }
        
        if ($result) {
            // Increment view count
            $update = $this->db->prepare("UPDATE faq_knowledge SET view_count = view_count + 1 WHERE id = ?");
            $update->execute([$result['id']]);
            
            // Get department names
            if ($result['related_departments']) {
                $dept_ids = json_decode($result['related_departments'], true);
                if (is_array($dept_ids) && !empty($dept_ids)) {
                    $placeholders = implode(',', array_fill(0, count($dept_ids), '?'));
                    $dept_stmt = $this->db->prepare("SELECT name FROM departments WHERE id IN ($placeholders)");
                    $dept_stmt->execute($dept_ids);
                    $result['department_names'] = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            }
            
            return $result;
        }
        
        return null;
    }
    
    /**
     * Log chat interaction
     */
    public function logChat($citizenId, $query, $response, $faqMatched = null) {
        $stmt = $this->db->prepare("
            INSERT INTO chat_logs (citizen_id, query, response, faq_matched)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$citizenId, $query, $response, $faqMatched]);
    }
    
    /**
     * Get popular FAQs
     */
    public function getPopularFAQs($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT question, answer, view_count
            FROM faq_knowledge
            WHERE is_active = 1
            ORDER BY view_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}