<script>var hostUrl = "{{ asset('backend/assets/') }}/";</script>
<script src="{{ asset('backend/assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('backend/assets/js/scripts.bundle.js') }}"></script>
<script src="{{ asset('backend/assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    /**
     * Validates every [required] field inside a form, activates the tab that
     * contains the first invalid field (for multi-language nav-tabs forms)
     * and shows a Metronic-style SweetAlert2 error. Returns true when valid.
     */
    function ktValidateRequired(form) {
        var $form = $(form);
        var hasError = false;
        var firstInvalidEl = null;
        var firstInvalidTabId = null;

        $form.find('[required]').each(function () {
            var $el = $(this);
            var value = ($el.val() || '').toString().trim();
            var $quillWrapper = $el.siblings('.quill-wrapper');

            if (!value) {
                hasError = true;
                $el.addClass('is-invalid');
                $quillWrapper.addClass('is-invalid');

                if (!firstInvalidEl) {
                    firstInvalidEl = $quillWrapper.length ? $quillWrapper : $el;
                }

                var pane = $el.closest('.tab-pane');
                if (pane.length && !firstInvalidTabId) {
                    firstInvalidTabId = pane.attr('id');
                }
            } else {
                $el.removeClass('is-invalid');
                $quillWrapper.removeClass('is-invalid');
            }
        });

        $form.find('[data-bs-toggle="tab"]').each(function () {
            var targetId = ($(this).attr('href') || '').replace('#', '');
            var isInvalid = targetId && $('#' + targetId).find('.is-invalid').length > 0;

            $(this).toggleClass('text-danger', !!isInvalid);
        });

        if (hasError) {
            if (firstInvalidTabId) {
                var tabEl = $form.find('[data-bs-toggle="tab"][href="#' + firstInvalidTabId + '"]').get(0);
                if (tabEl && typeof bootstrap !== 'undefined') {
                    new bootstrap.Tab(tabEl).show();
                }
            }

            if (firstInvalidEl) {
                $('html, body').animate({ scrollTop: firstInvalidEl.offset().top - 150 }, 300);
                firstInvalidEl.trigger('focus');
            }

            Swal.fire({
                icon: 'error',
                text: "{{ trans('backend.messages.error.required_fields') }}" +
                    (firstInvalidTabId ? "\n\n{{ trans('backend.messages.error.required_fields_tabs') }}" : ''),
                buttonsStyling: false,
                confirmButtonText: "{{ trans('backend.buttons.got_it') }}",
                customClass: {
                    confirmButton: 'btn fw-bold btn-primary'
                }
            });
        }

        return !hasError;
    }
</script>
