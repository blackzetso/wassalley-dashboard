@extends('layouts.admin.app')

@section('title','تعديل بيانات العضو')

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-edit"></i> تعديل بيانات العضو </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form class="mb-5" action="{{route('admin.customer.update',[$user['id']])}}" method="post" >
                    @csrf
                    <div class="row">
                        <div class="col-6"> 
                            <div class="form-group   lang_form" >
                                <label class="input-label" for="exampleFormControlInput1"> الإسم الأول </label>
                                <input class="form-control" type="text" name="f_name" value="{{ $user->f_name }}">
                            </div>   
                        </div>
                        <div class="col-6"> 
                            <div class="form-group   lang_form" >
                                <label class="input-label" for="exampleFormControlInput1"> الإسم الثانى </label>
                                <input class="form-control" type="text" name="l_name" value="{{ $user->l_name }}">
                            </div>   
                        </div>
                        <div class="col-6"> 
                            <div class="form-group   lang_form" >
                                <label class="input-label" for="exampleFormControlInput1"> البريد الإلكترونى </label>
                                <input class="form-control" type="text" name="email" value="{{ $user->email }}">
                            </div>   
                        </div>
                        <div class="col-6"> 
                            <div class="form-group   lang_form" >
                                <label class="input-label" for="exampleFormControlInput1"> رقم الهاتف </label>
                                <input class="form-control" type="text" name="phone" value="{{ $user->phone }}">
                            </div>   
                        </div> 
                        <div class="col-12">
                            
                            <button class="btn btn-primary " type="submit" >حفظ</button>
                        </div>
                    </div>
                </form>
                <hr>
                <form class="mt-5" action="{{route('admin.customer.customer-password',[$user['id']])}}" method="post" >
                    @csrf
                    <div class="row">
                        <div class="col-6"> 
                            <div class="form-group   lang_form" >
                                <label class="input-label" for="exampleFormControlInput1"> كلمة المرور </label>
                                <input class="form-control" type="text" name="password" >
                            </div>   
                        </div>
                        <div class="col-6"> 
                            <div class="form-group   lang_form" >
                                <label class="input-label" for="exampleFormControlInput1"> تأكيد كلمة المرور </label>
                                <input class="form-control" type="text" name="confirm_password" >
                            </div>   
                        </div> 
                        <div class="col-12"> 
                            <button class="btn btn-primary " type="submit" >حفظ</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection
 