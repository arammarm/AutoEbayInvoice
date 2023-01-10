<div class="row">
    <div class="col">
        <div class="table-responsive" style="max-height: 330px">
            <table class="table table-bordered rounded-sm">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Sales Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data as $d)
                    <tr>
                        <td>{{$d['date']}}</td>
                        <td>€{{$d['t']}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
