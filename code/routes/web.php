<?php

Auth::routes();

Route::get('ordini.xml', 'OrdersController@rss')->name('rss');
Route::get('ordini.ics', 'OrdersController@ical');

Route::get('gas/{id}/logo', 'GasController@getLogo');

Route::get('payment/status/paypal', 'PaymentController@statusPaymentPaypal')->name('payment.status_paypal');
Route::get('payment/status/satispay', 'PaymentController@statusPaymentSatispay')->name('payment.status_satispay');

Route::post('mail/status', 'MailController@postStatus');

Route::middleware(['auth'])->group(function() {
    Route::get('/', function () {
        Session::reflash();
        return Redirect::to('/dashboard');
    });

    Route::get('/home', function () {
        return Redirect::to('/dashboard');
    });

    Route::get('dashboard', 'CommonsController@getIndex')->name('dashboard');
    Route::post('dashboard/verify', 'CommonsController@postVerify');

    Route::get('gas/{id}/header', 'GasController@objhead')->name('gas.objhead');
    Route::get('gas/dumpdb', 'GasController@databaseDump')->name('gas.dumpdb');

    Route::get('users/ro/{id}', 'UsersController@show_ro');
    Route::get('users/{id}/header', 'UsersController@objhead')->name('users.objhead');
    Route::get('users/search', 'UsersController@search')->name('users.search');
    Route::get('users/searchorders/{id}', 'UsersController@searchOrders')->name('users.orders');
    Route::get('users/profile', 'UsersController@profile')->name('profile');
    Route::get('users/picture/{id}', 'UsersController@picture');
    Route::get('users/export', 'UsersController@export');
    Route::post('users/notifications/{id}', 'UsersController@notifications')->name('users.notifications');
    Route::get('users/fees', 'UsersController@fees')->name('users.fees');
    Route::post('users/fees', 'UsersController@feesSave')->name('users.savefees');
    Route::get('users/password', 'UsersController@changePassword')->name('users.password');

    Route::get('friends/{id}/header', 'FriendsController@objhead')->name('friends.objhead');

    Route::get('roles/{id}/header', 'RolesController@objhead')->name('roles.objhead');
    Route::get('roles/user/{user_id}', 'RolesController@formByUser');
    Route::get('roles/supplier/{supplier_id}', 'RolesController@formBySupplier');
    Route::post('roles/attach', 'RolesController@attach');
    Route::post('roles/detach', 'RolesController@detach');

    Route::get('suppliers/ro/{id}', 'SuppliersController@show_ro');
    Route::get('suppliers/{id}/header', 'SuppliersController@objhead')->name('suppliers.objhead');
    Route::get('suppliers/catalogue/{id}/{format?}', 'SuppliersController@catalogue');
    Route::get('suppliers/{id}/products', 'SuppliersController@productsDetails');
    Route::get('suppliers/{id}/products_grid', 'SuppliersController@productsGrid');
    Route::get('suppliers/{id}/invoicedata', 'SuppliersController@invoiceData')->name('suppliers.invoicedata');
    Route::get('suppliers/{id}/plain_balance', 'SuppliersController@plainBalance');

    Route::get('products/ro/{id}', 'ProductsController@show_ro');
    Route::get('products/duplicate/{id}', 'ProductsController@duplicate')->name('products.duplicate');
    Route::get('products/{id}/header', 'ProductsController@objhead')->name('products.objhead');
    Route::post('products/massiveupdate', 'ProductsController@massiveUpdate');
    Route::get('products/picture/{id}', 'ProductsController@picture');

    Route::get('vatrates/{id}/header', 'VatRatesController@objhead')->name('vatrates.objhead');

    Route::get('invoices/{id}/products', 'InvoicesController@products')->name('invoices.products');
    Route::get('invoices/{id}/movements', 'InvoicesController@getMovements')->name('invoices.movements');
    Route::post('invoices/{id}/movements', 'InvoicesController@postMovements')->name('invoices.savemovements');
    Route::post('invoices/wire/{step}/{id}', 'InvoicesController@wiring');
    Route::get('invoices/search', 'InvoicesController@search')->name('invoices.search');
    Route::get('invoices/{id}/header', 'InvoicesController@objhead');

    Route::get('receipts/{id}/header', 'ReceiptsController@objhead')->name('receipts.objhead');
    Route::get('receipts/{id}/download', 'ReceiptsController@download')->name('receipts.download');

    Route::get('categories/{id}/header', 'CategoriesController@objhead')->name('categories.objhead');

    Route::get('measures/{id}/header', 'MeasuresController@objhead')->name('measures.objhead');
    Route::get('measures/list/{id}', 'MeasuresController@listProducts');

    Route::get('variants/{id}/header', 'VariantsController@objhead')->name('variants.objhead');

    Route::get('orders/{id}/header', 'OrdersController@objhead')->name('orders.objhead');
    Route::get('orders/search', 'OrdersController@search');
    Route::post('orders/recalculate/{id}', 'OrdersController@recalculate');
    Route::get('orders/fixes/{id}/{product_id}', 'OrdersController@getFixes');
    Route::post('orders/fixes/{id}', 'OrdersController@postFixes');
    Route::get('orders/document/{id}/{type}', 'OrdersController@document')->name('orders.document');

    Route::get('dates/query', 'DatesController@query');
    Route::get('dates/orders', 'DatesController@orders')->name('dates.orders');
    Route::post('dates/orders', 'DatesController@updateOrders')->name('dates.updateorders');

    Route::get('aggregates/{id}/header', 'AggregatesController@objhead')->name('aggregates.objhead');
    Route::get('aggregates/notify/test/{id}', 'AggregatesController@testNotify');
    Route::post('aggregates/notify/{id}', 'AggregatesController@notify');
    Route::get('aggregates/export/{id}/{type}', 'AggregatesController@exportModal')->name('aggregates.export');
    Route::get('aggregates/document/{id}/{type}', 'AggregatesController@document')->name('aggregates.document');
    Route::get('aggregates/{id}/details', 'AggregatesController@details')->name('aggregates.details');
    Route::get('aggregates/{id}/multigas', 'AggregatesController@multiGAS')->name('aggregates.multigas');

    Route::get('attachments/{id}/header', 'AttachmentsController@objhead')->name('attachments.objhead');
    Route::get('attachments/download/{id}', 'AttachmentsController@download');

    Route::get('bookings/{id}/header', 'BookingController@objhead')->name('bookings.objhead');
    Route::get('booking/{aggregate_id}/user/{user_id}/header', 'BookingUserController@objhead2');
    Route::get('booking/{aggregate_id}/user/{user_id}/document', 'BookingUserController@document');
    Route::get('booking/{id}/header', 'BookingController@objhead')->name('booking.objhead');

    Route::get('deliveries/{id}/header', 'DeliveriesController@objhead')->name('deliveries.objhead');
    Route::get('delivery/{aggregate_id}/user/{user_id}/header', 'DeliveryUserController@objhead2');
    Route::get('deliveries/{aggregate_id}/fast', 'DeliveryUserController@getFastShipping');
    Route::post('deliveries/{aggregate_id}/fast', 'DeliveryUserController@postFastShipping');

    Route::get('notifications/{id}/header', 'NotificationsController@objhead')->name('notifications.objhead');
    Route::get('notificatios/search', 'NotificationsController@search')->name('notifications.search');
    Route::post('notifications/markread/{id}', 'NotificationsController@markread');

    Route::post('multigas/attach', 'MultiGasController@attach');
    Route::post('multigas/detach', 'MultiGasController@detach');

    Route::post('payment/do', 'PaymentController@doPayment')->name('payment.do');

    Route::get('movements/ro/{id}', 'MovementsController@show_ro');
    Route::get('movements/{id}/header', 'MovementsController@objhead')->name('movements.objhead');
    Route::get('movtypes/{id}/header', 'MovementTypesController@objhead')->name('movtypes.objhead');
    Route::get('movements/showcredits', 'MovementsController@creditsTable');
    Route::get('movements/showsuppliers', 'MovementsController@suppliersTable');
    Route::get('movements/balance', 'MovementsController@getBalance');
    Route::post('movements/recalculate', 'MovementsController@recalculate');
    Route::post('movements/close', 'MovementsController@closeBalance');
    Route::post('movements/deletebalance/{id}', 'MovementsController@deleteBalance')->name('movements.deletebalance');
    Route::get('movements/document/{type}/{subtype?}', 'MovementsController@document');

    Route::get('import/esmodal', 'ImportController@esModal')->name('import.esmodal');
    Route::post('import/csv', 'ImportController@postCsv');
    Route::get('import/gdxp', 'ImportController@getGdxp');
    Route::post('import/gdxp', 'ImportController@postGdxp');
    Route::get('import/legacy', 'ImportController@getLegacy');
    Route::post('import/legacy', 'ImportController@postLegacy');

    Route::resource('gas', 'GasController');
    Route::resource('multigas', 'MultiGasController');
    Route::resource('users', 'UsersController');
    Route::resource('friends', 'FriendsController');
    Route::resource('roles', 'RolesController');
    Route::resource('suppliers', 'SuppliersController');
    Route::resource('products', 'ProductsController');
    Route::resource('vatrates', 'VatRatesController');
    Route::resource('invoices', 'InvoicesController');
    Route::resource('receipts', 'ReceiptsController');
    Route::resource('deliveries', 'DeliveriesController');
    Route::resource('categories', 'CategoriesController');
    Route::resource('measures', 'MeasuresController');
    Route::resource('variants', 'VariantsController');
    Route::resource('orders', 'OrdersController');
    Route::resource('dates', 'DatesController');
    Route::resource('aggregates', 'AggregatesController');
    Route::resource('attachments', 'AttachmentsController');
    Route::resource('booking.user', 'BookingUserController');
    Route::resource('delivery.user', 'DeliveryUserController');
    Route::resource('booking', 'BookingController');
    Route::resource('bookings', 'BookingController');
    Route::resource('notifications', 'NotificationsController');
    Route::resource('movements', 'MovementsController');
    Route::resource('movtypes', 'MovementTypesController');
    Route::resource('stats', 'StatisticsController');
});
