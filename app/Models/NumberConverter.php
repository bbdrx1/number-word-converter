<?php

namespace App\Models;

use InvalidArgumentException;

class NumberConverter
{
    // Arrays for number words
    private $basicNumbers = [
        '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
        'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
        'seventeen', 'eighteen', 'nineteen'
    ];

    private $tensNumbers = [
        '', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'
    ];

    private $bigNumbers = ['', 'thousand', 'million', 'billion'];

    public function numberToWords($number)
    {
        // Validation
        if (!is_numeric($number)) {
            throw new InvalidArgumentException("Input must be a number");
        }

        // Handle zero
        if ($number == 0) {
            return 'zero';
        }

        // Handle negative numbers
        if ($number < 0) {
            return 'negative ' . $this->numberToWords(abs($number));
        }

        $result = '';
        $position = 0;

        // Process the number in groups of 3 digits
        while ($number > 0) {
            $group = $number % 1000;
            
            if ($group != 0) {
                $groupWords = $this->convertThreeDigits($group);
                if ($position > 0) {
                    $groupWords .= ' ' . $this->bigNumbers[$position];
                }
                
                if ($result != '') {
                    $result = $groupWords . ' ' . $result;
                } else {
                    $result = $groupWords;
                }
            }
            
            $number = intval($number / 1000);
            $position++;
        }

        return $result;
    }

    public function wordsToNumber($words)
    {
        // Clean up the input
        $words = $this->cleanUpWords($words);

        // Create lookup arrays
        $numberWords = [];
        
        // Add basic numbers (0-19)
        for ($i = 0; $i < count($this->basicNumbers); $i++) {
            if ($this->basicNumbers[$i] != '') {
                $numberWords[$this->basicNumbers[$i]] = $i;
            }
        }
        
        // Add tens (20, 30, 40, etc.)
        for ($i = 2; $i < count($this->tensNumbers); $i++) {
            if ($this->tensNumbers[$i] != '') {
                $numberWords[$this->tensNumbers[$i]] = $i * 10;
            }
        }
        
        // Add scale words
        $scaleWords = [
            'hundred' => 100,
            'thousand' => 1000,
            'million' => 1000000,
            'billion' => 1000000000
        ];

        // Split into individual words
        $wordArray = explode(' ', $words);
        $total = 0;
        $current = 0;

        foreach ($wordArray as $word) {
            // Skip "and"
            if ($word == 'and') {
                continue;
            }

            if (isset($numberWords[$word])) {
                // It's a basic number word
                $current += $numberWords[$word];
            } elseif (isset($scaleWords[$word])) {
                // It's a scale word
                if ($word == 'hundred') {
                    $current *= 100;
                } else {
                    $total += $current * $scaleWords[$word];
                    $current = 0;
                }
            } elseif ($word != '') {
                throw new InvalidArgumentException("Don't recognize this word: '$word'");
            }
        }

        return $total + $current;
    }

    // Convert a 3-digit group to words
    private function convertThreeDigits($number)
    {
        $result = '';
        $hundreds = intval($number / 100);
        $remainder = $number % 100;

        // Handle hundreds place
        if ($hundreds > 0) {
            $result = $this->basicNumbers[$hundreds] . ' hundred';
        }

        // Handle tens and ones places
        if ($remainder > 0) {
            if ($result != '') {
                $result .= ' and ';
            }
            
            if ($remainder < 20) {
                // Use basic numbers array for 1-19
                $result .= $this->basicNumbers[$remainder];
            } else {
                // Handle 20-99
                $tens = intval($remainder / 10);
                $ones = $remainder % 10;
                $result .= $this->tensNumbers[$tens];
                
                if ($ones > 0) {
                    $result .= ' ' . $this->basicNumbers[$ones];
                }
            }
        }

        return $result;
    }

    // Clean up the input words
    private function cleanUpWords($words)
    {
        // Convert to lowercase and trim
        $words = strtolower(trim($words));
        
        // Replace multiple spaces with single space
        $words = preg_replace('/\s+/', ' ', $words);

        // Handle compound words like "twentyone" -> "twenty one"
        // Get all tens words except empty ones
        $tensPattern = [];
        for ($i = 2; $i < count($this->tensNumbers); $i++) {
            if ($this->tensNumbers[$i] != '') {
                $tensPattern[] = $this->tensNumbers[$i];
            }
        }
        $tensRegex = implode('|', $tensPattern);
        
        // Get all basic number words except empty ones
        $onesPattern = [];
        for ($i = 1; $i < count($this->basicNumbers); $i++) {
            if ($this->basicNumbers[$i] != '') {
                $onesPattern[] = $this->basicNumbers[$i];
            }
        }
        $onesRegex = implode('|', $onesPattern);

        // Fix compound tens+ones (like "twentyone" -> "twenty one")
        $words = preg_replace('/\b(' . $tensRegex . ')(' . $onesRegex . ')\b/', '$1 $2', $words);

        // Fix compound number+scale (like "onehundred" -> "one hundred")
        $words = preg_replace('/\b(' . $onesRegex . ')(hundred|thousand|million|billion)\b/', '$1 $2', $words);

        return $words;
    }
}