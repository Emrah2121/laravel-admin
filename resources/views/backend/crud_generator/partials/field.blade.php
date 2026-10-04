<tr data-field-row>
    <td>
        <input type="text" name="fields[{{ $index }}][name]" value="{{ $field['name'] ?? '' }}" required
            class="form-control form-control-sm @error('fields.' . $index . '.name') is-invalid @enderror" placeholder="title" />
        @error('fields.' . $index . '.name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <input type="text" name="fields[{{ $index }}][label]" value="{{ $field['label'] ?? '' }}"
            class="form-control form-control-sm @error('fields.' . $index . '.label') is-invalid @enderror" placeholder="Title" />
        @error('fields.' . $index . '.label')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select name="fields[{{ $index }}][type]" class="form-select form-select-sm" data-field-type>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(($field['type'] ?? 'string') === $type)>@lang('backend.crud_generator.type_' . $type)</option>
            @endforeach
        </select>
        @error('fields.' . $index . '.type')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <input type="number" min="1" max="255" name="fields[{{ $index }}][max_length]" data-field-max-length
            value="{{ $field['max_length'] ?? 255 }}" class="form-control form-control-sm w-90px" />
        @error('fields.' . $index . '.max_length')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </td>
    <td class="text-center">
        <input type="hidden" name="fields[{{ $index }}][required]" value="0" />
        <input type="checkbox" name="fields[{{ $index }}][required]" value="1" class="form-check-input"
            @checked(!empty($field['required'])) />
    </td>
    <td class="text-center">
        <input type="hidden" name="fields[{{ $index }}][translatable]" value="0" />
        <input type="checkbox" name="fields[{{ $index }}][translatable]" value="1" class="form-check-input"
            data-field-translatable @checked(!empty($field['translatable'])) />
    </td>
    <td class="text-center">
        <input type="hidden" name="fields[{{ $index }}][quill]" value="0" />
        <input type="checkbox" name="fields[{{ $index }}][quill]" value="1" class="form-check-input"
            data-field-quill @checked(!empty($field['quill'])) />
    </td>
    <td class="text-end text-nowrap">
        <button type="button" class="btn btn-icon btn-sm btn-light" data-field-up title="@lang('backend.crud_generator.move_up')">
            <i class="ki-outline ki-arrow-up fs-4"></i>
        </button>
        <button type="button" class="btn btn-icon btn-sm btn-light" data-field-down title="@lang('backend.crud_generator.move_down')">
            <i class="ki-outline ki-arrow-down fs-4"></i>
        </button>
        <button type="button" class="btn btn-icon btn-sm btn-light-danger" data-field-remove title="@lang('backend.crud_generator.remove')">
            <i class="ki-outline ki-trash fs-4"></i>
        </button>
    </td>
</tr>
