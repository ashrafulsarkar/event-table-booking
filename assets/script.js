jQuery(document).ready(function($) {
    // Load tables on page load
    function loadTables() {
        $.post(ticketBookingAjax.ajax_url, { action: 'load_tables' }, function(response) {
            if (response.success) {
                $('#table-grid').html(response.data);
                setupGridLayout();
            } else {
                $('#table-grid').html('<p>' + response.data + '</p>');
            }
        });
    }

    loadTables();

    function setupGridLayout() {
        const tableCount = document.querySelectorAll('#table-grid .table').length;
        if (tableCount > 36) {
            $('#table-grid').css('grid-template-columns', 'repeat(13, 1fr)');
        } else {
            $('#table-grid').css('grid-template-columns', 'repeat(12, 1fr)');
        }
    }


    let basePrice = 0;

    // Open booking popup with updated content
    $(document).on('click', '.table-available', function () {
        const tableNumber = $(this).data('table-number');
        const seatCapacity = $(this).data('seat-capacity');
        const price = $(this).data('price');
        const type = $(this).data('type');
        const isIndividual = $(this).data('individual') === true;

        $('#popup-table-number').text(tableNumber);
        $('#hidden-table-type').val(type);
        basePrice = parseFloat(price);

        if (isIndividual) {
            // Show individual quantity selector
            $('#seat-quantity-wrapper').show();
            $('#popup-seat-capacity').hide();
            $('#seat-quantity').val(1);
            $('#popup-price').text((basePrice * 1).toFixed(2));
            $('#seat-quantity').attr('max', seatCapacity ); //seatCapacity
        } else {
            // Default for full/half tables
            $('#seat-quantity-wrapper').hide();
            $('#popup-seat-capacity').show().find('span').text(seatCapacity);
            $('#popup-price').text((basePrice * 1).toFixed(2));
        }

        $('#booking-popup').fadeIn();
    });

    // Update price based on seat quantity
    $('#increase-seat').on('click', function () {
        let currentQuantity = parseInt($('#seat-quantity').val(), 10);
        if (currentQuantity < $('#seat-quantity').attr('max')) {
            currentQuantity++;
            $('#seat-quantity').val(currentQuantity);
            $('#popup-price').text((basePrice * currentQuantity).toFixed(2));
        }
    });

    $('#decrease-seat').on('click', function () {
        let currentQuantity = parseInt($('#seat-quantity').val(), 10);
        if (currentQuantity > 1) {
            currentQuantity--;
            $('#seat-quantity').val(currentQuantity);
            $('#popup-price').text((basePrice * currentQuantity).toFixed(2));
        }
    });

    // Close booking popup
    $('#close-popup').on('click', function () {
        $('#booking-popup').fadeOut();
    });




    $('#continue-to-payment').on('click', function () {
        // e.preventDefault();
        // Retrieve table data
        const tableNumber = $('#popup-table-number').text();

        let seatQuantity = $('#seat-quantity').val();
        if (!seatQuantity) {
            seatQuantity = $('#popup-seat-capacity span').text();
        }

        const price = $('#popup-price').text();
    
        // Populate form fields
        $('#hidden-table-number').val(tableNumber);
        $('#hidden-seat-quantity').val(seatQuantity);
        $('#hidden-price').val(price);
    
        // Submit the form
        $('#booking-form').submit();
    });

    
});
