require(["/intranet/thankyou/js/build/tag.bundle.js"], function (tagApi) {
    var core_values = $('#core_values_enabled_body');

    core_values.on('click', '.js-tag-active', function () {
        var active = $(this).attr('data-value');
        active = !(active === "0" || active === 0);
        $(this).tooltip('hide');
        tagApi.setActive(
            $(this).parent().attr('data-id'),
            active,
            function () {
                tag_modal.reloadDataTable();
            }
        );
    });

    $('#tag_modal, input[name="tag_name"], .js-color-confirm').on('keypress', function (event) {
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if (keycode == '13') {
            $('.js-tag-form').submit();
        }
    });

    core_values.on('click', '.js-tag-create', function () {
        tag_modal.resetForm();
        tag_modal.showModal(true);
        tag_modal.modal.find('input[name="tag_bg_colour"]').val('#00adef');
        tag_modal.updateColourPickerBg();
    });


    core_values.on('click', '.js-tag-edit', function () {
        $(this).tooltip('hide');
        tag_modal.populateFromId($(this).parent().attr('data-id'));
    });

    core_values.on('click', '.js-tag-delete', function () {
        tag_delete_modal.populateFromId($(this).parent().attr('data-id'));
    });

    $('.js-tag-form').on('submit', function (event) {
        event.preventDefault();
        tag_modal.submit();
    });

    var tag_modal = {
        repository: tagApi.repository,
        modal: $('#tag_modal'),
        error_template: $('#js_modal_error_template'),
        populateFromId: function (id) {
            var self = this;
            this.repository.get(id, function (data) {
                self.populateFromTag(data);
                self.updateColourPickerBg();
            });
        },
        populateFromTag: function (tag) {
            this.modal.find('input[name="tag_id"]').val(+tag.id);
            this.modal.find('input[name="tag_name"]').val(tag.name);
            this.modal.find('input[name="tag_bg_colour"]').val(tag.bg_colour);
            this.resetErrors();
            this.showModal(true);
        },
        updateColourPickerBg: function () {
            var colourPicker = $('#tag_modal').find($('.js-color-confirm'));
            colourPicker.css('background-color', colourPicker.val());
        },
        showModal: function (show) {
            if (show === true) {
                this.modal.modal('show');
            } else {
                this.modal.modal('hide');
            }
        },
        reloadDataTable: function () {
            $('#tags_datatable table').dataTable().api().draw(false);
        },
        resetForm: function () {
            this.modal.find('input[name="tag_id"]').val(null);
            this.modal.find('input[name="tag_name"]').val(null);
            this.modal.find('input[name="tag_bg_colour"]').val(null);

            this.resetErrors();
        },
        resetErrors: function () {
            var form_errors = this.modal.find('.js-form-error');
            form_errors.empty();
        },
        submit: function () {
            var self = this;

            self.resetErrors();

            var id = +this.modal.find('input[name="tag_id"]').val();
            if (!(id > 0)) {
                id = null;
            }
            var name = this.modal.find('input[name="tag_name"]').val();
            var bg_colour = this.modal.find('input[name="tag_bg_colour"]').val();

            var tag = {
                name: name,
                bg_colour: bg_colour
            };

            if (id !== null) {
                tag.id = id;
            }

            this.repository.save(tag,
                function () {
                    self.showModal(false);
                    self.reloadDataTable();
                },
                function (response) {
                    var body = response.responseJSON;

                    var form_errors = self.modal.find('.js-form-error');
                    var error_displayed = false;
                    if ('invalid-params' in body && Array.isArray(body['invalid-params'])) {
                        var invalid_params_length = body['invalid-params'].length;
                        for (var invalid_params_offset = 0; invalid_params_offset < invalid_params_length; invalid_params_offset++) {
                            var invalid_param = body['invalid-params'][invalid_params_offset];
                            if ('name' in invalid_param) {
                                var error_container = form_errors.filter('[data-name="' + invalid_param.name + '"]');
                                if (error_container.length > 0 && 'reason' in invalid_param) {
                                    self.addError(error_container, invalid_param.reason);
                                    error_displayed = true;
                                }
                            }
                        }
                    }

                    var problem_details_title_error = form_errors.filter('[data-name="problem_details-title"]');
                    if (problem_details_title_error.length > 0 && 'title' in body && !error_displayed) {
                        self.addError(problem_details_title_error, body.title);
                    }
                }
            );
        },
        addError: function (container, message) {
            var error = $(_.template(this.error_template.html())({}));
            error.text(message);
            container.append(error);
        }
    };

    var tag_delete_modal = {
        repository: tagApi.repository,
        modal: $('#tag_delete_modal'),
        error_template: $('#js_modal_error_template'),
        resetErrors: function () {
            var form_errors = this.modal.find('.js-form-error');
            form_errors.empty();
        },
        populateFromId: function (id) {
            this.modal.find('input[name="id"]').val(id);
            this.resetErrors();
            this.showModal(true);
        },
        showModal: function (show) {
            if (show === true) {
                this.modal.modal('show');
            } else {
                this.modal.modal('hide');
            }
        },
        submit: function () {
            var self = this;

            self.resetErrors();

            var id = this.modal.find('input[name="id"]').val();

            this.repository.delete(id,
                function () {
                    self.showModal(false);
                    self.reloadDataTable();
                },
                function (response) {
                    var body = response.responseJSON;

                    var form_errors = self.modal.find('.js-form-error');
                    var problem_details_title_error = form_errors.filter('[data-name="problem_details-title"]');
                    if (problem_details_title_error.length > 0 && 'title' in body) {
                        self.addError(problem_details_title_error, body.title);
                    }
                }
            );
        },
        addError: function (container, message) {
            var error = $(_.template(this.error_template.html())({}));
            error.text(message);
            container.append(error);
        },
        reloadDataTable: function () {
            $('#tags_datatable table').dataTable().api().ajax.reload();
        }
    };

    tag_delete_modal.modal.find('.js-tag-delete-submit').on('click', function () {
        tag_delete_modal.submit();
    });
});
