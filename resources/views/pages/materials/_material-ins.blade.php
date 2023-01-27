@include('components.assets._datatable')
@include('components.assets._select2')

<div id="materialInsCrudDiv">
    <h2 class="section-title">
        {{ __('Material In List') }}
        <button type="button" id="addMaterialInButton" class="ml-2 btn btn-success" data-toggle="modal"
            data-target="#materialInFormModal">
            <i class="fas fa-plus-circle"></i> Tambah
        </button>
    </h2>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="materialInDatatable" style="width:100%">
                </table>
            </div>
        </div>
    </div>
</div>

@push('modal')
    <div class="modal fade" id="materialInFormModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
        aria-hidden="">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="materialInFormModalLabel"></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="materialInFormModalBody">

                    <form method="POST" id="materialInForm" onsubmit="return validateInputs();">
                        @csrf

                        <div class="row">
                            <div class="col form-group">
                                <label for="materialInCodeInput">{{ __('Code') }}</label>
                                <input type="text" class="form-control" name="code" id="materialInCodeInput">
                            </div>

                            <div class="col form-group">
                                <label for="materialInTypeSelect">{{ __('Type') }}</label>
                                <select id="materialInTypeSelect" name="type" required class="form-control">
                                    @foreach ($materialInTypes as $type)
                                        <option>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="materialInAtInput">{{ __('Date') }}</label>
                            <input type="date" class="form-control" name="at" required id="materialInAtInput">
                        </div>

                        <div class="form-group">
                            <label for="materialInNoteInput">{{ __('Note') }}</label>
                            <textarea class="form-control" name="note" id="materialInNoteInput" rows="3" style="height:100%;"></textarea>
                        </div>

                        <div class="px-1" style="overflow-x: auto">
                            <div id="materialInDetailsParent" style="width: 100%">
                                <div class="row m-0">
                                    <label class="col-4">{{ __('Name') }}</label>
                                    <label class="col-2">{{ __('Qty') }}</label>
                                    <label class="col-3">{{ __('Price') }}</label>
                                    <label class="col-2">{{ __('Subtotal') }}</label>
                                </div>
                            </div>
                        </div>


                        <div class="">
                            <a href="javascript:;" id="addMaterialInDetailButton" class="btn btn-success btn-sm mr-2"><i
                                    class="fas fa-plus"></i> {{ __('More') }}</a>
                            <a href="{{ route('materials.index') }}">{{ __('New material') }}?</a>
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <button type="submit" form="materialInForm"
                            class="btn btn-outline-success">{{ __('Save') }}</button>
                    </div>
                    <form action="" method="post" id="materialInDeleteForm">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-icon btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endpush

@push('js')
    <script>
        if (materialInsCrudDiv) {
            $(function() {
                $(materialInTypeSelect).select2({
                    tags: true,
                    dropdownParent: '#materialInFormModalBody'
                })
            })

            function validateInputs() {
                const selectedMaterialIds = [];
                let isValid = true;

                $('.text-danger').remove();

                [...document.getElementsByClassName('materialSelect')].map(selectEl => {
                    if (selectedMaterialIds.includes(selectEl.value)) {
                        const errorTextDiv = document.createElement('div');
                        errorTextDiv.innerHTML = '{{ __('Material is duplicated') }}';
                        errorTextDiv.classList.add('text-danger')

                        selectEl.parentNode.append(errorTextDiv)
                        isValid = false;
                    } else {
                        selectedMaterialIds.push(selectEl.value)
                    }
                })

                return isValid;
            }

            const removePutMethodInput = () => {
                $('[name="_method"][value="put"]').remove()
            }

            const addPutMethodInputInsert = () => {
                $('#materialInForm').append($('@method('put')'))
            }

            function addMaterialInDetailRow(detail) {
                const nDetailInputSet = $('.materialInDetailInputSetDiv').length

                const detailRowDiv = document.createElement('div')
                detailRowDiv.setAttribute('class', 'form-group row mx-0 align-items-center materialInDetailInputSetDiv')
                materialInDetailsParent.append(detailRowDiv);

                function getMaterialSelect() {
                    const materials = @json(App\Models\Material::all());

                    const initMaterialsSelect = $selectDom => $selectDom.select2({
                        dropdownParent: '#materialInFormModalBody',
                        placeholder: '{{ __('Material') }}',
                        data: materials.map(material => {
                            return {
                                id: material.id,
                                text: `${material.name} (${material.brand})`
                            }
                        })
                    });

                    const materialSelectParentDiv = document.createElement('div')
                    materialSelectParentDiv.setAttribute('class', 'col-4 pl-0 pr-2')
                    const $selectDom = $(`<select required placeholder="{{ __('Material name') }}"></select>`)
                        .addClass('form-control materialSelect')
                        .attr('name', `details[${nDetailInputSet}][material_id]`)
                    $(materialSelectParentDiv).append($selectDom)
                    initMaterialsSelect($selectDom);
                    $selectDom.val(detail.material_id).change();

                    return materialSelectParentDiv
                }

                function getSubtotalDiv() {
                    const temp = document.createElement('div')
                    temp.setAttribute('class', 'col-2 px-2')

                    const subtotal = detail.qty * detail.price
                    if (subtotal) {
                        temp.innerHTML = subtotal.toLocaleString('id', {
                            style: 'currency',
                            currency: 'IDR',
                            maximumFractionDigits: 0
                        })
                    }

                    return temp;
                }

                $(detailRowDiv).append(getMaterialSelect());

                const qtyInputParentDiv = document.createElement('div')
                qtyInputParentDiv.setAttribute('class', 'col-2 px-2')
                $(qtyInputParentDiv).append(
                    `<input class="form-control" name="details[${nDetailInputSet}][qty]" min="${detail.qty - detail.stock?.qty || 0}" type="number" required placeholder="{{ __('Qty') }}" value="${detail.qty || ''}">`
                )

                const priceInputParentDiv = document.createElement('div')
                priceInputParentDiv.setAttribute('class', 'col-3 px-2')
                $(priceInputParentDiv).append(
                    `<input class="form-control" name="details[${nDetailInputSet}][price]" min="0" type="number" required placeholder="{{ __('Price') }}" value="${detail.price || ''}">`
                )

                const getRemoveRowButtonParentDiv = () => {
                    const temp = document.createElement('div')
                    temp.setAttribute('class', 'col-1 pl-2 pr-0')
                    $(temp).append($(
                        `<button class="btn btn-outline-danger btn-icon" tabindex="-1" onclick="this.parentNode.parentNode.remove()"><i class="fas fa-trash"></i></button>`
                    ))

                    return temp;
                }


                $(detailRowDiv).append(qtyInputParentDiv)
                    .append(priceInputParentDiv)
                    .append(getSubtotalDiv())

                if (nDetailInputSet !== 0) {
                    $(detailRowDiv).append(getRemoveRowButtonParentDiv())
                }
            }

            const setMaterialInFormValue = materialIn => {
                const materialInTypeSelect = $('#materialInTypeSelect')
                materialInTypeSelect.val(materialIn.type || null).trigger('change')
                materialInCodeInput.value = materialIn.code || null
                materialInNoteInput.value = materialIn.note || null

                if (materialIn.at) {
                    materialInAtInput.value = `${moment(materialIn.at).format('YYYY-MM-DD')}`
                } else {
                    materialInAtInput.value = '{{ date('Y-m-d') }}'
                }

                if (materialIn.details && materialIn.details.length > 0) {
                    materialIn.details.map(detail => addMaterialInDetailRow(detail))
                } else {
                    addMaterialInDetailRow({})
                    addMaterialInDetailRow({})
                    addMaterialInDetailRow({})
                }
            }

            $(document).on('click', '#addMaterialInButton', () => {
                $('.materialInDetailInputSetDiv').remove()

                removePutMethodInput()
                setMaterialInFormValue({})

                materialInFormModalLabel.innerHTML = '{{ __('Add New Material In') }}'
                materialInForm.action = "{{ route('material-ins.store') }}"
                materialInDeleteForm.style.display = "none"
            })

            $(document).on('click', '.editMaterialInsertButton', function() {
                $('.materialInDetailInputSetDiv').remove()

                const materialInId = $(this).data('material-in-id')
                const materialIn = materialInsCrudDiv.materialIns.find(materialIn => materialIn.id ===
                    materialInId)

                removePutMethodInput()
                addPutMethodInputInsert()
                setMaterialInFormValue(materialIn)

                materialInFormModalLabel.innerHTML =
                    `{{ __('Edit Material In') }}: ${moment(materialIn.at).format('DD-MM-YYYY')}`
                materialInForm.action = `{{ route('material-ins.update', '') }}/${materialIn.id}`
                materialInDeleteForm.style.display = "block"
                materialInDeleteForm.action = `{{ route('material-ins.destroy', '') }}/${materialIn.id}`
            })

            $(document).on('click', '#addMaterialInDetailButton', function() {
                addMaterialInDetailRow({})
            })



            // ######### DATATABLE SECTION

            const renderTagButton = text => `<a href="javascript:;" class="m-1 badge badge-success materialInDetailTag">${text}</a>`

            $(document).on('click', '.materialInDetailTag', function() {
                const materialName = this.innerHTML.split(' ')[0]
                materialInsCrudDiv.materialInDatatable.DataTable().search(materialName).draw()
            })

            materialInsCrudDiv.materialInDatatable = $(materialInDatatable).dataTable({
                processing: true,
                search: {
                    return: true,
                },
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.1/i18n/{{ app()->getLocale() }}.json'
                },
                serverSide: true,
                ajax: {
                    url: '/api/datatable/MaterialIn?with=details.material,details.stock',
                    dataSrc: json => {
                        materialInsCrudDiv.materialIns = json.data;
                        return json.data;
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader(
                            "Authorization",
                            'Bearer {{ decrypt(request()->cookie('api-token')) }}'
                        )
                    },
                    cache: true
                },
                order: [1, 'desc'],
                columns: [{
                    data: 'code',
                    title: '{{ __('validation.attributes.code') }}'
                }, {
                    data: 'at',
                    title: '{{ __('validation.attributes.at') }}',
                    render: at => moment(at).format('DD-MM-YYYY')
                }, {
                    data: 'type',
                    title: '{{ __('validation.attributes.type') }}',
                }, {
                    data: 'note',
                    title: '{{ __('validation.attributes.note') }}'
                }, {
                    orderable: false,
                    title: '{{ __('Items') }}',
                    data: 'details',
                    name: 'details.material.name',
                    width: '20%',
                    render: details => details.map(detail => renderTagButton(
                        `${detail.material?.name} (${detail.stock?.qty}/${detail.qty})`)).join('')
                }, {
                    render: function(data, type, row) {
                        const editButton = $(
                            '<a class="btn-icon-custom" href="javascript:;"><i class="fas fa-cog"></i></a>'
                        )
                        editButton.attr('data-toggle', 'modal')
                        editButton.attr('data-target', '#materialInFormModal')
                        editButton.addClass('editMaterialInsertButton');
                        editButton.attr('data-material-in-id', row.id)
                        return editButton.prop('outerHTML')
                    },
                    orderable: false
                }]
            });
        }
    </script>
@endpush
