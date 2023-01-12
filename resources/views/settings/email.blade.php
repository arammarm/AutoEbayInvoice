@extends('adminlte::page')

@section('title', 'Email Settings')

@section('content_header')
    <h1>Email Settings</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">Templates</div>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-2">
                <a href="javascript:void(0);" class="btn btn-sm btn-primary" id="new-email-template-form-btn" data-toggle="modal"
                   data-target="#emailTemplateModal">Add New
                    Template</a>
            </div>

            @if(session()->has('error'))
                <div class="alert alert-@if(session()->get('error') == 1){{'danger'}}@else{{'success'}}@endif text-sm mx-2">{{session()->get('message')}}</div>
            @endif

            <table id="orders_table" class="table " style="width:100%;font-size:12px">
                <thead>
                <th>Template Name</th>
                <th>Content</th>
                <th>Action</th>
                </thead>
                <tbody>
                @foreach($templates as $tpl)
                    <tr>
                        <td>{{$tpl->template_name }}</td>
                        <td>{{$tpl->template_content }}</td>
                        <td>
                            <button class="btn btn-primary btn-sm editTemplate"
                                    data-id="{{ $tpl->id }}">Edit
                            </button>
                            <button class="btn btn-danger btn-sm removeTemplate"
                                    data-id="{{ $tpl->id }}">Remove
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal fade" id="emailTemplateModal" tabindex="-1" aria-labelledby="emailTemplateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Email Template</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('settings-add-email-template')}}" id="email-template-form" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="0">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="manage-email-template"/>
                        <div class="form-group mb-3">
                            <label>Template Name</label>
                            <input class="form-control" required name="template_name"/>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea rows="10" class="form-control" required name="message"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')

@stop

@section('js')
    <script>
        $(function () {
            $(document).on('click', '#new-email-template-form-btn', () => {
                $('input[name="id"]').val(0);
                $('#email-template-form').trigger("reset");
            });
            $(document).on('click', '.editTemplate', function () {
                let id = $(this).attr('data-id');
                $.get(`{{route('settings-get-email-template')}}/${id}`, (res) => {
                    if (res.error === 0) {
                        let name = res.data.template_name;
                        let content = res.data.template_content;
                        let active = res.data.active;

                        $('#emailTemplateModal').modal('show');

                        $('input[name="template_name"]').val(name);
                        $('textarea[name="message"]').val(content);
                        $('input[name="id"]').val(id);
                    } else {
                        toastr.error(res.message ?? 'Could not get template data', 'Request failed');
                    }

                }).fail(() => {
                    toastr.error('Could not get template data', 'Request failed');
                });
            });
            $(document).on('click', '.removeTemplate', function () {
                let id = $(this).attr('data-id');
                let confirmR = confirm('Do you want to remove the template?');
                if (confirmR) {
                    $.post(`{{route('settings-delete-email-template')}}`, {id: id, _token: '{{csrf_token()}}'}, (res) => {
                        if (res.error === 0) {
                            toastr.success(res.message, 'Deleted');
                        } else {
                            toastr.error('Could not delete template', 'Request failed');
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000)
                    }).fail(() => {
                        toastr.error('Could not delete template', 'Request failed');
                    });
                }
            });

        });

    </script>
@stop
