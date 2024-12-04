<?php

// Function to map final average to rating and description
function getFinalRating($finalAverage) {
    if ($finalAverage === null) {
        return ['rating' => '', 'description' => 'No rating available'];
    } elseif ($finalAverage >= 4.20) {
        return ['rating' => 'O', 'description' => 'Outstanding'];
    } elseif ($finalAverage >= 3.40) {
        return ['rating' => 'VS', 'description' => 'Very Satisfactory'];
    } elseif ($finalAverage >= 2.60) {
        return ['rating' => 'S', 'description' => 'Satisfactory'];
    } elseif ($finalAverage >= 1.80) {
        return ['rating' => 'U', 'description' => 'Unsatisfactory'];
    } else {
        return ['rating' => 'P', 'description' => 'Poor'];
    }
}

// Function to map final average to rating and description
function getRatingColor($rating) {
    if ($rating >= 4.20) {
        return '#06BA63';
    } elseif ($rating >= 3.40) {
        return '#A4C639';
    } elseif ($rating >= 2.60) {
        return '#F8D351';
    } elseif ($rating >= 1.80) {
        return '#FF6347';
    } else {
        return '#F48282';
    }
}
?>