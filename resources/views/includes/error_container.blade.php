@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin-bottom:0px">
            {!! implode('', $errors->all('<li>:message</li>')) !!}
        </ul>
    </div>
@endif
@if(Session::get('error') && Session::get('error') != null)
    <div class="alert alert-danger">{{ Session::get('error') }}</div>
    @php
        Session::put('error', null)
    @endphp
@endif
@if(Session::get('success') && Session::get('success') != null)
    <div class="alert alert-success">{{ Session::get('success') }}</div>
    @php
        Session::put('success', null)
    @endphp
@endif
