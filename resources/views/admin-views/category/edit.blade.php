@extends('layouts.admin.app')

@section('title','Update category')

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-edit"></i> {{\App\CentralLogics\translate('category')}} {{\App\CentralLogics\translate('update')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.category.update',[$category['id']])}}" method="post" enctype="multipart/form-data">
                    @csrf
                    @php($language=\App\Model\BusinessSetting::where('key','language')->first())
                    @php($language = $language->value ?? null)
                    @php($default_lang = 'en')
                    @if($language)
                        @php($default_lang = json_decode($language)[0])
                        <ul class="nav nav-tabs mb-4">
                            @foreach(json_decode($language) as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link {{$lang == $default_lang? 'active':''}}" href="#" id="{{$lang}}-link">{{\App\CentralLogics\Helpers::get_language_name($lang).'('.strtoupper($lang).')'}}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="row">
                            <div class="col-6">
                                @foreach(json_decode($language) as $lang)
                                    <?php
                                    if(count($category['translations'])){
                                        $translate = [];
                                        foreach($category['translations'] as $t)
                                        {
                                            if($t->locale == $lang && $t->key=="name"){
                                                $translate[$lang]['name'] = $t->value;
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="form-group {{$lang != $default_lang ? 'd-none':''}} lang_form" id="{{$lang}}-form">
                                        <label class="input-label" for="exampleFormControlInput1">{{\App\CentralLogics\translate('name')}} ({{strtoupper($lang)}})</label>
                                        <input type="text" name="name[]" value="{{$lang==$default_lang?$category['name']:($translate[$lang]['name']??'')}}" class="form-control" placeholder="New Category" {{$lang == $default_lang? 'required':''}}>
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{$lang}}">
                                @endforeach
                                @else
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group lang_form" id="{{$default_lang}}-form">
                                                <label class="input-label" for="exampleFormControlInput1">{{\App\CentralLogics\translate('name')}} ({{strtoupper($lang)}})</label>
                                                <input type="text" name="name[]" value="{{$category['name']}}" class="form-control" placeholder="New Category" required>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{$default_lang}}">
                                            @endif
                                            <input name="position" value="0" style="display: none">
                                        </div>
                                        <div class="col-6 from_part_2">
                                            <div class="form-group">
                                                <label>{{\App\CentralLogics\translate('image')}}</label><small style="color: red">* ( {{\App\CentralLogics\translate('ratio')}} 3:1 )</small>
                                                <div class="custom-file">
                                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                                           accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label" for="customFileEg1">{{\App\CentralLogics\translate('choose')}} {{\App\CentralLogics\translate('file')}}</label>
                                                </div>
                                                <hr>
                                            </div>
                                        </div>
                                        <div class="col-12 from_part_2">
                                            <center>
                                                <img style="width: 30%;border: 1px solid; border-radius: 10px;" id="viewer"
                                                     src="{{asset('storage/app/public/category')}}/{{$category['image']}}" alt=""/>
                                            </center>
                                        </div>
                                    </div>
                                    <hr>
                                    <button type="submit" class="btn btn-primary">{{\App\CentralLogics\translate('update')}}</button>
                </form>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $(".lang_link").click(function(e){
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];
            console.log(lang);
            $("#"+lang+"-form").removeClass('d-none');
            if(lang == '{{$default_lang}}')
            {
                $(".from_part_2").removeClass('d-none');
            }
            else
            {
                $(".from_part_2").addClass('d-none');
            }
        });
    </script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
        });
    </script>
@endpush
