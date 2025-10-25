<style>
/* Mobile-first responsive design for profile forms */
@media (max-width: 640px) {
    /* Container adjustments */
    .max-w-4xl {
        max-width: 100%;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    /* Form spacing */
    .space-y-6 > * + * {
        margin-top: 1rem;
    }
    
    /* Grid adjustments for mobile */
    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    /* Profile picture size adjustment */
    .w-32.h-32 {
        width: 6rem;
        height: 6rem;
    }
    
    /* Button adjustments */
    .flex.justify-end.space-x-4 {
        flex-direction: column;
        space-x: 0;
        gap: 0.75rem;
    }
    
    .flex.justify-end.space-x-4 button,
    .flex.justify-end.space-x-4 a {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
    
    /* Tab navigation */
    .flex.space-x-8 {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .flex.space-x-8 button {
        flex: 1;
        min-width: 0;
        text-align: center;
        font-size: 0.875rem;
        padding: 0.75rem 0.5rem;
    }
    
    /* Table responsive */
    .grid.grid-cols-4 {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .grid.grid-cols-4 > div:first-child::before {
        content: "Type: ";
        font-weight: 600;
        color: #374151;
    }
    
    .grid.grid-cols-4 > div:nth-child(2)::before {
        content: "Label: ";
        font-weight: 600;
        color: #374151;
    }
    
    .grid.grid-cols-4 > div:nth-child(3)::before {
        content: "Address: ";
        font-weight: 600;
        color: #374151;
    }
    
    /* Hide table header on mobile */
    .bg-gray-50.px-6.py-3 {
        display: none;
    }
    
    /* Mobile card style for addresses */
    .divide-y.divide-gray-200 > div {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        padding: 1rem;
    }
    
    /* Country code dropdown adjustments */
    .flex select {
        min-width: 80px;
        font-size: 0.875rem;
    }
    
    /* Input adjustments */
    input, select, textarea {
        font-size: 16px; /* Prevents zoom on iOS */
    }
}

@media (max-width: 480px) {
    /* Extra small screens */
    .px-4.sm\:px-6.lg\:px-8 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .p-8 {
        padding: 1rem;
    }
    
    .text-2xl {
        font-size: 1.5rem;
    }
    
    .py-8 {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
}

/* Tablet adjustments */
@media (min-width: 641px) and (max-width: 1024px) {
    .max-w-4xl {
        max-width: 90%;
    }
    
    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
}

/* Focus states for better accessibility */
input:focus,
select:focus,
textarea:focus,
button:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Success/Error message styles */
.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d1fae5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert-error {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

/* Smooth transitions */
* {
    transition: all 0.2s ease-in-out;
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>