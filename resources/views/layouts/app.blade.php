<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Number-Word Converter')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .converter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .card-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .card-body {
            padding: 3rem;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: transform 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .result-card {
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-top: 2rem;
        }
        
        .error-card {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .currency-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .examples-card {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .badge-api {
            font-size: 0.7rem;
            padding: 0.4rem 0.6rem;
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .card-header h1 {
                font-size: 2rem;
            }
            
            .card-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <div class="main-container">
        <div class="container">
            @yield('content')
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for AJAX functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // CSRF token setup for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // Form validation
        $(document).ready(function() {
            $('#converter-form').on('submit', function() {
                const submitBtn = $(this).find('button[type="submit"]');
                const loading = submitBtn.find('.loading');
                
                submitBtn.prop('disabled', true);
                loading.addClass('show');
                
                setTimeout(function() {
                    submitBtn.prop('disabled', false);
                    loading.removeClass('show');
                }, 5000);
            });
            
            // Input validation
            $('#input').on('input', function() {
                const conversionType = $('#conversion_type').val();
                const value = $(this).val();
                
                if (conversionType === 'number_to_words' && value && !/^\d+$/.test(value)) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>