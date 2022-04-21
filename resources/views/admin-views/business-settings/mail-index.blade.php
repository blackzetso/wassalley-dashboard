@extends('layouts.admin.app')

@section('title','Settings')

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{\App\CentralLogics\translate('smtp')}} {{\App\CentralLogics\translate('mail')}} {{\App\CentralLogics\translate('setup')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            @php($config=\App\Model\BusinessSetting::where(['key'=>'mail_config'])->first())
            @php($data=json_decode($config['value'],true))
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{env('APP_MODE')!='demo'?route('admin.business-settings.mail-config'):'javascript:'}}" method="post"
                      enctype="multipart/form-data">
                    @csrf
                    @if(isset($config))
                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('mailer')}} {{\App\CentralLogics\translate('name')}}</label><br>
                            <input type="text" placeholder="ex : Alex" class="form-control" name="name"
                                   value="{{env('APP_MODE')!='demo'?$data['name']:''}}" required>
                        </div>

                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('host')}}</label><br>
                            <input type="text" class="form-control" name="host" value="{{env('APP_MODE')!='demo'?$data['host']:''}}" required>
                        </div>
                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('driver')}}</label><br>
                            <input type="text" class="form-control" name="driver" value="{{env('APP_MODE')!='demo'?$data['driver']:''}}" required>
                        </div>
                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('port')}}</label><br>
                            <input type="text" class="form-control" name="port" value="{{env('APP_MODE')!='demo'?$data['port']:''}}" required>
                        </div>

                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('username')}}</label><br>
                            <input type="text" placeholder="ex : ex@yahoo.com" class="form-control" name="username"
                                   value="{{env('APP_MODE')!='demo'?$data['username']:''}}" required>
                        </div>

                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('email')}} {{\App\CentralLogics\translate('id')}}</label><br>
                            <input type="text" placeholder="ex : ex@yahoo.com" class="form-control" name="email"
                                   value="{{env('APP_MODE')!='demo'?$data['email_id']:''}}" required>
                        </div>

                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('encryption')}}</label><br>
                            <input type="text" placeholder="ex : tls" class="form-control" name="encryption"
                                   value="{{env('APP_MODE')!='demo'?$data['encryption']:''}}" required>
                        </div>

                        <div class="form-group mb-2">
                            <label style="padding-left: 10px">{{\App\CentralLogics\translate('password')}}</label><br>
                            <input type="text" class="form-control" name="password" value="{{env('APP_MODE')!='demo'?$data['password']:''}}" required>
                        </div>

                        <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}" onclick="{{env('APP_MODE')!='demo'?'':'call_demo()'}}" class="btn btn-primary mb-2">{{\App\CentralLogics\translate('save')}}</button>
                    @else
                        <button type="submit" class="btn btn-primary mb-2">{{\App\CentralLogics\translate('configure')}}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')

@endpush
