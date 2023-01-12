@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
    <h1>Reports</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">Sales Report</div>
        </div>
        <div class="card-body">
            @if($total['count'] == 0)
                <div class="alert alert-warning">There is no orders yet. Please update orders first</div>
            @else
                <div class="row">
                    <div class="col">
                        <div class="row">
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h4>{{$total['count']}}</h4>
                                        <p>Total Orders</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h4><sup style="font-size: 20px">€</sup>{{$total['sales']}}</h4>
                                        <p>Total Sales</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-euro-sign"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-6">

                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h4><sup style="font-size: 20px">€</sup>{{$total_year['sales']}} / {{$total_year['count']}}</h4>
                                        <p>This Year <span class="">Sales / Total</span></p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h4><sup style="font-size: 20px">€</sup>{{$total_month['sales']}} / {{$total_month['count']}}</h4>
                                        <p>This Month <span>Sales / Total</span></p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-5">
                        <div style="display: none" id="report-loader" class="float-right text-sm">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <select class="form-control " id="s-filter-country">
                                        <option value="">All</option>
                                        @foreach($countries as $country)
                                            <option value="{{$country['code']}}">{{$country['flag']}} &nbsp; {{$country['name']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country">By</label>
                                    <select class="form-control " id="s-filter-duration">
                                        @foreach($durations as $key =>  $duration)
                                            <option value="{{$key}}">{{$duration}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <strong>Report Summary: </strong>
                            <div style="display: none" id="report-summary-loader" class="float-right text-sm">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                            <div id="report-summary-container"></div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="card mb-5">
        <div class="card-header">
            <div class="card-title">Invoices</div>
        </div>
        <div class="card-body">
            @if($total['count'] == 0)
                <div class="alert alert-warning">There is no orders yet. Please update orders first</div>
            @else

                @include('includes.error_container')
                <form id="i-download-form" action="{{route('reports-invoice-download')}}" method="post">
                    <div class="row">
                        @csrf
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Country</label>
                                <select name="country" class="form-control " id="i-filter-country">
                                    <option value="">All</option>
                                    @foreach($countries as $country)
                                        <option @if(old('country') == $country['code']) selected @endif value="{{$country['code']}}">{{$country['flag']}}
                                            &nbsp; {{$country['name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year" class="form-control" id="i-filter-year">
                                    @foreach($years as $y)
                                        <option @if(old('year') == $y) selected @endif>{{$y}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Month</label>
                                <select name="month" class="form-control" id="i-filter-month">
                                    @for($x=1;$x<=12; $x++ )
                                        <option @if(old('month') == $x) selected @endif >{{$x}}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button class="btn btn-success form-control">Download</button>
                            </div>
                        </div>

                    </div>
                </form>
            @endif
        </div>
    </div>
@stop

@section('css')

@stop

@section('js')
    @if($total['count'] != 0)
        <script>

            $(function () {
                var chartCanvas = initiateChartJs({
                    labels: ['loading'],
                    datasets: []
                });
                setGraph();
                $(document).on('change', '#s-filter-country, #s-filter-duration', function () {
                    setGraph();
                });


                function setGraph() {
                    $('#report-loader').show();
                    let country = $('#s-filter-country').val();
                    let duration = $('#s-filter-duration').val();
                    $.post(`{{route('reports-graph-data')}}`, {_token: `{{csrf_token()}}`, country: country, duration: duration}, (res) => {
                        if (res.error === 0) {
                            $('#report-summary-container').html('');
                            chartCanvas.destroy();
                            chartCanvas = initiateChartJs(res.data.graph_data);
                            $('#report-summary-loader').show();
                            $.post(`{{route('reports-summary-view')}}`, {
                                _token: `{{csrf_token()}}`,
                                raw_data: res.data.raw_data,
                                duration: duration
                            }, (viewRes) => {
                                $('#report-summary-container').html(viewRes);
                            }).fail(() => {
                                $('#report-summary-loader').hide();
                                toastr.error('Could not get response from the request', 'Request failed');
                            }).done(() => {
                                $('#report-summary-loader').hide();
                            });
                            return;
                        }
                        toastr.error(res.message ?? 'Could not get response from the request', 'Request failed');
                    }).fail(() => {
                        $('#report-loader').hide();
                        toastr.error('Could not get response from the request', 'Request failed');
                    }).done(() => {
                        $('#report-loader').hide();
                    });
                }
            });

            function initiateChartJs(data) {
                const ctx = document.getElementById('myChart');
                return new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

        </script>@endif
@stop
