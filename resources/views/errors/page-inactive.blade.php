@extends('errors::minimal')

@section('title', __('Page Inactive'))
@section('code', '404')
@section('message')
    <div class="error-content">
        <h2>Page Currently Unavailable</h2>
        <p>The page you're looking for is temporarily inactive or under maintenance.</p>
        <p>Please check back later or contact support if you need immediate assistance.</p>
        
        <div class="error-actions mt-4">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="fas fa-home"></i> Return Home
            </a>
            <a href="{{ url('/contact') }}" class="btn btn-outline-secondary">
                <i class="fas fa-envelope"></i> Contact Support
            </a>
        </div>
    </div>
@endsection

@push('styles')
<style>
.error-content {
    text-align: center;
    padding: 2rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #4285f4, #34a853);
    color: white;
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
    text-decoration: none;
    color: white;
}

.btn-outline-secondary {
    background: transparent;
    color: #6c757d;
    border: 1px solid #6c757d;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 200px;
        justify-content: center;
    }
}
</style>
@endpush