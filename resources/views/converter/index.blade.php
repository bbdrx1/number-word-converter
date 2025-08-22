@extends('layouts.app')

@section('title', 'Number-Word Converter with Currency API')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <div class="converter-card">
            <div class="card-header">
                <h1>Number ↔ Word Converter</h1>
                <p class="mb-0 opacity-75">Convert numbers to words and vice versa with live currency conversion</p>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('converter.convert') }}" id="converter-form">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="conversion_type" class="form-label fw-bold">Conversion Type</label>
                            <select name="conversion_type" id="conversion_type" class="form-select" required>
                                <option value="number_to_words" {{ old('conversion_type', 'number_to_words') === 'number_to_words' ? 'selected' : '' }}>
                                    Number to Words
                                </option>
                                <option value="words_to_number" {{ old('conversion_type') === 'words_to_number' ? 'selected' : '' }}>
                                    Words to Number
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-8 mb-3">
                            <label for="input" class="form-label fw-bold">Input</label>
                            <input type="text" 
                                   name="input" 
                                   id="input" 
                                   class="form-control" 
                                   value="{{ old('input') }}" 
                                   placeholder="Enter number or words..." 
                                   required>
                            <div class="form-text">
                                <span id="input-hint">Enter a number (e.g., 390)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span class="loading spinner-border spinner-border-sm me-2" role="status"></span>
                            Convert Now
                        </button>
                    </div>
                </form>
                @if(isset($result))
                    <div class="result-card p-4 {{ $result['error'] ? 'error-card' : '' }}">
                        @if($result['error'])
                            <h5 class="mb-3"><i class="fas fa-exclamation-circle me-2"></i>Error</h5>
                            <p class="mb-0">{{ $result['error'] }}</p>
                        @else
                            <h5 class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i>Conversion Result</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Input:</strong><br>
                                    <span class="text-muted">{{ $result['input'] }}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Output:</strong><br>
                                    <span class="text-primary fw-bold">{{ $result['converted'] }}</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <strong>PHP Amount:</strong> 
                                <span class="badge bg-secondary">₱{{ number_format($result['php_amount'], 2) }}</span>
                            </div>
                            
                            @if($result['currency_data'])
                                <div class="currency-info p-3">
                                    <h6 class="mb-3">
                                        <i class="fas fa-dollar-sign me-2"></i>Currency Conversion
                                        <span class="badge badge-api bg-info ms-2">{{ $result['currency_data']['api_used'] ?? 'currencyconverterapi.com' }}</span>
                                    </h6>
                                    
                                    @if($result['currency_data']['success'])
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <strong>USD Amount:</strong><br>
                                                <span class="h5 text-success">${{ number_format($result['currency_data']['usd_amount'], 2) }}</span>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <strong>Exchange Rate:</strong><br>
                                                <span class="text-muted">1 PHP = {{ $result['currency_data']['exchange_rate'] }} USD</span>
                                            </div>
                                        </div>
                                        
                                        @if(isset($result['currency_data']['primary_error']))
                                            <div class="mt-2">
                                                <small class="text-warning">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Using fallback API: {{ $result['currency_data']['primary_error'] }}
                                                </small>
                                            </div>
                                        @endif
                                        
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Updated: {{ $result['currency_data']['timestamp']->format('M j, Y g:i A') }}
                                            </small>
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Currency conversion failed: {{ $result['currency_data']['error'] }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                @endif

                <div class="examples-card p-4">
                    <h6 class="mb-3"><i class="fas fa-lightbulb me-2"></i>Examples & Features</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">Number to Words:</h6>
                            <ul class="list-unstyled small">
                                <li><code>390</code> → <em>"three hundred and ninety"</em></li>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">Words to Number:</h6>
                            <ul class="list-unstyled small">
                                <li><em>"three hundred and ninety"</em> → <code>390</code></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-success">Error Correction:</h6>
                            <ul class="list-unstyled small">
                                <li><code>"onehundred and ten"</code> → <em>"one hundred and ten"</em> → <code>110</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $('#conversion_type').on('change', function() {
        const type = $(this).val();
        const inputField = $('#input');
        const hint = $('#input-hint');
        
        if (type === 'number_to_words') {
            inputField.attr('placeholder', 'Enter a number (e.g., 390)');
            hint.text('Enter a number (e.g., 390)');
        } else {
            inputField.attr('placeholder', 'Enter words (e.g., three hundred and ninety)');
            hint.text('Enter words (e.g., three hundred and ninety)');
        }
        
        // Clear validation states
        inputField.removeClass('is-valid is-invalid');
    });
    
    // Live input validation
    $('#input').on('input', function() {
        const type = $('#conversion_type').val();
        const value = $(this).val().trim();
        
        if (!value) {
            $(this).removeClass('is-valid is-invalid');
            return;
        }
        
        if (type === 'number_to_words') {
            if (/^\d+$/.test(value) && parseInt(value) <= 999999999999) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        } else {
            // Basic word validation
            if (/^[a-zA-Z\s]+$/.test(value)) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        }
    });
    $(document).ready(function() {
        $('#conversion_type').trigger('change');
    });
</script>
@endsection