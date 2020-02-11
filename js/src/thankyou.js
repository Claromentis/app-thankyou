define(['jquery', 'cla_select2'], function ($) {
    var ThankYou = function (list) {
        this.list = list;
        this.modal = list.find('.js-thank_you-modal').first();
        this.form = this.modal.find('.js-thank_you-form');
        this.delete_modal = list.find('.js-thank_you-delete-modal');
        this.delete_form = this.delete_modal.find('.js-thank_you-delete-form');
        this.form_error_template = _.template(this.modal.find('.js-form-error-template').html());

        this.tags_config = {
            width: '100%',
            allowClear: true,
            ajax: {
                url: "/api/thankyou/v2/tags",
                dataType: 'json',
                data: function (params) {
                    return {
                        name: params.term,
                        active: true,
                        limit: 4
                    };
                },
                processResults: function (tags) {
                    var results = [];
                    var tags_length = tags.length;
                    for (var index = 0; index < tags_length; index++) {
                        results.push({id: tags[index].id, text: tags[index].name});
                    }

                    return {results: results};
                }
            },
            placeholder: lmsg('thankyou.common.tags.multiselect')
        };

        this.configureTags();
        this.registerEventListeners();
    };

    ThankYou.prototype.configureTags = function () {
        this.form.find('select[name="thank_you_tags[]"]').select2(this.tags_config);
    };

    ThankYou.prototype.create = function (thanked) {
        this.resetForm();

        if (typeof thanked === 'object' && thanked !== null) {
            this.setThanked(thanked);
            this.lockThanked(true);
        }
        this.showModal(true);
    };

    ThankYou.prototype.edit = function (id) {
        var self = this;
        $.ajax('/api/thankyou/v2/thanks/' + id + '?thanked=1&tags=1', {
            success: function (data) {
                self.populateForm(data);
                self.lockThanked(false);
                self.showModal(true);
            }
        });
    };

    ThankYou.prototype.delete = function (id) {
        this.setDeleteID(id);
        this.showDeleteModal(true);
    };

    ThankYou.prototype.resetForm = function () {
        this.resetErrors();
        this.setThankYouID(null);
        this.setThanked(null);
        this.setTags(null);
        this.setDescription(null);
    };

    ThankYou.prototype.populateForm = function (thank_you) {
        this.resetErrors();
        this.setThankYouID(thank_you.id);

        if ('thanked' in thank_you) {
            this.setThanked(thank_you.thanked);
        }

        if ('tags' in thank_you) {
            this.setTags(thank_you.tags);
        }

        this.setDescription(thank_you.description);
    };

    ThankYou.prototype.getThankYouIDInput = function () {
        return this.form.find('input[name="id"]');
    };

    ThankYou.prototype.getThankedInput = function () {
        return this.form.find('select[name="thank_you_user[]"]');
    };

    ThankYou.prototype.getTagsInput = function () {
        return this.form.find('select[name="thank_you_tags[]"]');
    };

    ThankYou.prototype.getDescriptionInput = function () {
        return this.form.find('textarea[name="thank_you_description"]');
    };

    ThankYou.prototype.getDeleteIDInput = function () {
        return this.delete_form.find('input[name="id"]');
    };

    ThankYou.prototype.setThankYouID = function (value) {
        this.getThankYouIDInput().val(value);
    };

    ThankYou.prototype.setThanked = function (values) {
        var picker = this.getThankedInput();

        picker.val(null);
        picker.html(null);

        if (Array.isArray(values)) {
            var values_length = values.length;
            for (var index = 0; index < values_length; index++) {
                cla_multi_object_picker.addOption(values[index].object_type.id, values[index].id, values[index].object_type.name + ": " + values[index].name, picker.attr('id'));
            }
        }

        picker.trigger('change');
    };

    ThankYou.prototype.setTags = function (tags) {
        var tags_input = this.getTagsInput();
        tags_input.val(null);
        tags_input.html(null);

        if (Array.isArray(tags)) {
            var tags_length = tags.length;
            for (var tags_offset = 0; tags_offset < tags_length; tags_offset++) {
                var option = new Option(tags[tags_offset].name, tags[tags_offset].id, null, true);
                tags_input.append(option);
            }
        }

        tags_input.trigger('change');
    };

    ThankYou.prototype.setDescription = function (value) {
        this.getDescriptionInput().val(value);
    };

    ThankYou.prototype.setDescriptionMaxLength = function (length) {
        this.getDescriptionInput().attr('maxlength', length);
    };

    ThankYou.prototype.setPreselected = function (string) {
        this.form.find('.js-thank_you-thanked-names').text(string);
    };

    ThankYou.prototype.setDeleteID = function (id) {
        this.getDeleteIDInput().val(id);
    };

    ThankYou.prototype.showModal = function (show) {
        if (show === true) {
            this.modal.modal('show');
        } else {
            this.modal.modal('hide');
        }
    };

    ThankYou.prototype.showDeleteModal = function (show) {
        if (show === true) {
            this.delete_modal.modal('show');
        } else {
            this.delete_modal.modal('hide');
        }
    };

    ThankYou.prototype.lockThanked = function (lock) {
        var picker = this.getThankedInput();
        if (lock) {
            this.displayPicker(false);

            var thanked_names = '';
            var options = cla_multi_object_picker.GetSelected(picker);
            var options_length = options.length;
            for (var options_offset = 0; options_offset < options_length; options_offset++) {
                thanked_names += options[options_offset].text();
            }
            this.setPreselected(thanked_names);
            this.displayThankedNames(true);
        } else {
            this.displayThankedNames(false);
            this.setPreselected('');
            this.displayPicker(true);
        }
    };

    ThankYou.prototype.displayPicker = function (display) {
        var picker_container = this.form.find('.js-thank_you-picker-container');
        if (display === true) {
            picker_container.show();
        } else {
            picker_container.hide();
        }
    };

    ThankYou.prototype.displayThankedNames = function (show) {
        var thanked_names_container = this.form.find('.js-thank_you-thanked-names-container');
        if (show === true) {
            thanked_names_container.show();
        } else {
            thanked_names_container.hide();
        }
    };

    ThankYou.prototype.submit = function () {
        $('.btn-submit-modal').prop('disabled', true);
        var self = this;

        self.resetErrors();

        var id = self.getThankYouIDInput().val();

        if (id === '') {
            id = null;
        }

        var thanked = self.getThankedInput().val();
        var description = self.getDescriptionInput().val();

        var body = {
            description: description
        };

        if (Array.isArray(thanked)) {
            var thanked_array = [];
            var thanked_length = thanked.length;
            for (var thanked_offset = 0; thanked_offset < thanked_length; thanked_offset++) {
                var thanked_split = thanked[thanked_offset].split('_');
                thanked_array.push({oclass: parseInt(thanked_split[0]), id: parseInt(thanked_split[1])});
            }
            body.thanked = thanked_array;
        }

        var tags_input = self.getTagsInput();
        if (tags_input !== null) {
            var tags = tags_input.val();
            body.tags = [];
            if (Array.isArray(tags)) {
                var tags_length = tags.length;
                for (var tags_offset = 0; tags_offset < tags_length; tags_offset++) {
                    body.tags.push(parseInt(tags[tags_offset]));
                }
            }
        }

        var url = '/api/thankyou/v2/thankyou';
        if (id !== null) {
            url += '/' + id;
        }


        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(body),
            error: function (response) {
                $('.btn-submit-modal').prop('disabled', false);
                var body = response.responseJSON;

                var form_errors = self.form.find('.js-form-error');
                var problem_details_title_error = form_errors.filter('[data-name="problem_details-title"]');

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
                    return;

                }
                if (problem_details_title_error.length > 0 && 'title' in body && !error_displayed) {
                    self.addError(problem_details_title_error, body.title);
                }
            },
            success: function (response) {
                window.location.reload();
            }
        });
    };

    ThankYou.prototype.resetErrors = function () {
        var form_errors = this.form.find('.js-form-error');
        form_errors.empty();
    };

    ThankYou.prototype.addError = function (container, message) {
        var error = $(this.form_error_template({message: message}));
        container.html(error);

    };

    ThankYou.prototype.submitDelete = function () {
        var self = this;

        var id = self.getDeleteIDInput().val();

        $.ajax({
            url: '/api/thankyou/v2/thankyou/' + id,
            type: 'DELETE',
            error: function (response) {
                var body = response.responseJSON;

                var form_errors = self.delete_form.find('.js-form-error');
                var problem_details_title_error = form_errors.filter('[data-name="problem_details-title"]');
                if (problem_details_title_error.length > 0 && 'title' in body) {
                    self.addError(problem_details_title_error, body.title);
                }
            },
            success: function (response) {
                location.reload();
            }
        });
    };

    ThankYou.prototype.registerEventListeners = function () {
        var self = this;
        this.list.on('click', '.js-thank-you-create', function () {
            var thanked = null;
            var create_thanked = $(this).attr('data-preselected_thanked');
            if (typeof create_thanked === 'string') {
                thanked = JSON.parse(create_thanked);
            }
            self.create(thanked);
        });

        this.list.on('click', '.js-thank_you-edit-button', function () {
            self.edit($(this).attr('data-id'));
        });

        this.list.on('click', '.js-thank_you-delete-button', function () {
            self.delete($(this).attr('data-id'));
        });

        this.list.on('click', '.js-comments-reveal', function (event) {
            $(event.target).closest('.js-thank-you').find('.js-comments').toggle();
        });

        this.form.on('submit', null, this, function (event) {
            event.preventDefault();
            event.data.submit();
        });

        this.delete_form.on('submit', null, this, function (event) {
            event.preventDefault();
            event.data.submitDelete();
        });
    };

    return ThankYou;
});
