define(['jquery', '../../css/style.scss'], function ($) {
    var ListableItemsAdmin = function () {
        this.column_headings = [];

        this.new_item_key_preface = 'new-';
        this.row_new_class = 'listable-item-admin-new';
        this.row_modified_class = 'listable-item-admin-modified';
        this.row_deleted_class = 'listable-item-admin-deleted';
        this.class_editable_field = 'js-listable-item-admin-editable-field';
        this.class_editable_field_error = 'js-listable-item-admin-editable-field-error';
        this.row_class = 'js-listable-item-admin-row';
        this.button_create = $('#js-listable-item-admin-create');
        this.button_class_edit = 'js-listable-item-admin-edit-button';
        this.button_class_reset = 'js-listable-item-admin-reset-button';
        this.button_class_delete = 'js-listable-item-admin-delete-button';
        this.button_next = $('#js-listable-item-admin-nav-next');
        this.button_previous = $('#js-listable-item-admin-nav-previous');
        this.button_save = $('#js-listable-item-admin-save');
        this.button_cancel = $('#js-listable-item-admin-cancel');
        this.limit = 20;
        this.offset = 0;
        this.page = 1;
        this.page_count = 1;

        this.html_template = $('#js-listable-item-admin-item-template');
        this.template_heading = $('#js-lia-template-heading');

        this.localised_edit = $('#js-lia-loc-edit').text();
        this.localised_delete = $('#js-lia-loc-delete').text();
        this.localised_save_edit = $('#js-lia-loc-save-edit').text();
        this.localised_reset = $('#js-lia-loc-reset').text();

        this.items_list = $('#js-listable-item-admin-list');
        //TODO merge the items and store statuses as properties
        this.loaded_items = {};
        this.unlocked_items = {};
        this.modified_items = {};
        this.item_errors = {};
        this.displayed_ids = [];
        this.deleted_items_ids = [];
        this.new_item_ids = [];
        this.editable_field_names = [];
        this.new_offset = 1;
        this.saveUrl = '/api/thankyou/v2/tags';
        this.errors_banner = $('#js-listable-item-admin-errors');
        this.no_results_banner = $('#js-listable-item-admin-no-results');

        this.items_list.on('click', '.' + this.button_class_edit, function () {
            items_admin.toggleEditMode(this.closest('.' + items_admin.row_class).getAttribute('data-id'));
        });

        this.items_list.on('click', '.' + this.button_class_reset, function () {
            var id = this.closest('.' + items_admin.row_class).getAttribute('data-id');
            items_admin.resetEdit(id);
            items_admin.refreshDisplay();
        });

        this.items_list.on('click', '.' + this.button_class_delete, function () {
            var id = this.closest('.' + items_admin.row_class).getAttribute('data-id');
            items_admin.deleteItem(id);
        });
    };

    ListableItemsAdmin.prototype.createNew = function () {
        var template = $($.parseHTML(_.template(this.html_template.html())({})));

        var item = {};
        this.fillItemFromRow(template, item);

        var key = this.new_item_key_preface + this.new_offset;
        this.new_offset++;

        this.loaded_items[key] = item;

        this.new_item_ids.unshift(key);

        this.displayed_ids.unshift(key);

        this.checkModified();

        this.toggleEditMode(key);
    };

    ListableItemsAdmin.prototype.changePage = function (page_number) {
        //TODO: Add a shortcut so that loadItems is only run if we know there are items not in loaded_items. Not urgent, just speedier.
        var self = this;
        this.page = page_number;
        this.offset = (self.page - 1) * this.limit;
        this.loadItems(this.limit, this.offset, function (items) {

            self.displayed_ids = [];

            for (var offset in self.new_item_ids) {
                self.displayed_ids.push(self.new_item_ids[offset]);
            }

            for (var id in items) {
                self.loaded_items[id] = items[id];
                self.displayed_ids.push(id);
            }
            self.refreshDisplay();
        });

        this.checkPageNavigation();
    };

    ListableItemsAdmin.prototype.loadItems = function (limit, offset, callback) {
        $.ajax('/api/thankyou/v2/tags?limit=' + limit + '&offset=' + offset).done(function (items) {
            if (typeof callback === 'function') {
                callback(items);
            }
        });
    };

    ListableItemsAdmin.prototype.fillItemFromRow = function (row, item) {
        row.find('.' + this.class_editable_field).each(function () {
            var field = $(this);
            var type = field.attr('type');
            var name = field.attr('data-name');

            var value = null;
            if (type === 'text') {
                value = field.val();
                field.val(value);
            } else if (type === 'checkbox') {
                value = field.prop('checked');
            } else {
                console.log('Unsupported field type ' + type);
            }

            item[name] = value;
        });
    };

    ListableItemsAdmin.prototype.toggleEditMode = function (id) {
        if (id in this.unlocked_items) {
            this.updateItemFromForm(id);
            delete this.unlocked_items[id];
        } else {
            this.unlocked_items[id] = true;
        }

        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.deleteItem = function (id) {
        if (this.new_item_ids.includes(id)) {
            this.forgetItem(id);
        } else if (!this.deleted_items_ids.includes(id)) {
            this.deleted_items_ids.push(id);
        }

        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.forgetItem = function (id) {
        if (this.new_item_ids.includes(id)) {
            var new_offset = this.new_item_ids.indexOf(id);
            this.new_item_ids.splice(new_offset, 1);
        }

        if (this.displayed_ids.includes(id)) {
            var displayed_offset = this.displayed_ids.indexOf(id);
            this.displayed_ids.splice(displayed_offset, 1);
        }

        if (id in this.modified_items) {
            delete this.modified_items[id];
        }

        if (id in this.loaded_items) {
            delete this.loaded_items[id];
        }

        if (id in this.unlocked_items) {
            delete this.unlocked_items[id];
        }

        if (this.deleted_items_ids.includes(id)) {
            var deleted_offset = this.deleted_items_ids.indexOf(id);
            this.deleted_items_ids.splice(deleted_offset, 1);
        }

        if (id in this.item_errors) {
            delete this.item_errors[id];
        }
    };

    ListableItemsAdmin.prototype.loadPageCount = function () {
        var self = this;
        $.ajax('/api/thankyou/v2/tags/total').done(function (total) {
            self.page_count = Math.ceil(total / self.limit);

            self.changePage(self.page);
        });
    };

    ListableItemsAdmin.prototype.getItem = function (id) {
        var item = {};
        if (id in this.modified_items) {
            item = this.modified_items[id];
        } else {
            item = this.loaded_items[id];
        }
        return item;
    };

    ListableItemsAdmin.prototype.getModifiedItem = function (id) {
        if (!(id in this.modified_items)) {
            this.modified_items[id] = JSON.parse(JSON.stringify(this.loaded_items[id]));
        }
        return this.modified_items[id];
    };

    ListableItemsAdmin.prototype.updateItemFromForm = function (id) {
        var row = $('.' + this.row_class + '[data-id=' + id + ']');
        var item = this.getModifiedItem(id);

        this.fillItemFromRow(row, item);

        if (_.isEqual(item, this.loaded_items[id])) {
            this.resetEdit(id);
        }
        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.resetAll = function () {

        var offset;
        for (offset in this.new_item_ids) {
            this.forgetItem(this.new_item_ids[offset]);
        }

        for (var id in this.modified_items) {
            this.resetEdit(id);
        }

        for (offset in this.deleted_items_ids) {
            this.resetEdit(this.deleted_items_ids[offset]);
        }

        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.resetEdit = function (id) {
        delete this.modified_items[id];
        delete this.item_errors[id];

        if (this.deleted_items_ids.includes(id)) {
            this.deleted_items_ids.splice(this.deleted_items_ids.indexOf(id));
        }
        if (this.new_item_ids.includes(id)) {
            this.getModifiedItem(id);
        }
    };

    ListableItemsAdmin.prototype.save = function () {
        var self = this;

        self.item_errors = {};

        var new_item_ids = self.new_item_ids;
        var modified_items = self.modified_items;
        var url = self.saveUrl;

        var body = {
            created: {},
            modified: {},
            deleted: self.deleted_items_ids
        };

        for (var id in modified_items) {
            if (new_item_ids.includes(id)) {
                body.created[id] = modified_items[id];
            } else {
                body.modified[id] = modified_items[id];
            }
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(body)
        }).done(function (response) {
            if ('errors' in response) {
                self.item_errors = response.errors;
            }

            for (var id in self.loaded_items) {
                if (!(id in self.item_errors)) {
                    self.forgetItem(id);
                }
            }

            self.changePage(1);
            self.loadPageCount();
        });
    };

    ListableItemsAdmin.prototype.refreshDisplay = function () {
        this.items_list.empty();

        for (var offset in this.column_headings) {
            var heading = $($.parseHTML(_.template(this.template_heading.html())({})));
            heading.append(this.column_headings[offset]);
            this.items_list.append(heading);
        }

        if (Object.keys(this.item_errors).length > 0) {
            this.errors_banner.show();
        } else {
            this.errors_banner.hide();
        }

        var displayed_ids = this.displayed_ids;

        if (displayed_ids.length === 0) {
            this.no_results_banner.show();
        } else {
            this.no_results_banner.hide();
            for (var offset in displayed_ids) {
                var item = this.getItem(displayed_ids[offset]);
                this.items_list.append(this.displayRow(displayed_ids[offset], item));
            }
        }

        this.checkModified();
    };

    ListableItemsAdmin.prototype.displayRow = function (id, item) {
        var new_item = this.new_item_ids.includes(id);
        var edit_mode = (id in this.unlocked_items);
        var modified = (id in this.modified_items);
        var deleted = this.deleted_items_ids.includes(id);

        var template = $($.parseHTML(_.template(this.html_template.html())({})));

        if (new_item) {
            template.addClass(this.row_new_class);
        } else if (deleted) {
            template.addClass(this.row_deleted_class);
        } else if (modified) {
            template.addClass(this.row_modified_class);
        }

        var edit_button = template.find('.' + this.button_class_edit);
        var reset_button = template.find('.' + this.button_class_reset);
        var delete_button = template.find('.' + this.button_class_delete);

        template.find('.' + this.class_editable_field).prop('disabled', !edit_mode);

        edit_button.text(edit_mode ? this.localised_save_edit : this.localised_edit);
        delete_button.text(this.localised_delete);
        reset_button.text(this.localised_reset);

        edit_button.hide();
        reset_button.hide();
        delete_button.hide();

        if ((!new_item && (deleted || modified)) || (new_item && edit_mode)) {
            reset_button.show();
        }
        if (!deleted) {
            edit_button.show();
        }
        if ((new_item && !edit_mode) || (!deleted && !edit_mode && !modified)) {
            delete_button.show();
        }

        template.attr('data-id', id);

        this.fillRowFields(id, template, item);

        return template;
    };

    ListableItemsAdmin.prototype.fillRowFields = function (id, template, item) {
        var self = this;
        template.find('.' + this.class_editable_field).each(function () {
            var field = $(this);
            var type = field.attr('type');
            var name = field.attr('data-name');
            //TODO: Add lodash 'has' for deeply complex objects, once lodash exists.
            var value = (name in item ? item[name] : null);

            if (type === 'text') {
                field.val(value);
            } else if (type === 'checkbox') {
                field.attr('checked', value);
            } else {
                console.log('Unsupported field type ' + type);
            }

            if (id in self.item_errors && name in self.item_errors[id]) {
                var error_banner = field.next('.' + self.class_editable_field_error);
                if (error_banner.length > 0) {
                    error_banner.text(self.item_errors[id][name]);
                }
            }
        });
    };

    ListableItemsAdmin.prototype.checkModified = function () {
        if (Object.keys(this.modified_items).length !== 0 || this.deleted_items_ids.length > 0) {
            this.button_save.show();
            this.button_cancel.show();
        } else {
            this.button_save.hide();
            this.button_cancel.hide();
        }
    };

    ListableItemsAdmin.prototype.checkPageNavigation = function () {
        var page_number = this.page;
        if (page_number === 1) {
            this.button_previous.hide();
        } else {
            this.button_previous.show();
        }

        if (page_number < this.page_count) {
            this.button_next.show();
        } else {
            this.button_next.hide();
        }
    };

    ListableItemsAdmin.prototype.getFieldsFromTemplate = function () {
        var template = $($.parseHTML(this.html_template.html()));
        var fields = [];
        template.find('.' + this.class_editable_field).each(function () {
            fields.push($(this).attr('data-name'));
        });
        this.editable_field_names = fields;
    };

    var items_admin = new ListableItemsAdmin();

    items_admin.getFieldsFromTemplate();

    items_admin.loadPageCount();

    items_admin.button_create.click(function () {
        items_admin.createNew();
    });
    items_admin.button_next.click(function () {
        items_admin.changePage(items_admin.page + 1)
    });
    items_admin.button_previous.click(function () {
        items_admin.changePage(items_admin.page - 1)
    });
    items_admin.button_save.click(function () {
        items_admin.save();
    });
    items_admin.button_cancel.click(function () {
        items_admin.resetAll();
    });

    return items_admin;
});
