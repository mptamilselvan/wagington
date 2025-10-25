{{-- Dashboard Navigation Scripts --}}
<script>
    // Global functions for opening/closing navigation
    window.openDashboard = function() {
        // Open the dashboard slider (used on all customer pages)
        const slider = document.getElementById('dashboard-slider');
        const overlay = document.getElementById('dashboard-slider-overlay');
        if (slider) {
            slider.style.display = 'block';
            slider.classList.remove('hidden');
            if (overlay) {
                overlay.style.display = 'block';
                overlay.classList.remove('hidden');
            }
            return;
        }
    }
    
    window.closeDashboardSidebar = function() {
        // Legacy function - now redirects to slider close
        window.closeDashboardSlider();
    }
    
    window.closeDashboardSlider = function() {
        const slider = document.getElementById('dashboard-slider');
        const overlay = document.getElementById('dashboard-slider-overlay');
        if (slider) {
            slider.style.display = 'none';
            slider.classList.add('hidden');
            if (overlay) {
                overlay.style.display = 'none';
                overlay.classList.add('hidden');
            }
        }
    }
</script>