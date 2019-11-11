define(['jquery', 'cla_select2', '../../css/style.scss'], function ($) {
    var ThankYou = function () {
        this.modal = $('#thank_you_modal');
        this.form = $('.js-thank_you-form');
        this.delete_form = $('.js-thank_you-delete-form');
        this.form_error_template = this.modal.find('.js-form-error-template');
        this.tags_config = {
            width: '100%',
            allowClear: true,
            ajax: {
                url: "/api/thankyou/v2/tags",
                dataType: 'json',
                data: function (params) {
                    return {
                        name: params.term,
                        limit: 4
                    };
                },
                processResults: function (tags) {
                    var results = [];
                    for (var id in tags) {
                        results.push({id: id, text: tags[id].name});
                    }

                    return {results: results};
                }
            }
        };

        this.configureTags();

        $('.js-thank-you-create').on('click', function () {
            var thanked = null;
            var create_thanked = $(this).attr('data-preselected_thanked');
            if (typeof create_thanked === 'string') {
                thanked = JSON.parse(create_thanked);
            }
            thank_you.create(thanked);
        });

        $('.js-thank_you-edit-button').on('click', function () {
            thank_you.edit($(this).attr('data-id'));
        });

        $('.js-thank_you-delete-button').on('click', function () {
            thank_you.delete($(this).attr('data-id'));
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

    ThankYou.prototype.configureTags = function () {
        this.form.find('select[name="thank_you_tags[]"]').select2(this.tags_config);
    };

    ThankYou.prototype.create = function (thanked) {
        this.resetForm();

        if (typeof thanked === 'object' && thanked !== null) {
            this.setThanked([thanked]);
            this.lockThanked(true);
        }
        this.showModal(true);
    };

    ThankYou.prototype.edit = function (id) {
        var self = this;
        $.ajax('/api/thankyou/v2/thanks/' + id, {
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

        if (typeof values === 'object') {
            for (var offset in values) {
                cla_multi_object_picker.addOption(values[offset].object_type.id, values[offset].id, values[offset].object_type.name + ": " + values[offset].name, picker.attr('id'));
            }
        }

        picker.trigger('change');
    };

    ThankYou.prototype.setTags = function (values) {
        var tags = this.getTagsInput();
        tags.val(null);
        tags.html(null);

        if (typeof values === 'object') {
            for (var offset in values) {
                var option = new Option(values[offset].name, values[offset].value);
                tags.append(option);
            }
        }

        tags.trigger('change');
    };

    ThankYou.prototype.setDescription = function (value) {
        this.getDescriptionInput().val(value);
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
        var modal = $('#thank_you_delete_modal');
        if (show === true) {
            modal.modal('show');
        } else {
            modal.modal('hide');
        }
    };

    ThankYou.prototype.lockThanked = function (lock) {
        var picker = this.getThankedInput();
        if (lock) {
            this.displayPicker(false);

            var thanked_names = '';
            var options = cla_multi_object_picker.GetSelected(picker);
            for (var i in options) {
                thanked_names += options[i].text();
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
        var self = this;

        self.resetErrors();

        var id = self.getThankYouIDInput().val();

        if (id === '') {
            id = null;
        }

        var thanked = self.getThankedInput().val();
        var tags = self.getTagsInput().val();
        var description = self.getDescriptionInput().val();

        var body = {
            description: description
        };

        if (typeof thanked === 'object') {
            var thanked_array = [];
            for (var offset in thanked) {
                var thanked_split = thanked[offset].split('_');
                thanked_array.push({oclass: parseInt(thanked_split[0]), id: parseInt(thanked_split[1])});
            }
            body.thanked = thanked_array;
        }

        if (typeof tags === 'object' && tags !== null) {
            var thanked_tags = [];
            for (var offset in tags) {
                thanked_tags.push(parseInt(tags[offset]));
            }
            body.tags = thanked_tags;
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
                var body = response.responseJSON;

                var form_errors = self.form.find('.js-form-error');
                var problem_details_title_error = form_errors.filter('[data-name="problem_details-title"]');
                if (problem_details_title_error.length > 0 && 'title' in body) {
                    self.addError(problem_details_title_error, body.title);
                }

                if ('invalid-params' in body) {
                    for (var offset in body['invalid-params']) {
                        var invalid_param = body['invalid-params'][offset];
                        if ('name' in invalid_param) {
                            var error_container = form_errors.filter('[data-name="' + invalid_param.name + '"]');
                            if (error_container.length > 0 && 'reason' in invalid_param) {
                                self.addError(error_container, invalid_param.reason);
                            }
                        }
                    }
                }
            },
            success: function (response) {
                location.reload();
            }
        });
    };

    ThankYou.prototype.resetErrors = function () {
        var form_errors = this.form.find('.js-form-error');
        form_errors.empty();
    };

    ThankYou.prototype.addError = function (container, message) {
        var error = $(_.template(this.form_error_template.html())({}));
        error.text(message);
        container.append(error);
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

    var thank_you = new ThankYou();

    return thank_you;
});
