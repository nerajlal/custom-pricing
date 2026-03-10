{{-- Script disabled in favor of unified custom-price-script.blade.php --}}
<script>
  console.log('⚠️ Legacy collection-price-script disabled (User Revert Override)');
  // Ensure we don't block anything if this accidentally runs
  if (document.getElementById('metora-initial-hide')) {
      document.getElementById('metora-initial-hide').remove();
  }
</script>
