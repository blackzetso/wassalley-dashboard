@foreach($customers as $key=>$customer)
    <tr class="">
        <td class="">
            {{$customers->firstitem()+$key}}
        </td>
        <td class="table-column-pl-0">
            <a href="{{route('admin.customer.view',[$customer['id']])}}">
                {{$customer['f_name']." ".$customer['l_name']}}
            </a>
        </td>
        <td>
            {{$customer['email']}}
        </td>
        <td>
            {{$customer['phone']}}
        </td>
        <td>
            <label class="badge badge-soft-info">
                {{$customer->orders->count()}}
            </label>
        </td>
        <td class="show-point-{{$customer['id']}}-table">
            {{$customer['point']}}
        </td>
        <td>
            <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$customer->id}}">
                <input type="checkbox" onclick="status_change_alert('{{route('admin.customer.status',[$customer->id,$customer->status ? 0 : 1 ])}}', '{{$customer->status?__('من انك تريد إلغاء حظر هذا العضو'):__('من أنك تريد حظر هذا العضو')}}', event)" class="toggle-switch-input" id="stocksCheckbox{{$customer->id}}" {{$customer->status?'checked':''}}>
                <span class="toggle-switch-label">
                    <span class="toggle-switch-indicator"></span>
                </span>
            </label>
        </td>
        <td>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                        id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                    <i class="tio-settings"></i>
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item"
                       href="{{route('admin.customer.view',[$customer['id']])}}">
                        <i class="tio-visible"></i> عرض
                    </a>
                    <a class="dropdown-item" href="javascript:" onclick="set_point_modal_data('{{route('admin.customer.set-point-modal-data',[$customer['id']])}}')">
                        <i class="tio-coin"></i>   إضافة نقط
                    </a>
                    <a class="dropdown-item" href="./edit/{{ $customer->id }}" > 
                    <i class="tio-edit"></i> تعديل </a>  

                    <a class="dropdown-item" href="javascript:" onclick="form_alert('category-{{$customer['id']}}','هل تريد حذف هذا العضو ؟')">
                    <i class="tio-delete"></i> حذف </a>
                    <form action="{{route('admin.customer.delete',[$customer['id']])}}"
                            method="post" id="category-{{$customer['id']}}">
                        @csrf @method('delete')
                    </form>

                    {{--<a class="dropdown-item" target="" href="">
                        <i class="tio-download"></i> Suspend
                    </a>--}}
                </div>
            </div>
        </td>
    </tr>
<!--    <div class="modal fade" id="exampleModal-{{$customer['id']}}" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Internal Point</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="javascript:" method="POST" id="point-form-{{$customer['id']}}">
                    @csrf
                    <div class="modal-body">
                        <h5>
                            <label class="badge badge-soft-info">
                                {{$customer['f_name']}} {{$customer['l_name']}}
                            </label>
                            <label class="show-point-{{$customer['id']}}">
                                ( Available Point : {{$customer['point']}} )
                            </label>
                        </h5>
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label">Add Point :</label>
                            <input type="number" min="1" value="1" max="1000000"
                                   class="form-control"
                                   name="point">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Close
                        </button>
                        <button type="button"
                                onclick="add_point('point-form-{{$customer['id']}}','{{route('admin.customer.add-point',[$customer['id']])}}','{{$customer['id']}}')"
                                class="btn btn-primary">Add
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>-->
@endforeach
@push('script_2')
    <script>
        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'هل أنت متأكد ؟',
                text: message,
                type: 'تنبيه',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'لا',
                confirmButtonText: 'نعم',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href=url;
                }
            })
        }
    </script>
@endpush