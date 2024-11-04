jQuery(document).ready(function($) {
    $('.accordion-row').on('click', function() {
        var date = $(this).data('date');
        $(this).next('.accordion-content').toggle();
    })
});