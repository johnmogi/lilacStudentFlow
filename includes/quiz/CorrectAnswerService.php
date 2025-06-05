<?php
/**
 * Correct Answer Service
 * 
 * Handles retrieving correct answers for LearnDash quiz questions
 * in a reliable, testable way without DOM parsing or guesswork.
 */

class Lilac_CorrectAnswerService {
    /**
     * Get all correct answers for a quiz
     *
     * @param int $quiz_id The quiz post ID
     * @return array Associative array of question IDs to correct answer values
     */
    public function get_quiz_correct_answers($quiz_id = null) {
        if (!$quiz_id) {
            // Get current quiz ID if not provided
            global $post;
            if (!$post || $post->post_type !== 'sfwd-quiz') {
                return array();
            }
            $quiz_id = $post->ID;
        }
        
        $answer_map = array();
        
        // Get all questions for this quiz using LearnDash's API
        $questions = learndash_get_quiz_questions($quiz_id);
        
        if (!empty($questions)) {
            foreach ($questions as $question) {
                $question_id = $question->ID;
                $question_pro_id = get_post_meta($question_id, 'question_pro_id', true);
                $question_type = get_post_meta($question_id, 'question_type', true);
                
                // Get question data from the database
                global $wpdb;
                $question_data = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wp_pro_quiz_question WHERE id = %d",
                        $question_pro_id
                    )
                );
                
                if ($question_data) {
                    // Extract correct answer data based on question type
                    $correct_answer = '';
                    
                    // Get answer data
                    $answer_data = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}wp_pro_quiz_answer WHERE question_id = %d ORDER BY sort ASC",
                            $question_pro_id
                        )
                    );
                    
                    if (!empty($answer_data)) {
                        // For single choice questions
                        if ($question_type === 'single') {
                            foreach ($answer_data as $index => $answer) {
                                if ($answer->correct) {
                                    // Add 1 because LearnDash uses 1-indexed values in the frontend
                                    $correct_answer = (string)($index + 1); 
                                    break;
                                }
                            }
                        }
                        // Add other question types here as needed
                    }
                    
                    // Store the correct answer for this question
                    if (!empty($correct_answer)) {
                        $answer_map[$question_id] = $correct_answer;
                    }
                }
            }
        }
        
        return $answer_map;
    }
}
