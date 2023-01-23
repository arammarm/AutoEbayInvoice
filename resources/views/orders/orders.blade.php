@extends('adminlte::page')

@section('title', 'Orders')

@section('content_header')
    <h1>Orders</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col">

                    <div class="d-flex justify-content-end">
                        <div class="">
                            <button onclick="downloadOrderAjax(this)" class="btn btn-outline-primary">
                                <div id="downloadOrderLoader" class="spinner-grow spinner-grow-sm text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div> &nbsp;
                                Update Orders
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col">

                    @if(session()->has('error'))
                        <div
                            class="alert alert-@if(session()->get('error') == 1){{'danger'}}@else{{'success'}}@endif text-sm mx-2">{{session()->get('message')}}</div>
                    @endif

                    <table id="orders_table" class="table">
                        <thead>
                        <tr>
                            <th>Número</th>
                            <th>Purchase History #</th>
                            <th>Order Date</th>
                            <th>Order Id</th>
                            <th>Buyer</th>
                            <th>Address</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Qty</th>
                            <th>Order Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>{{$order['ref']}}</td>
                                <td>{{$order['purchase_number']}}</td>
                                <td>{{$order['ordered_date']}}</td>
                                <td>{{$order['order_id']}}</td>
                                <td>{{$order['buyer']}}</td>
                                <td>{!! $order['address'] !!}</td>
                                <td>{!! $order['products'] !!}</td>
                                <td>{{$order['total']}}</td>
                                <td>{{$order['qty']}}</td>
                                <td>{{$order['order_status']}}</td>
                                <td>

                                    <!-- Default dropleft button -->
                                    <div class="btn-group dropleft">
                                        <button type="button" class="btn btn-outline-primary dropdown-toggle btn-sm" data-toggle="dropdown"
                                                aria-expanded="false">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <li><a class="dropdown-item editModal" data-id="{{$order['id']}}" href="#"><i class="fas fa-edit"></i> &nbsp;
                                                    Edit</a></li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('orders-download-invoice', $order['id'])}}"><i
                                                        class="far fa-list-alt"></i> &nbsp;
                                                    Download Invoice</a>
                                            </li>
                                            <li><a class="dropdown-item sendEmail" data-id="{{$order['id']}}" data-order-id="{{$order['order_id']}}"
                                                   href="javascript:void(0)"><i
                                                        class="fas fa-envelope"></i> &nbsp; Send
                                                    Email</a></li>
                                            <li><a class="dropdown-item sendWhatsapp"
                                                   data-mobile-num=" {{\App\Helper\eBayFunctions::getMobileNumber( $order['invoice_details'] )}}"
                                                   data-item-id="{{\App\Helper\eBayFunctions::getItemId( $order )}}" data-order-id="{{$order['order_id']}}"
                                                   data-toggle="modal" data-target="#whatsapp-message-modal"
                                                   href="javascript:void(0);"><i class="fab fa-whatsapp-square"></i> &nbsp; Send Whatsapp</a></li>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="height: auto;">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Invoice Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('orders-update-invoice')}}" method="POST">
                    @csrf
                    <div class="modal-body" id="editInvoiceModalBody">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="whatsapp-message-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title " id="staticBackdropLabel">Send Whatsapp Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="sendWhatsappMessageForm">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label>Order Id</label>
                            <input name="order_id" required class="form-control" readonly/>
                            <input type="hidden" name="id" value=""/>
                        </div>
                        <div class="form-group mb-3">
                            <label>Mobile Number</label>
                            <input name="mobile_number" required class="form-control"/>
                        </div>
                        <div class="form-group mb-3">
                            <label>Purchase Link</label>
                            <input name="purchase_link" class="form-control"/>
                        </div>
                        <div class="form-group mb-3">
                            <label>Whatsapp Template</label>
                            <select class="form-control" required id="whatsapp_template">
                                <option value="">-- Choose --</option>
                                @foreach($whatsapp_templates as $whatsapp_template)
                                    <option value="{{$whatsapp_template->id}}">{{$whatsapp_template->template_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Template</label>
                            <textarea class="form-control" required name="template" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="email-modal" tabindex="-1" aria-labelledby="email-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Send Email</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="{{route('orders-sendmail')}}">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="action" value="send-email"/>
                        <input type="hidden" name="row_id" value=""/>
                        <input type="hidden" name="order_id" value=""/>
                        <div class="form-group mb-2">
                            <label>Mail To</label>
                            <input required class="form-control" name="mail_to"/>
                        </div>
                        <div class="form-group mb-2">
                            <label>Subject</label>
                            <input required class="form-control" name="subject"/>
                        </div>
                        <div class="form-group mb-2">
                            <label><input type="checkbox" name="include_invoice" checked/> &nbsp;&nbsp;Include Invoice</label>
                        </div>
                        <div class="form-group mb-3">
                            <label>Email Template</label>
                            <select class="form-control" required id="email_template">
                                <option value="">-- Choose --</option>
                                @foreach($email_templates as $email_template)
                                    <option value="{{$email_template->id}}">{{$email_template->template_name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label>Message</label>
                            <textarea class="form-control" required name="template" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@stop

@section('css')

    <style>
        #orders_table {
            width: 100%;
            font-size: 12px
        }
    </style>
@stop


@section('js')
    <script>
        $(function () {
            $('#downloadOrderLoader').hide();

            $(document).on('click', '.editModal', function () {
                let id = $(this).attr('data-id');
                $.get('{{route('orders-edit-content')}}/' + id, function (res) {
                    $('#staticBackdrop').modal('show');
                    $('#editInvoiceModalBody').html(res);
                }).fail(() => {
                    toastr.error('Could not get response from the request', 'Request failed');

                });
            })

            $(document).on('click', '.sendWhatsapp', function () {
                $('input[name="order_id"]').val($(this).attr('data-order-id'));
                $('input[name="mobile_number"]').val("+34" + $(this).attr('data-mobile-num'));
                $('input[name="purchase_link"]').val("https://ebay.es/itm/" + $(this).attr('data-item-id'));
                $('textarea[name="template"]').val("Order Number: " + $(this).attr('data-order-id'));
                $('#whatsapp_template').val('');
            })
            $(document).on('change', '#whatsapp_template', function () {
                let templateId = $(this).val();
                $.get('{{route('settings-get-wa-template')}}/' + templateId, function (res) {
                    if (res.error === 0) {
                        $('textarea[name="template"]').val("Order Number: " + $('input[name="order_id"]').val() + "\n" + res.data.template_content);
                    }
                })
            })
            $(document).on('change', '#email_template', function () {
                let templateId = $(this).val();
                $.get('{{route('settings-get-email-template')}}/' + templateId, function (res) {
                    if (res.error === 0) {
                        $('textarea[name="template"]').val(res.data.template_content);
                    }
                }).fail(() => {
                    toastr.error('Could not get response from the request', 'Request failed');
                    ;
                })
            })

            $(document).on('submit', '#sendWhatsappMessageForm', function () {
                let mobileNum = $('input[name="mobile_number"]').val().replace(/ /g, '');
                let template = $('textarea[name="template"]').val();
                let purchaseLink;
                if ($('input[name="purchase_link"]').val().length > 0) {
                    purchaseLink = $('input[name="purchase_link"]').val();
                }
                template += "\n\n" + purchaseLink;

                let win = window.open(`https://wa.me/${(mobileNum)}?text=${encodeURI(template)}`, '_blank');
                if (win) {
                    win.focus();
                } else {
                    alert('Please allow popups for this website');
                }
            });

            $(document).on('click', '.sendEmail', function () {
                let id = $(this).attr('data-id');
                let orderId = $(this).attr('data-order-id');
                $.get(`{{route('orders-get-by-id')}}/${id}`, function (res) {
                    if (res.error === 0) {
                        let order = res.data;
                        if (order != null) {
                            let email = '';
                            let orderDetail = order.order_detail;
                            if (orderDetail.TransactionArray !== undefined && orderDetail.TransactionArray.Transaction[0] !== undefined) {
                                email = orderDetail.TransactionArray.Transaction[0].Buyer.Email;
                            } else if (orderDetail.TransactionArray !== undefined && orderDetail.TransactionArray.Transaction !== undefined) {
                                email = orderDetail.TransactionArray.Transaction.Buyer.Email;
                            }
                            if (email.length > 0) {
                                $('input[name="mail_to"]').val(email);
                                $('input[name="row_id"]').val(id);
                                $('input[name="order_id"]').val(orderId);
                                $('#email-modal').modal('show');
                            }
                        }
                    } else {
                        toastr.error(res.message ?? 'Requested order did not exists', 'Order not found');
                    }
                }).fail(() => {
                    toastr.error('Could not get response from the request', 'Request failed');
                    ;
                })
            })


        });

        function downloadOrderAjax(element) {
            let loader = $('#downloadOrderLoader').show();
            $(element).prop('disabled', true);
            $.post('{{route('orders-download')}}', {_token: '{{csrf_token()}}'}, (res) => {
                if (res.error === 0) {
                    toastr.success('Order has been download and updated', 'Updated');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000)
                } else {
                    toastr.error(res.message ?? 'Could not download/update orders', 'Download/Update fail');
                }
            }).fail(()=>{
                toastr.error('Could not download/update orders', 'Download/Update fail');
                loader.hide();
                $(element).prop('disabled', false);
            }).done(() => {
                loader.hide();
                $(element).prop('disabled', false);
            })
        }

    </script>
@stop
