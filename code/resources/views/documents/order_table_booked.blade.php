@include('documents.order_table_master', [
    'order' => $order,
    'selected_bookings' => $bookings,
    'get_function' => 'getBookedQuantity',
    'get_total' => 'booked',
    'with_friends' => true,
    'get_function_real' => true
])
