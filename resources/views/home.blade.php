@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">{{ __('dashboard') }}</div>

                <div class="panel-body">
                    <div class="alert alert-info">
                        <p>{{ __($msg) }}</p>
                    </div>
                    @if (!empty($wifidog_uri))
                    <a class="btn btn-success" href={{ $wifidog_uri }}>start internet</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
