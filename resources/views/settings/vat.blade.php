@extends('adminlte::page')

@section('title', 'Vat Settings')

@section('content_header')
    <h1>Vat Settings</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">Vat Percentages</div>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                <tr>
                    <th>Country Code</th>
                    <th>Country Name</th>
                    <th>Percentage</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($vat_configs as $vat_config)
                    <tr class="table-row">
                        <th>{{$vat_config['country_code']}}</th>
                        <td>{{$vat_config['country_name']}}</td>
                        <td>
                            <div class="input-group mb-3">
                                <input min="0" type="number" value="{{$vat_config['percentage']}}" class="form-control vat-percentage" placeholder="Percentage"
                                       aria-label="Vat Percentage"
                                       aria-describedby="basic-addon2">
                                <div class="input-group-append">
                                    <span class="input-group-text" id="basic-addon2">%</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-success btn-vat-save" data-id="{{$vat_config['id']}}">
                                 <span style="display: none" class="spinner-grow spinner-grow-sm loader"
                                      role="status" aria-hidden="true"></span> &nbsp;
                                Save
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        $(function () {
            $('.btn-vat-save').find('.loader').hide();
            $(document).on('click', '.btn-vat-save', function () {
                let dataId = $(this).attr('data-id');
                let percentage = $(this).closest('.table-row').find('.vat-percentage').val();
                $(this).prop('disabled', true);
                $(this).find('.loader').show();

                $.post('{{route('settings-vat-save')}}', {_token: `{{csrf_token()}}`, id: dataId, percentage: percentage}, function (res) {
                    if (res.error === 0) {
                        toastr.success(res.message, 'Success');
                        return;
                    }
                    toastr.error(res.message ?? 'Something went wrong', 'Update failed');
                }).fail(() => {
                    toastr.error('Could not get response from the request', 'Request failed');

                }).done(() => {
                    $(this).find('.loader').hide();
                    $(this).prop('disabled', false);
                });
            });
        })
    </script>
@stop
