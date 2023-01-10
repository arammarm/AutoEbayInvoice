<input type="hidden" name="action" value="update-invoice-details"/>
<input type="hidden" name="id" value="{{$order['id']}}"/>
<div class="row">
    <div class="col-md-4">
        <div class="form-group mb-2">
            <label>Número</label>
            <input class="form-control" name="numero" value="{{$invoice_details['numero']}}"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group  mb-2">
            <label>Fecha</label>
            <input class="form-control" name="fetcha" value="{{$invoice_details['fetcha']}}"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group mb-4">
            <label>Forma de pago</label>
            <input class="form-control" name="site" value="{{$invoice_details['site']}}"/>
        </div>
    </div>
</div>
<h6>DATOS DEL CLIENTE</h6>
<div class="form-group mb-2">
    <label>Name</label>
    <input class="form-control" name="buyer_name" value="{{$invoice_details['buyer_name']}}"/>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-2">
            <label>Address</label>
            <input class="form-control" name="address" value="{{$invoice_details['address']}}"/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-2">
            <label>Address 2</label>
            <input class="form-control" name="address_2" value="{{$invoice_details['address_2']}}"/>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group mb-2">
            <label>City</label>
            <input class="form-control" name="city" value="{{$invoice_details['city']}}"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group  mb-2">
            <label>State</label>
            <input class="form-control" name="state" value="{{$invoice_details['state']}}"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group  mb-2">
            <label>Zip Code</label>
            <input class="form-control" name="zip_code" value="{{$invoice_details['zip_code']}}"/>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group  mb-2">
            <label>Country</label>
            <input class="form-control" name="country" value="{{$invoice_details['country']}}"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group  mb-2">
            <label>Phone No</label>
            <input class="form-control" name="phone_no" value="{{$invoice_details['phone_no']}}"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group mb-4">
            <label>CIF No</label>
            <input class="form-control" name="cif_no" value="{{$invoice_details['cif_no']}}"/>
        </div>
    </div>
</div>

@foreach ($invoice_details['ref'] as $ref)
    <h6>ITEM DETAILS #{{ $loop->iteration }}</h6>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-2">
                <label>Ref</label>
                <input class="form-control" name="ref[]" value="{{$ref}}"/>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-2">
                <label>Nombre</label>
                <input class="form-control" name="item_name[]" value="{{$invoice_details['item_name'][$loop->iteration -1]}}"/>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="form-group mb-2">
                <label>PRECIO</label>
                <input class="form-control" name="price[]" value="{{$invoice_details['price'][$loop->iteration-1]}}"/>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group mb-2">
                <label>DTO</label>
                <input class="form-control" name="discount[]" value="{{$invoice_details['discount'][$loop->iteration-1]}}"/>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group mb-2">
                <label>UDS</label>
                <input class="form-control" name="qty[]" value="{{$invoice_details['qty'][$loop->iteration-1]}}"/>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-2">
                <label>SUBTOTAL</label>
                <input class="form-control" name="sub_total[]" value="{{$invoice_details['sub_total'][$loop->iteration-1]}}"/>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-4">
                <label>IMP</label>
                <input class="form-control" name="tax[]" value="{{$invoice_details['tax'][$loop->iteration-1]}}"/>
            </div>
        </div>
    </div>
@endforeach
<h6>TOTAL SUMMARY</h6>
<div class="row">
    <div class="col-md-3">
        <div class="form-group mb-2">
            <label>BASE</label>
            <input class="form-control" name="sub_total_final" value="{{$invoice_details['sub_total_final']}}"/>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-2">
            <label>Shipping Cost</label>
            <input class="form-control" name="shipping_cost" value="{{$invoice_details['shipping_cost']}}"/>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-2">
            <label>Tax</label>
            <input class="form-control" name="total_tax" value="{{$invoice_details['total_tax']}}"/>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-2">
            <label>Total</label>
            <input class="form-control" name="total_final" value="{{$invoice_details['total_final']}}"/>
        </div>
    </div>
</div>
