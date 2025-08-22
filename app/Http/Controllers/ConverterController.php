<?php

namespace App\Http\Controllers;

use App\Models\NumberConverter;
use App\Models\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConverterController extends Controller
{
    private $numberConverter;
    private $currencyConverter;

    public function __construct()
    {
        $this->numberConverter = new NumberConverter();
        $this->currencyConverter = new CurrencyConverter();
    }

    public function index()
    {
        return view('converter.index');
    }

    public function convert(Request $request)
    {
        // Basic validation
        $validator = Validator::make($request->all(), [
            'input' => 'required|string|max:1000',
            'conversion_type' => 'required|in:number_to_words,words_to_number'
        ]);

        // Check for specific input errors
        $input = trim($request->input('input'));
        $type = $request->input('conversion_type');
        
        if ($type == 'number_to_words') {
            // Check if user entered letters instead of numbers
            if (preg_match('/[a-zA-Z]/', $input)) {
                $validator->errors()->add('input', 'For number to words conversion, please enter only numbers (like 123, not "one two three")');
            }
            // Check for weird characters
            if (preg_match('/[^0-9\s\-\+\.]/', $input)) {
                $validator->errors()->add('input', 'Please use only numbers and minus sign. No special characters allowed.');
            }
            // Check if actually a number
            $cleanNumber = str_replace(' ', '', $input);
            if (!is_numeric($cleanNumber)) {
                $validator->errors()->add('input', 'This doesn\'t look like a valid number. Try something like: 123 or 1000');
            }
            // Don't allow decimals for now
            if (strpos($cleanNumber, '.') !== false) {
                $validator->errors()->add('input', 'Sorry, decimal numbers are not supported yet. Please enter whole numbers only.');
            }
            // Check size
            if (is_numeric($cleanNumber)) {
                $num = intval($cleanNumber);
                if ($num < -999999999999 || $num > 999999999999) {
                    $validator->errors()->add('input', 'Number is too big! Please enter a number between -999,999,999,999 and 999,999,999,999');
                }
            }
        } else {
            // words_to_number validation
            // Check if user entered only numbers instead of words
            if (preg_match('/^[\d\s\-\+\.]+$/', trim($input))) {
                $validator->errors()->add('input', 'For words to number conversion, please enter words like "twenty five" instead of numbers like "25"');
            }
            // Check if empty
            if (empty(trim($input))) {
                $validator->errors()->add('input', 'Please enter some words like "twenty five" or "one hundred"');
            }
            // Check for weird characters
            if (preg_match('/[^\w\s\-]/', $input)) {
                $validator->errors()->add('input', 'Please use only letters, spaces, and hyphens. Example: "twenty-one" or "one hundred"');
            }
            // Check if too short
            if (strlen(trim($input)) < 2) {
                $validator->errors()->add('input', 'Please enter complete words like "ten" or "fifty"');
            }
        }

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the errors below and try again.');
        }

        try {
            $result = $this->doConversion($input, $type);
            
            return view('converter.index', compact('result'))
                ->withInput($request->only(['input', 'conversion_type']));

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    private function doConversion($input, $type)
    {
        $result = [
            'input' => $input,
            'type' => $type,
            'converted' => '',
            'php_amount' => 0,
            'currency_data' => null,
            'error' => null,
            'timestamp' => now()
        ];

        try {
            if ($type == 'number_to_words') {
                // Convert number to words
                $cleanNumber = str_replace(' ', '', $input);
                
                if (!is_numeric($cleanNumber)) {
                    throw new \Exception('Please enter a valid number');
                }

                $number = intval($cleanNumber);
                
                // Check range again just to be safe
                if ($number < -999999999999 || $number > 999999999999) {
                    throw new \Exception('Number is too big to convert');
                }

                $result['converted'] = $this->numberConverter->numberToWords($number);
                $result['php_amount'] = abs($number); // Use positive value for currency

            } else {
                // Convert words to number
                $words = trim($input);
                
                if (empty($words)) {
                    throw new \Exception('Please enter some words to convert');
                }

                // Try to convert
                try {
                    $number = $this->numberConverter->wordsToNumber($words);
                } catch (\InvalidArgumentException $e) {
                    throw new \Exception('I couldn\'t understand some of those words. Please use words like "one", "twenty", "hundred", etc. Error: ' . $e->getMessage());
                }
                
                // Check if result makes sense
                if ($number == 0 && !$this->isUserTryingToSayZero($words)) {
                    throw new \Exception('I couldn\'t convert those words to a number. Please try using standard number words like "twenty five" or "one hundred"');
                }

                if ($number < 0) {
                    throw new \Exception('Negative numbers in words are not supported yet. Please use positive numbers.');
                }

                $result['converted'] = number_format($number);
                $result['php_amount'] = $number;
            }

            // Try to get USD conversion if we have PHP amount
            if ($result['php_amount'] > 0) {
                try {
                    $result['currency_data'] = $this->currencyConverter->convertToUSD($result['php_amount']);
                } catch (\Exception $e) {
                    // Don't break everything if currency fails
                    $result['currency_data'] = [
                        'success' => false,
                        'error' => 'Currency conversion not available right now: ' . $e->getMessage()
                    ];
                }
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            throw $e;
        }

        return $result;
    }

    // Check if user is trying to say zero
    private function isUserTryingToSayZero($words)
    {
        $zeroWords = ['zero', 'nothing', 'none', 'null', 'empty'];
        $cleanWords = strtolower(trim($words));
        
        foreach ($zeroWords as $zeroWord) {
            if ($cleanWords == $zeroWord) {
                return true;
            }
        }
        
        return false;
    }

    // API endpoint for AJAX calls
    public function apiConvert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'input' => 'required|string|max:1000',
            'conversion_type' => 'required|in:number_to_words,words_to_number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Input validation failed'
            ], 422);
        }

        try {
            $result = $this->doConversion(
                $request->input('input'), 
                $request->input('conversion_type')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Conversion completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Conversion failed'
            ], 400);
        }
    }

    // Get currencies list
    public function getCurrencies()
    {
        try {
            $currencies = $this->currencyConverter->getSupportedCurrencies();
            
            return response()->json([
                'success' => true,
                'data' => $currencies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Could not get currencies: ' . $e->getMessage()
            ], 500);
        }
    }
}