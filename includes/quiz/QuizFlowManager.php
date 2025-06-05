<?php
/**
 * Quiz Flow Manager
 * 
 * Manages the quiz flow, including loading correct answers
 * and controlling UI behavior based on answer validation.
 */

class Lilac_QuizFlowManager {
    /**
     * Instance of the CorrectAnswerService
     * 
     * @var Lilac_CorrectAnswerService
     */
    private $answer_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once dirname(__FILE__) . '/CorrectAnswerService.php';
        $this->answer_service = new Lilac_CorrectAnswerService();
        
        // Register hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Initialize the quiz flow functionality
     */
    public function init() {
        // Only apply to quiz pages
        if (!is_singular('sfwd-quiz')) {
            return;
        }
        
        $quiz_id = get_the_ID();
        $enforce_hint = get_post_meta($quiz_id, 'quiz_enforce_hint', true);
        
        // Only apply to quizzes with enforce hint enabled
        if (!$enforce_hint) {
            return;
        }
        
        // Get all correct answers for this quiz
        $answers = $this->answer_service->get_quiz_correct_answers($quiz_id);
        
        // Pass correct answers to JavaScript
        wp_localize_script('lilac-quiz-answer-flow', 'lilacQuizData', array(
            'correctAnswers' => $answers,
            'enforceHint' => true
        ));
    }
    
    /**
     * Enqueue scripts and styles for quiz answer flow
     */
    public function enqueue_scripts() {
        // Only enqueue on quiz pages
        if (!is_singular('sfwd-quiz')) {
            return;
        }
        
        $quiz_id = get_the_ID();
        $enforce_hint = get_post_meta($quiz_id, 'quiz_enforce_hint', true);
        
        // Only apply to quizzes with enforce hint enabled
        if (!$enforce_hint) {
            return;
        }
        
        wp_enqueue_script(
            'lilac-quiz-answer-flow',
            get_stylesheet_directory_uri() . '/includes/quiz/assets/js/quiz-answer-flow.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/includes/quiz/assets/js/quiz-answer-flow.js'),
            true
        );
        
        // Initialize the flow by providing correct answers
        $this->init();
    }
}
